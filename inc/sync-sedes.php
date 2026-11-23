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
function cita_comparendo_upsert_ciudad(array $ubicacion, $nombre)
{
    $nombre = sanitize_text_field($nombre);

    if ('' === $nombre) {
        return new WP_Error('cita_sync_ciudad', __('La ciudad no tiene nombre.', 'cita-comparendo'));
    }

    $existente = term_exists($nombre, 'ciudad');
    $term_id = 0;

    if (is_array($existente) && isset($existente['term_id'])) {
        $term_id = (int) $existente['term_id'];
    } elseif (is_int($existente)) {
        $term_id = $existente;
    } elseif (is_object($existente) && isset($existente->term_id)) {
        $term_id = (int) $existente->term_id;
    } else {
        $creado = wp_insert_term($nombre, 'ciudad', array('slug' => sanitize_title($nombre)));

        if (is_wp_error($creado)) {
            // Ciudad con el mismo slug pero otro nombre (tildes, mayúsculas):
            // reutilizamos el término existente en vez de perder la asignación.
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
        }
    }

    $lat = cita_comparendo_normalizar_coordenada(
        isset($ubicacion['latitud_ubicacion']) ? $ubicacion['latitud_ubicacion'] : (isset($ubicacion['lat']) ? $ubicacion['lat'] : ''),
        'lat'
    );
    $lng = cita_comparendo_normalizar_coordenada(
        isset($ubicacion['longitud_ubicacion']) ? $ubicacion['longitud_ubicacion'] : (isset($ubicacion['lng']) ? $ubicacion['lng'] : ''),
        'lng'
    );

    if ('' !== $lat) {
        update_term_meta($term_id, 'latitud', $lat);
    }
    if ('' !== $lng) {
        update_term_meta($term_id, 'longitud', $lng);
    }

    $zoom = null;
    if (isset($ubicacion['nivel_zoom']) && '' !== $ubicacion['nivel_zoom']) {
        $zoom = (int) $ubicacion['nivel_zoom'];
    } elseif (isset($ubicacion['zoom']) && '' !== $ubicacion['zoom']) {
        $zoom = (int) $ubicacion['zoom'];
    }
    if (null !== $zoom && $zoom > 0) {
        update_term_meta($term_id, 'nivel_zoom', (string) max(1, min(20, $zoom)));
    }

    $estado = cita_comparendo_limpiar_texto(isset($ubicacion['estado']) ? $ubicacion['estado'] : '');
    if ('' !== $estado) {
        update_term_meta($term_id, 'estado', $estado);
    }

    return $term_id;
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

        $term_id = cita_comparendo_upsert_ciudad($ubicacion, $nombre_ciudad);
        if (is_wp_error($term_id)) {
            $registrar_error(sprintf(
                /* translators: 1: ciudad, 2: mensaje de error. */
                __('Ciudad "%1$s": %2$s', 'cita-comparendo'),
                $nombre_ciudad,
                $term_id->get_error_message()
            ));
            $term_id = 0;
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

            $coincidencias = cita_comparendo_buscar_sedes_por_crm($crm_id);
            $existente = ! empty($coincidencias) ? (int) $coincidencias[0] : 0;
            $es_nueva = (0 === $existente);

            if (count($coincidencias) > 1) {
                $registrar_error(sprintf(
                    /* translators: 1: sede, 2: número de registros, 3: id CRM, 4: id del post actualizado. */
                    __('Sede "%1$s": %2$d registros con complemento_iframe "%3$s"; se actualiza la más antigua (#%4$d).', 'cita-comparendo'),
                    $titulo,
                    count($coincidencias),
                    $crm_id,
                    $existente
                ));
            }

            // Una sede sin coordenadas válidas o sin dirección queda incompleta.
            $lat = cita_comparendo_normalizar_coordenada(
                isset($tienda['lat']) ? $tienda['lat'] : (isset($tienda['latitud_tienda']) ? $tienda['latitud_tienda'] : ''),
                'lat'
            );
            $lng = cita_comparendo_normalizar_coordenada(
                isset($tienda['lng']) ? $tienda['lng'] : (isset($tienda['longitud_tienda']) ? $tienda['longitud_tienda'] : ''),
                'lng'
            );
            $direccion = cita_comparendo_limpiar_texto(isset($tienda['direccion']) ? $tienda['direccion'] : '');
            $incompleta = ('' === $lat || '' === $lng || '' === $direccion);

            $estado_actual = $es_nueva ? '' : (string) get_post_status($existente);
            $publicada_por_sync = $es_nueva ? '' : (string) get_post_meta($existente, '_cita_sync_publicada', true);

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

            if ($es_nueva) {
                $post_id = wp_insert_post(wp_slash(array(
                    'post_title'  => $titulo,
                    'post_type'   => 'sede',
                    'post_status' => $nuevo_estado,
                )), true);

                if (is_wp_error($post_id)) {
                    $registrar_error(sprintf(
                        /* translators: 1: sede, 2: mensaje de error. */
                        __('Sede "%1$s": %2$s', 'cita-comparendo'),
                        $titulo,
                        $post_id->get_error_message()
                    ));
                    continue;
                }

                $post_id = (int) $post_id;

                if (null !== $marcar_publicada) {
                    update_post_meta($post_id, '_cita_sync_publicada', $marcar_publicada);
                }
            } else {
                $post_id = $existente;

                $datos_post = array(
                    'ID'         => $post_id,
                    'post_title' => $titulo,
                );

                if (null !== $nuevo_estado) {
                    $datos_post['post_status'] = $nuevo_estado;
                }

                $actualizado = wp_update_post(wp_slash($datos_post), true);

                if (is_wp_error($actualizado)) {
                    $registrar_error(sprintf(
                        /* translators: 1: sede, 2: mensaje de error. */
                        __('Sede "%1$s": %2$s', 'cita-comparendo'),
                        $titulo,
                        $actualizado->get_error_message()
                    ));
                    continue;
                }

                if (null !== $marcar_publicada) {
                    update_post_meta($post_id, '_cita_sync_publicada', $marcar_publicada);
                }

                if ('publish' === $nuevo_estado) {
                    $stats['reactivated']++;
                }
            }

            $estado_final = (string) get_post_status($post_id);

            if ('draft' === $estado_final) {
                $stats['borradores']++;
            }

            if ($incompleta) {
                $registrar_error(sprintf(
                    'publish' === $estado_final
                        ? __('Sede "%s" sin dirección o coordenadas: se mantiene publicada.', 'cita-comparendo')
                        : __('Sede "%s" sin dirección o coordenadas: queda en borrador.', 'cita-comparendo'),
                    $titulo
                ));
            } elseif ($es_nueva) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }

            if ($term_id) {
                wp_set_object_terms($post_id, array($term_id), 'ciudad', false);
            }

            $meta_texto = array(
                'direccion'          => 'direccion',
                'horario'            => 'horario',
                'telefono'           => 'telefono',
                'complemento_iframe' => 'complemento_iframe',
                'price_range'        => 'price_range',
                'schema_type'        => 'schema_type',
            );

            foreach ($meta_texto as $campo => $clave_api) {
                $valor = cita_comparendo_limpiar_texto(
                    isset($tienda[$clave_api]) ? $tienda[$clave_api] : '',
                    'horario' === $campo
                );

                if ('' !== $valor) {
                    update_post_meta($post_id, $campo, $valor);
                }
            }

            if ('' !== $lat) {
                update_post_meta($post_id, 'lat', $lat);
            }
            if ('' !== $lng) {
                update_post_meta($post_id, 'lng', $lng);
            }

            // Estado: solo se escribe si el CRM manda un valor reconocible.
            // Desconocido o vacío deja intacto lo editado en WordPress.
            $estado_api = cita_comparendo_limpiar_texto(isset($tienda['estado_sede_motor']) ? $tienda['estado_sede_motor'] : '');
            $estado_norm = cita_comparendo_estado_normalizado($estado_api);
            $estado_previo = (string) get_post_meta($post_id, 'estado_sede_motor', true);

            if ('inactivo' === $estado_norm) {
                update_post_meta($post_id, 'estado_sede_motor', 'Inactivo');

                if (! cita_comparendo_estado_inactivo($estado_previo)) {
                    $stats['deactivated']++;
                }
            } elseif ('activo' === $estado_norm) {
                update_post_meta($post_id, 'estado_sede_motor', 'Activo');
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
