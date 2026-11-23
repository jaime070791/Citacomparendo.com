<?php
/**
 * Sincronización de sedes desde el CRM (/api/obtener-ubicaciones).
 *
 * Diferencia deliberada con el tema anterior: si el CRM responde vacío, con
 * un formato inválido o con error HTTP, NO se toca ninguna sede. El tema
 * original alternaba el estado de todas las sedes en ese caso, lo que aquí
 * borraría el catálogo completo ante un fallo del CRM.
 *
 * La API tampoco desactiva sedes que ya no aparecen en la respuesta: el
 * catálogo solo se da de baja a mano (o si el CRM envía estado_sede_motor
 * inactivo para esa sede).
 *
 * Una sede sin coordenadas válidas o sin dirección (el CRM manda "-" en las
 * sedes administrativas) se crea como borrador. Si ya estaba publicada se
 * mantiene publicada: el sync nunca despublica.
 *
 * La identidad es `complemento_iframe`: sin ese dato la sede se omite
 * (stats.omitidas) y no se escribe nada. El estado manual se respeta salvo
 * que el propio sync la haya publicado antes (`_cita_sync_publicada`).
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Subpágina Sedes → Sincronizar sedes.
 *
 * @return void
 */
function cita_comparendo_sync_admin_menu()
{
    add_submenu_page(
        'edit.php?post_type=sede',
        __('Sincronizar sedes', 'cita-comparendo'),
        __('Sincronizar sedes', 'cita-comparendo'),
        'manage_options',
        'cita-sincronizar-sedes',
        'cita_comparendo_render_sync_page'
    );
}
add_action('admin_menu', 'cita_comparendo_sync_admin_menu');

/**
 * Configuración que consume assets/js/sedes-sync.js.
 *
 * @return array
 */
function cita_comparendo_sedes_sync_config()
{
    return array(
        'ajaxUrl'     => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce('sync-sedes-nonce'),
        'nonceProbar' => wp_create_nonce('cita-probar-crm'),
        'crmUrl'      => cita_comparendo_crm_url(),
        'strings'     => array(
            'confirmar'  => __('Esto crea y actualiza sedes en WordPress con los datos del CRM. ¿Continuar?', 'cita-comparendo'),
            'sincronizando' => __('Sincronizando…', 'cita-comparendo'),
            'probando'   => __('Probando conexión…', 'cita-comparendo'),
            'error'      => __('No se pudo completar la operación.', 'cita-comparendo'),
            'listo'      => __('Listo.', 'cita-comparendo'),
        ),
    );
}

/**
 * Pinta la página de sincronización.
 *
 * @return void
 */
function cita_comparendo_render_sync_page()
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para sincronizar sedes.', 'cita-comparendo'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Sincronizar sedes', 'cita-comparendo'); ?></h1>

        <p>
            <?php
            printf(
                /* translators: %s: URL del CRM. */
                esc_html__('Consulta %s y crea o actualiza las sedes de WordPress con los datos del CRM.', 'cita-comparendo'),
                '<code>' . esc_html(cita_comparendo_crm_url() . '/api/obtener-ubicaciones') . '</code>'
            );
            ?>
        </p>

        <p class="description">
            <?php esc_html_e('Si el CRM no responde o devuelve una lista vacía no se modifica ninguna sede. Las sedes que ya no aparecen en el CRM tampoco se desactivan: eso se hace a mano.', 'cita-comparendo'); ?>
            <?php esc_html_e('Las sedes nuevas sin dirección o sin coordenadas válidas se crean en borrador; una sede ya publicada no se despublica. Las que no traen complemento_iframe se omiten.', 'cita-comparendo'); ?>
        </p>

        <?php if (cita_comparendo_sync_limite() > 0) : ?>
            <p class="description">
                <?php
                printf(
                    /* translators: %d: número máximo de sedes. */
                    esc_html__('Límite configurado: se procesarán como máximo %d sedes por sincronización.', 'cita-comparendo'),
                    (int) cita_comparendo_sync_limite()
                );
                ?>
            </p>
        <?php endif; ?>

        <p>
            <button type="button" id="cita-sync-sedes" class="button button-primary">
                <?php esc_html_e('Sincronizar sedes ahora', 'cita-comparendo'); ?>
            </button>
            <button type="button" id="cita-probar-crm" class="button">
                <?php esc_html_e('Probar conexión', 'cita-comparendo'); ?>
            </button>
        </p>

        <pre id="cita-sync-salida" style="white-space:pre-wrap;background:#fff;border:1px solid #dcdcde;padding:12px;max-height:420px;overflow:auto;"></pre>
    </div>
    <?php
}

/**
 * ¿El arreglo es una lista indexada (0..n-1)?
 *
 * @param array $valor Arreglo a comprobar.
 * @return bool
 */
function cita_comparendo_es_lista(array $valor)
{
    if (function_exists('array_is_list')) {
        return array_is_list($valor);
    }

    $i = 0;
    foreach ($valor as $clave => $sin_uso) {
        if ($clave !== $i) {
            return false;
        }
        $i++;
    }

    return true;
}

/**
 * Extrae la lista de ubicaciones de la respuesta del CRM.
 *
 * Acepta una lista directa (formato actual) o un envoltorio `Data`.
 *
 * @param mixed $respuesta Respuesta decodificada.
 * @return array|WP_Error
 */
function cita_comparendo_extraer_ubicaciones($respuesta)
{
    if (! is_array($respuesta)) {
        return new WP_Error(
            'cita_sync_formato',
            __('El CRM devolvió una respuesta con formato inesperado. No se modificó ninguna sede.', 'cita-comparendo')
        );
    }

    if (cita_comparendo_es_lista($respuesta)) {
        return $respuesta;
    }

    if (isset($respuesta['Data']) && is_array($respuesta['Data']) && cita_comparendo_es_lista($respuesta['Data'])) {
        return $respuesta['Data'];
    }

    return new WP_Error(
        'cita_sync_formato',
        __('El CRM devolvió una respuesta con formato inesperado. No se modificó ninguna sede.', 'cita-comparendo')
    );
}

/**
 * Limpia un valor de texto que viene del CRM.
 *
 * El CRM usa "-" como "sin dato"; se traduce a cadena vacía para no escribir
 * guiones en la landing.
 *
 * @param mixed $valor       Valor de entrada.
 * @param bool  $multilinea  Permitir saltos de línea (horarios).
 * @return string
 */
function cita_comparendo_limpiar_texto($valor, $multilinea = false)
{
    if (is_null($valor) || is_bool($valor) || is_array($valor) || is_object($valor)) {
        return '';
    }

    $valor = trim((string) $valor);

    if (in_array(strtolower($valor), array('-', '--', '—', '–', 'n/a', 'na', 'null', 'undefined'), true)) {
        return '';
    }

    return $multilinea ? sanitize_textarea_field($valor) : sanitize_text_field($valor);
}

/**
 * Crea o actualiza el término `ciudad` con las coordenadas del mapa.
 *
 * @param array $ubicacion Ubicación del CRM.
 * @param string $nombre   Nombre limpio de la ciudad.
 * @return int|WP_Error ID del término.
 */
/**
 * Crea o actualiza un término de taxonomía con sus metadatos de mapa.
 *
 * @param string $taxonomy Taxonomía registrada.
 * @param string $nombre   Nombre del término.
 * @param array  $meta     latitud, longitud, nivel_zoom, estado.
 * @return array|WP_Error ['term_id' => int, 'action' => 'created'|'updated']
 */
function cita_comparendo_upsert_termino($taxonomy, $nombre, array $meta = array(), $parent = 0)
{
    $taxonomy = sanitize_key($taxonomy);
    $nombre = sanitize_text_field((string) $nombre);
    $parent = max(0, (int) $parent);

    if ('' === $taxonomy || ! taxonomy_exists($taxonomy)) {
        return new WP_Error('cita_termino_taxonomia', __('La taxonomía no existe.', 'cita-comparendo'));
    }

    if ('' === $nombre) {
        return new WP_Error('cita_termino_nombre', __('El término no tiene nombre.', 'cita-comparendo'));
    }

    $existente = term_exists($nombre, $taxonomy, $parent);
    $term_id = 0;
    $action = 'updated';

    if (is_array($existente) && isset($existente['term_id'])) {
        $term_id = (int) $existente['term_id'];
    } elseif (is_int($existente)) {
        $term_id = $existente;
    } elseif (is_object($existente) && isset($existente->term_id)) {
        $term_id = (int) $existente->term_id;
    } else {
        $argumentos = array('slug' => sanitize_title($nombre));

        if ($parent > 0) {
            $argumentos['parent'] = $parent;
        }

        $creado = wp_insert_term($nombre, $taxonomy, $argumentos);

        if (is_wp_error($creado)) {
            // Mismo slug con otro nombre (tildes, mayúsculas): reutilizamos.
            if ('term_exists' === $creado->get_error_code()) {
                $datos = $creado->get_error_data();

                if (is_array($datos) && isset($datos['term_id'])) {
                    $term_id = (int) $datos['term_id'];
                } elseif (is_numeric($datos)) {
                    $term_id = (int) $datos;
                }
            }

            if (! $term_id) {
                return $creado;
            }
        } else {
            $term_id = (int) $creado['term_id'];
            $action = 'created';
        }
    }

    if (isset($meta['latitud'])) {
        $lat = cita_comparendo_normalizar_coordenada($meta['latitud'], 'lat');

        if ('' !== $lat) {
            update_term_meta($term_id, 'latitud', $lat);
        }
    }

    if (isset($meta['longitud'])) {
        $lng = cita_comparendo_normalizar_coordenada($meta['longitud'], 'lng');

        if ('' !== $lng) {
            update_term_meta($term_id, 'longitud', $lng);
        }
    }

    if (isset($meta['nivel_zoom']) && is_scalar($meta['nivel_zoom']) && '' !== $meta['nivel_zoom']) {
        $zoom = (int) $meta['nivel_zoom'];

        if ($zoom > 0) {
            update_term_meta($term_id, 'nivel_zoom', (string) max(1, min(20, $zoom)));
        }
    }

    if (isset($meta['estado']) && is_scalar($meta['estado'])) {
        $estado = sanitize_text_field((string) $meta['estado']);

        if ('' !== $estado) {
            update_term_meta($term_id, 'estado', $estado);
        }
    }

    return array(
        'term_id' => $term_id,
        'action'  => $action,
    );
}

/**
 * Crea o actualiza una sede con datos del CRM (mismo código para push y pull).
 *
 * Identidad: `_sede_id_api` y, por compatibilidad, `complemento_iframe`. Al
 * guardar se escriben ambos. Nunca se tocan `post_name` ni `post_content`.
 *
 * @param array $datos Datos normalizados:
 *                     id_api, id_empresa, titulo, direccion, horario, telefono,
 *                     barrio, url_video, url_imagen, lat, lng,
 *                     estado_sede_motor, whatsapp, price_range, schema_type,
 *                     ciudad (array nombre/latitud/longitud/nivel_zoom/estado),
 *                     localidad (igual), ciudad_term_id, ciudad_nombre,
 *                     localidad_term_id, zona (texto libre por ciudad).
 * @return array Resultado con success, action, id, message, reason, estado,
 *               incompleta, reactivated, deactivated, warning, ciudad,
 *               localidad, zona.
 */
function cita_comparendo_upsert_sede(array $datos)
{
    $resultado = array(
        'success'     => false,
        'action'      => 'skipped',
        'id'          => 0,
        'message'     => '',
        'reason'      => '',
        'estado'      => '',
        'incompleta'  => false,
        'reactivated' => false,
        'deactivated' => false,
        'warning'     => '',
        'ciudad'      => null,
        'localidad'   => null,
    );

    $id_api = isset($datos['id_api']) && is_scalar($datos['id_api'])
        ? trim(sanitize_text_field((string) $datos['id_api']))
        : '';

    $titulo = isset($datos['titulo']) ? cita_comparendo_limpiar_texto($datos['titulo']) : '';

    if ('' === $id_api) {
        $resultado['reason'] = 'sin_id';
        $resultado['message'] = sprintf(
            /* translators: %s: título de la sede. */
            __('Sede "%s" sin identificador del CRM: no se sincroniza.', 'cita-comparendo'),
            $titulo
        );

        return $resultado;
    }

    if ('' === $titulo) {
        $titulo = 'Sede ' . $id_api;
    }

    $advertencias = array();

    // Ciudad y localidad (términos + term meta).
    $ciudad_res = null;
    if (! empty($datos['ciudad_term_id'])) {
        $ciudad_res = array('term_id' => (int) $datos['ciudad_term_id'], 'action' => 'updated');
    } elseif (isset($datos['ciudad']) && is_array($datos['ciudad']) && ! empty($datos['ciudad']['nombre'])) {
        $ciudad_res = cita_comparendo_upsert_termino('ciudad', $datos['ciudad']['nombre'], $datos['ciudad']);
    }

    if (is_wp_error($ciudad_res)) {
        $advertencias[] = sprintf(
            /* translators: %s: mensaje de error. */
            __('Ciudad: %s', 'cita-comparendo'),
            $ciudad_res->get_error_message()
        );
        $ciudad_res = null;
    }

    $ciudad_nombre = '';

    if (isset($datos['ciudad']) && is_array($datos['ciudad']) && ! empty($datos['ciudad']['nombre'])) {
        $ciudad_nombre = sanitize_text_field((string) $datos['ciudad']['nombre']);
    } elseif (! empty($datos['ciudad_nombre'])) {
        $ciudad_nombre = sanitize_text_field((string) $datos['ciudad_nombre']);
    } elseif (! empty($ciudad_res['term_id'])) {
        $termino_ciudad = get_term((int) $ciudad_res['term_id'], 'ciudad');

        if ($termino_ciudad && ! is_wp_error($termino_ciudad)) {
            $ciudad_nombre = (string) $termino_ciudad->name;
        }
    }

    $localidad_res = null;
    if (! empty($datos['localidad_term_id'])) {
        $localidad_res = array('term_id' => (int) $datos['localidad_term_id'], 'action' => 'updated');
    } elseif (isset($datos['localidad']) && is_array($datos['localidad']) && ! empty($datos['localidad']['nombre'])) {
        $localidad_res = cita_comparendo_upsert_termino('localidad', $datos['localidad']['nombre'], $datos['localidad']);
    }

    if (is_wp_error($localidad_res)) {
        $advertencias[] = sprintf(
            /* translators: %s: mensaje de error. */
            __('Localidad: %s', 'cita-comparendo'),
            $localidad_res->get_error_message()
        );
        $localidad_res = null;
    }

    // Identidad: primero `_sede_id_api`, luego `complemento_iframe`.
    $estados_post = array('publish', 'draft', 'pending', 'private', 'future');
    $post_id = 0;

    $por_api = get_posts(array(
        'post_type'      => 'sede',
        'post_status'    => $estados_post,
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => '_sede_id_api',
        'meta_value'     => $id_api,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ));

    if (! empty($por_api)) {
        $post_id = (int) $por_api[0];

        if (count($por_api) > 1) {
            $advertencias[] = sprintf(
                /* translators: 1: sede, 2: número de registros, 3: id CRM, 4: id actualizado. */
                __('Sede "%1$s": %2$d registros con _sede_id_api "%3$s"; se actualiza el más antiguo (#%4$d).', 'cita-comparendo'),
                $titulo,
                count($por_api),
                $id_api,
                $post_id
            );
        }
    } else {
        $por_iframe = cita_comparendo_buscar_sedes_por_crm($id_api);

        if (! empty($por_iframe)) {
            $post_id = (int) $por_iframe[0];

            if (count($por_iframe) > 1) {
                $advertencias[] = sprintf(
                    /* translators: 1: sede, 2: número de registros, 3: id CRM, 4: id actualizado. */
                    __('Sede "%1$s": %2$d registros con complemento_iframe "%3$s"; se actualiza el más antiguo (#%4$d).', 'cita-comparendo'),
                    $titulo,
                    count($por_iframe),
                    $id_api,
                    $post_id
                );
            }
        }
    }

    $es_nueva = (0 === $post_id);

    $lat = cita_comparendo_normalizar_coordenada(isset($datos['lat']) ? $datos['lat'] : '', 'lat');
    $lng = cita_comparendo_normalizar_coordenada(isset($datos['lng']) ? $datos['lng'] : '', 'lng');
    $direccion = cita_comparendo_limpiar_texto(isset($datos['direccion']) ? $datos['direccion'] : '');
    $incompleta = ('' === $lat || '' === $lng || '' === $direccion);

    $estado_actual = $es_nueva ? '' : (string) get_post_status($post_id);
    $publicada_por_sync = $es_nueva ? '' : (string) get_post_meta($post_id, '_cita_sync_publicada', true);

    // Estado objetivo: null = no tocar el post_status.
    $nuevo_estado = null;
    $marcar_publicada = null;

    if ($es_nueva) {
        $nuevo_estado = $incompleta ? 'draft' : 'publish';
        $marcar_publicada = $incompleta ? '0' : '1';
    } elseif ('publish' === $estado_actual) {
        // Nunca se despublica una sede que ya estaba publicada.
        $nuevo_estado = null;
    } elseif (in_array($estado_actual, array('draft', 'pending', 'private'), true)) {
        // Solo se republica si el propio sync la publicó antes.
        if (! $incompleta && '1' === $publicada_por_sync) {
            $nuevo_estado = 'publish';
            $marcar_publicada = '1';
        }
    }

    $reactivado = false;

    if ($es_nueva) {
        $post_id = wp_insert_post(wp_slash(array(
            'post_title'  => $titulo,
            'post_type'   => 'sede',
            'post_status' => $nuevo_estado,
        )), true);

        if (is_wp_error($post_id)) {
            $resultado['reason'] = 'error';
            $resultado['message'] = sprintf(
                /* translators: 1: sede, 2: mensaje de error. */
                __('Sede "%1$s": %2$s', 'cita-comparendo'),
                $titulo,
                $post_id->get_error_message()
            );

            return $resultado;
        }

        $post_id = (int) $post_id;
        $action = 'created';
    } else {
        $datos_post = array(
            'ID'         => $post_id,
            'post_title' => $titulo,
        );

        if (null !== $nuevo_estado) {
            $datos_post['post_status'] = $nuevo_estado;
        }

        $actualizado = wp_update_post(wp_slash($datos_post), true);

        if (is_wp_error($actualizado)) {
            $resultado['reason'] = 'error';
            $resultado['message'] = sprintf(
                /* translators: 1: sede, 2: mensaje de error. */
                __('Sede "%1$s": %2$s', 'cita-comparendo'),
                $titulo,
                $actualizado->get_error_message()
            );

            return $resultado;
        }

        $action = 'updated';
        $reactivado = ('publish' === $nuevo_estado);
    }

    if (null !== $marcar_publicada) {
        update_post_meta($post_id, '_cita_sync_publicada', $marcar_publicada);
    }

    // Meta de texto.
    foreach (array('direccion', 'horario', 'telefono', 'barrio', 'price_range', 'schema_type') as $clave) {
        $valor = cita_comparendo_limpiar_texto(
            isset($datos[$clave]) ? $datos[$clave] : '',
            'horario' === $clave
        );

        if ('' !== $valor) {
            update_post_meta($post_id, $clave, $valor);
        }
    }

    // URLs.
    foreach (array('url_video', 'url_imagen') as $clave) {
        $valor = isset($datos[$clave]) && is_scalar($datos[$clave])
            ? esc_url_raw(trim((string) $datos[$clave]))
            : '';

        if ('' !== $valor) {
            update_post_meta($post_id, $clave, $valor);
        }
    }

    // WhatsApp (solo dígitos y +).
    $whatsapp = isset($datos['whatsapp']) && is_scalar($datos['whatsapp'])
        ? preg_replace('/[^0-9+]/', '', (string) $datos['whatsapp'])
        : '';

    if ('' !== $whatsapp) {
        update_post_meta($post_id, 'whatsapp', $whatsapp);
    }

    if ('' !== $lat) {
        update_post_meta($post_id, 'lat', $lat);
    }

    if ('' !== $lng) {
        update_post_meta($post_id, 'lng', $lng);
    }

    if (isset($datos['id_empresa']) && is_scalar($datos['id_empresa']) && (int) $datos['id_empresa'] > 0) {
        update_post_meta($post_id, '_sede_id_empresa', (int) $datos['id_empresa']);
    }

    // Identidad en ambos metas (compatibilidad push/pull).
    update_post_meta($post_id, '_sede_id_api', $id_api);
    update_post_meta($post_id, 'complemento_iframe', $id_api);

    // Estado: activo/inactivo explícitos se escriben siempre. Vacío o
    // desconocido solo se resuelve a Activo en sedes nuevas: en una existente
    // no se toca, para no reactivar una sede cerrada a mano.
    $estado_norm = cita_comparendo_estado_normalizado(isset($datos['estado_sede_motor']) ? $datos['estado_sede_motor'] : '');
    $estado_previo = (string) get_post_meta($post_id, 'estado_sede_motor', true);
    $desactivado = false;

    if ('inactivo' === $estado_norm) {
        update_post_meta($post_id, 'estado_sede_motor', 'Inactivo');
        $desactivado = ! cita_comparendo_estado_inactivo($estado_previo);
    } elseif ('activo' === $estado_norm || $es_nueva) {
        update_post_meta($post_id, 'estado_sede_motor', 'Activo');
    }

    // Términos (reemplazan los anteriores).
    if (! empty($ciudad_res['term_id'])) {
        wp_set_object_terms($post_id, array((int) $ciudad_res['term_id']), 'ciudad', false);
    }

    if (! empty($localidad_res['term_id'])) {
        wp_set_object_terms($post_id, array((int) $localidad_res['term_id']), 'localidad', false);
    }

    // Zona por ciudad: término hijo del término-ciudad dentro de `zona`.
    // Si no llega zona no se toca la que ya tenga la sede.
    $zona_res = null;
    $zona = isset($datos['zona']) && is_scalar($datos['zona'])
        ? trim(sanitize_text_field((string) $datos['zona']))
        : '';

    if ('' !== $zona && '' !== $ciudad_nombre) {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($zona) > 60) {
                $zona = mb_substr($zona, 0, 60);
            }
        } elseif (strlen($zona) > 60) {
            $zona = substr($zona, 0, 60);
        }

        $padre_zona = cita_comparendo_upsert_termino('zona', $ciudad_nombre, array(), 0);

        if (is_wp_error($padre_zona)) {
            $advertencias[] = sprintf(
                /* translators: %s: mensaje de error. */
                __('Zona: %s', 'cita-comparendo'),
                $padre_zona->get_error_message()
            );
        } else {
            $zona_res = cita_comparendo_upsert_termino('zona', $zona, array(), (int) $padre_zona['term_id']);

            if (is_wp_error($zona_res)) {
                $advertencias[] = sprintf(
                    /* translators: %s: mensaje de error. */
                    __('Zona: %s', 'cita-comparendo'),
                    $zona_res->get_error_message()
                );
                $zona_res = null;
            } else {
                wp_set_object_terms($post_id, array((int) $zona_res['term_id']), 'zona', false);
            }
        }
    }

    $estado_final = (string) get_post_status($post_id);
    $mensaje = '';

    if ($incompleta) {
        $mensaje = sprintf(
            'publish' === $estado_final
                ? __('Sede "%s" sin dirección o coordenadas: se mantiene publicada.', 'cita-comparendo')
                : __('Sede "%s" sin dirección o coordenadas: queda en borrador.', 'cita-comparendo'),
            $titulo
        );
    }

    return array(
        'success'     => true,
        'action'      => $action,
        'id'          => $post_id,
        'message'     => $mensaje,
        'reason'      => '',
        'estado'      => $estado_final,
        'incompleta'  => $incompleta,
        'reactivated' => $reactivado,
        'deactivated' => $desactivado,
        'warning'     => implode(' ', $advertencias),
        'ciudad'      => $ciudad_res,
        'localidad'   => $localidad_res,
        'zona'        => $zona_res,
    );
}

/**
 * Busca sedes por el ID del CRM (meta complemento_iframe).
 *
 * Devuelve todos los IDs, del más antiguo al más reciente, para poder detectar
 * duplicados. Nunca se busca por slug: el título puede cambiar.
 *
 * @param string $crm_id ID de la sede en el CRM.
 * @return int[] IDs de posts.
 */
function cita_comparendo_buscar_sedes_por_crm($crm_id)
{
    if ('' === $crm_id) {
        return array();
    }

    $ids = get_posts(array(
        'post_type'      => 'sede',
        'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => 'complemento_iframe',
        'meta_value'     => $crm_id,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ));

    return array_map('intval', $ids);
}

/**
 * Aplica una respuesta del CRM sobre el catálogo de sedes.
 *
 * Función pura respecto de la red: recibe el arreglo ya decodificado, lo que
 * permite probarla sin llamar al CRM.
 *
 * @param array $ubicaciones Ubicaciones tal como las devuelve el CRM.
 * @param array $opciones    `limite` => int (0 = sin límite).
 * @return array|WP_Error Estadísticas o error (sin escribir nada).
 */
function cita_comparendo_sync_aplicar($ubicaciones, $opciones = array())
{
    $stats = array(
        'created'     => 0,
        'updated'     => 0,
        'reactivated' => 0,
        'deactivated' => 0,
        'borradores'  => 0,
        'omitidas'    => 0,
        'errors'      => array(),
    );

    if (! is_array($ubicaciones) || empty($ubicaciones)) {
        return new WP_Error(
            'cita_sync_sin_datos',
            __('El CRM devolvió una lista vacía de sedes. No se modificó ninguna sede.', 'cita-comparendo'),
            array('stats' => $stats)
        );
    }

    if (! cita_comparendo_es_lista($ubicaciones)) {
        return new WP_Error(
            'cita_sync_formato',
            __('El CRM devolvió una respuesta con formato inesperado. No se modificó ninguna sede.', 'cita-comparendo'),
            array('stats' => $stats)
        );
    }

    $limite = isset($opciones['limite']) ? max(0, (int) $opciones['limite']) : cita_comparendo_sync_limite();
    $procesadas = 0;
    $agotado = false;

    $registrar_error = function ($mensaje) use (&$stats) {
        if (count($stats['errors']) < 50) {
            $stats['errors'][] = $mensaje;
        }
    };

    foreach ($ubicaciones as $indice => $ubicacion) {
        if ($agotado) {
            break;
        }

        if (! is_array($ubicacion)) {
            $registrar_error(sprintf(
                /* translators: %d: posición del elemento en la respuesta. */
                __('Ubicación %d ignorada: formato inválido.', 'cita-comparendo'),
                (int) $indice + 1
            ));
            continue;
        }

        $nombre_ciudad = cita_comparendo_limpiar_texto(isset($ubicacion['nombre_ubicacion']) ? $ubicacion['nombre_ubicacion'] : '');

        if ('' === $nombre_ciudad) {
            $registrar_error(sprintf(
                /* translators: %d: posición del elemento en la respuesta. */
                __('Ubicación %d ignorada: no tiene nombre_ubicacion.', 'cita-comparendo'),
                (int) $indice + 1
            ));
            continue;
        }

        $ciudad_res = cita_comparendo_upsert_termino('ciudad', $nombre_ciudad, array(
            'latitud'    => isset($ubicacion['latitud_ubicacion']) ? $ubicacion['latitud_ubicacion'] : (isset($ubicacion['lat']) ? $ubicacion['lat'] : ''),
            'longitud'   => isset($ubicacion['longitud_ubicacion']) ? $ubicacion['longitud_ubicacion'] : (isset($ubicacion['lng']) ? $ubicacion['lng'] : ''),
            'nivel_zoom' => isset($ubicacion['nivel_zoom']) ? $ubicacion['nivel_zoom'] : (isset($ubicacion['zoom']) ? $ubicacion['zoom'] : ''),
            'estado'     => isset($ubicacion['estado']) ? $ubicacion['estado'] : '',
        ));

        $term_id = is_wp_error($ciudad_res) ? 0 : (int) $ciudad_res['term_id'];

        if (0 === $term_id) {
            $registrar_error(sprintf(
                /* translators: 1: ciudad, 2: mensaje de error. */
                __('Ciudad "%1$s": %2$s', 'cita-comparendo'),
                $nombre_ciudad,
                is_wp_error($ciudad_res) ? $ciudad_res->get_error_message() : __('no se pudo crear el término.', 'cita-comparendo')
            ));
        }

        $tiendas = isset($ubicacion['tab_ubicacion']) && is_array($ubicacion['tab_ubicacion'])
            ? $ubicacion['tab_ubicacion']
            : array();

        foreach ($tiendas as $tienda) {
            if ($limite > 0 && $procesadas >= $limite) {
                $agotado = true;
                break;
            }

            if (! is_array($tienda)) {
                $registrar_error(sprintf(
                    /* translators: %s: ciudad. */
                    __('Sede ignorada en "%s": formato inválido.', 'cita-comparendo'),
                    $nombre_ciudad
                ));
                continue;
            }

            $titulo = cita_comparendo_limpiar_texto(isset($tienda['titulo']) ? $tienda['titulo'] : '');

            if ('' === $titulo) {
                $registrar_error(sprintf(
                    /* translators: %s: ciudad. */
                    __('Sede ignorada en "%s": no tiene título.', 'cita-comparendo'),
                    $nombre_ciudad
                ));
                continue;
            }

            $crm_id = cita_comparendo_limpiar_texto(isset($tienda['complemento_iframe']) ? $tienda['complemento_iframe'] : '');

            // La identidad es obligatoria: sin complemento_iframe no se escribe
            // nada (ni se crea) para no duplicar sedes al cambiar el título.
            if ('' === $crm_id || ! preg_match('/^[0-9A-Za-z._-]{1,60}$/', $crm_id)) {
                $stats['omitidas']++;
                $registrar_error(sprintf(
                    /* translators: %s: título de la sede. */
                    __('Sede "%s" sin complemento_iframe: no se sincroniza.', 'cita-comparendo'),
                    $titulo
                ));
                continue;
            }

            // Misma función que usa el receptor push (sin duplicar lógica).
            $res = cita_comparendo_upsert_sede(array(
                'id_api'             => $crm_id,
                'titulo'             => $titulo,
                'direccion'          => isset($tienda['direccion']) ? $tienda['direccion'] : '',
                'horario'            => isset($tienda['horario']) ? $tienda['horario'] : '',
                'telefono'           => isset($tienda['telefono']) ? $tienda['telefono'] : '',
                'lat'                => isset($tienda['lat']) ? $tienda['lat'] : (isset($tienda['latitud_tienda']) ? $tienda['latitud_tienda'] : ''),
                'lng'                => isset($tienda['lng']) ? $tienda['lng'] : (isset($tienda['longitud_tienda']) ? $tienda['longitud_tienda'] : ''),
                'estado_sede_motor'  => isset($tienda['estado_sede_motor']) ? $tienda['estado_sede_motor'] : '',
                'price_range'        => isset($tienda['price_range']) ? $tienda['price_range'] : '',
                'schema_type'        => isset($tienda['schema_type']) ? $tienda['schema_type'] : '',
                'ciudad_term_id'     => $term_id ? (int) $term_id : 0,
                'ciudad_nombre'      => $nombre_ciudad,
                'zona'               => isset($tienda['zona']) ? $tienda['zona'] : '',
            ));

            if (! $res['success']) {
                if ('sin_id' === $res['reason']) {
                    $stats['omitidas']++;
                }

                $registrar_error($res['message']);
                continue;
            }

            if ('' !== $res['warning']) {
                $registrar_error($res['warning']);
            }

            if ('draft' === $res['estado']) {
                $stats['borradores']++;
            }

            if ($res['incompleta']) {
                $registrar_error($res['message']);
            } elseif ('created' === $res['action']) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }

            if ($res['reactivated']) {
                $stats['reactivated']++;
            }

            if ($res['deactivated']) {
                $stats['deactivated']++;
            }

            $procesadas++;
        }
    }

    $mensaje = sprintf(
        /* translators: 1: creadas, 2: actualizadas, 3: en borrador. */
        __('Sincronización terminada: %1$d creadas, %2$d actualizadas, %3$d en borrador (revisar en Sedes).', 'cita-comparendo'),
        $stats['created'],
        $stats['updated'],
        $stats['borradores']
    );

    if ($stats['omitidas'] > 0) {
        $mensaje .= ' ' . sprintf(
            /* translators: %d: sedes omitidas por falta de identidad. */
            __('%d omitidas por falta de complemento_iframe.', 'cita-comparendo'),
            $stats['omitidas']
        );
    }

    return array(
        'stats'      => $stats,
        'procesadas' => $procesadas,
        'message'    => $mensaje,
    );
}

/**
 * AJAX: sincroniza el catálogo de sedes.
 *
 * @return void
 */
function cita_comparendo_ajax_sync_sedes()
{
    if (! current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('No tienes permisos para sincronizar sedes.', 'cita-comparendo'),
            'code'    => 'forbidden',
        ), 403);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (! wp_verify_nonce($nonce, 'sync-sedes-nonce')) {
        wp_send_json_error(array(
            'message' => __('La sesión expiró. Recarga la página e inténtalo de nuevo.', 'cita-comparendo'),
            'code'    => 'nonce',
        ), 403);
    }

    $respuesta = cita_comparendo_api_ubicaciones();

    if (is_wp_error($respuesta)) {
        wp_send_json_error(array(
            'message' => $respuesta->get_error_message(),
            'code'    => $respuesta->get_error_code(),
        ), 502);
    }

    $ubicaciones = cita_comparendo_extraer_ubicaciones($respuesta);

    if (is_wp_error($ubicaciones)) {
        wp_send_json_error(array(
            'message' => $ubicaciones->get_error_message(),
            'code'    => $ubicaciones->get_error_code(),
        ), 502);
    }

    if (empty($ubicaciones)) {
        wp_send_json_error(array(
            'message' => __('El CRM devolvió una lista vacía de sedes. No se modificó ninguna sede.', 'cita-comparendo'),
            'code'    => 'cita_sync_sin_datos',
        ), 200);
    }

    $resultado = cita_comparendo_sync_aplicar($ubicaciones);

    if (is_wp_error($resultado)) {
        wp_send_json_error(array(
            'message' => $resultado->get_error_message(),
            'code'    => $resultado->get_error_code(),
            'stats'   => $resultado->get_error_data(),
        ), 200);
    }

    wp_send_json_success($resultado);
}
add_action('wp_ajax_sync_sedes', 'cita_comparendo_ajax_sync_sedes');

/**
 * AJAX: prueba la conexión con el CRM sin escribir nada.
 *
 * @return void
 */
function cita_comparendo_ajax_probar_crm()
{
    if (! current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('No tienes permisos para probar la conexión.', 'cita-comparendo'),
            'code'    => 'forbidden',
        ), 403);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (! wp_verify_nonce($nonce, 'cita-probar-crm')) {
        wp_send_json_error(array(
            'message' => __('La sesión expiró. Recarga la página e inténtalo de nuevo.', 'cita-comparendo'),
            'code'    => 'nonce',
        ), 403);
    }

    $respuesta = cita_comparendo_api_ubicaciones();

    if (is_wp_error($respuesta)) {
        wp_send_json_error(array(
            'message' => $respuesta->get_error_message(),
            'code'    => $respuesta->get_error_code(),
        ), 502);
    }

    $ubicaciones = cita_comparendo_extraer_ubicaciones($respuesta);

    if (is_wp_error($ubicaciones)) {
        wp_send_json_error(array(
            'message' => $ubicaciones->get_error_message(),
            'code'    => $ubicaciones->get_error_code(),
        ), 502);
    }

    $total_sedes = 0;
    foreach ($ubicaciones as $ubicacion) {
        if (is_array($ubicacion) && ! empty($ubicacion['tab_ubicacion']) && is_array($ubicacion['tab_ubicacion'])) {
            $total_sedes += count($ubicacion['tab_ubicacion']);
        }
    }

    wp_send_json_success(array(
        'ubicaciones' => count($ubicaciones),
        'sedes'       => $total_sedes,
        'message'     => sprintf(
            /* translators: 1: número de ubicaciones, 2: número de sedes. */
            __('Conexión correcta: el CRM reporta %1$d ubicaciones y %2$d sedes.', 'cita-comparendo'),
            count($ubicaciones),
            $total_sedes
        ),
    ));
}
add_action('wp_ajax_cita_probar_crm', 'cita_comparendo_ajax_probar_crm');
