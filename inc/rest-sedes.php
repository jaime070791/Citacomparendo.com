<?php
/**
 * Receptor push de sedes desde el CRM (REST: podpixel/v1).
 *
 * El CRM envía un POST con el payload cifrado (AES-256-CBC + HMAC-SHA256):
 * `{iv, data, hmac}` en base64. Este archivo verifica la firma, desencripta y
 * reutiliza cita_comparendo_upsert_sede() para crear/actualizar las sedes.
 *
 * El secreto se lee de PODPIXEL_SHARED_SECRET (wp-config.php) o del ajuste
 * `cita_webhook_secret`. Nunca se registra en logs.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Secreto del webhook. La constante tiene prioridad sobre el ajuste.
 *
 * @return string
 */
function cita_comparendo_webhook_secret()
{
    if (defined('PODPIXEL_SHARED_SECRET') && is_string(PODPIXEL_SHARED_SECRET)) {
        $constante = trim(PODPIXEL_SHARED_SECRET);

        if ('' !== $constante) {
            return $constante;
        }
    }

    $secret = get_option('cita_webhook_secret', '');

    return is_string($secret) ? trim($secret) : '';
}

/**
 * Respuesta estándar del receptor.
 *
 * @param int    $status  Código HTTP.
 * @param string $message Mensaje.
 * @param bool   $success Resultado.
 * @param array  $data    Datos (stats).
 * @return WP_REST_Response
 */
function cita_comparendo_webhook_respuesta($status, $message, $success, $data = array())
{
    $status = (int) $status;

    return new WP_REST_Response(array(
        'Status'  => $status,
        'Message' => $message,
        'Success' => (bool) $success,
        'Data'    => is_array($data) ? $data : array(),
    ), $status);
}

/**
 * Registra las rutas REST.
 *
 * @return void
 */
function cita_comparendo_registrar_rest()
{
    register_rest_route('podpixel/v1', '/receive', array(
        'methods'             => 'POST',
        'callback'            => 'podpixel_receive_and_verify',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('podpixel/v1', '/ping', array(
        'methods'             => 'GET',
        'callback'            => 'cita_comparendo_rest_ping',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'cita_comparendo_registrar_rest');

/**
 * GET /podpixel/v1/ping: salud del sitio (sin datos sensibles).
 *
 * @return WP_REST_Response
 */
function cita_comparendo_rest_ping()
{
    return new WP_REST_Response(array(
        'ok'      => true,
        'site'    => get_bloginfo('name'),
        'url'     => home_url(),
        'version' => defined('CITA_COMPARENDO_VERSION') ? CITA_COMPARENDO_VERSION : '1.0.0',
        'time'    => current_time('c'),
    ), 200);
}

/**
 * POST /podpixel/v1/receive.
 *
 * @param WP_REST_Request $request Petición.
 * @return WP_REST_Response
 */
function podpixel_receive_and_verify(WP_REST_Request $request)
{
    $secret = cita_comparendo_webhook_secret();

    if ('' === $secret) {
        return cita_comparendo_webhook_respuesta(
            500,
            __('El secreto del webhook no está configurado.', 'cita-comparendo'),
            false
        );
    }

    $body = (string) $request->get_body();

    if ('' === trim($body)) {
        return cita_comparendo_webhook_respuesta(400, __('Cuerpo de la petición vacío.', 'cita-comparendo'), false);
    }

    $sobre = json_decode($body, true);

    if (! is_array($sobre)
        || ! isset($sobre['iv'], $sobre['data'], $sobre['hmac'])
        || ! is_string($sobre['iv'])
        || ! is_string($sobre['data'])
        || ! is_string($sobre['hmac'])
    ) {
        return cita_comparendo_webhook_respuesta(400, __('Payload inválido.', 'cita-comparendo'), false);
    }

    $iv = base64_decode($sobre['iv'], true);
    $cifrado = base64_decode($sobre['data'], true);
    $firma = base64_decode($sobre['hmac'], true);

    if (false === $iv || false === $cifrado || false === $firma || 16 !== strlen($iv)) {
        return cita_comparendo_webhook_respuesta(400, __('Codificación inválida.', 'cita-comparendo'), false);
    }

    $firma_esperada = hash_hmac('sha256', $iv . $cifrado, $secret, true);

    if (! hash_equals($firma_esperada, $firma)) {
        return cita_comparendo_webhook_respuesta(401, __('Firma no válida.', 'cita-comparendo'), false);
    }

    $clave = hash('sha256', $secret, true);
    $plano = openssl_decrypt($cifrado, 'aes-256-cbc', $clave, OPENSSL_RAW_DATA, $iv);

    if (false === $plano) {
        return cita_comparendo_webhook_respuesta(400, __('No se pudo desencriptar el payload.', 'cita-comparendo'), false);
    }

    $datos = json_decode($plano, true);

    if (! is_array($datos)) {
        return cita_comparendo_webhook_respuesta(400, __('El payload desencriptado no es JSON válido.', 'cita-comparendo'), false);
    }

    // Anti-replay: fecha_envio ISO dentro de ±1 hora.
    $fecha = isset($datos['fecha_envio']) && is_string($datos['fecha_envio']) ? trim($datos['fecha_envio']) : '';
    $marca = '' !== $fecha ? strtotime($fecha) : false;

    if (false === $marca) {
        return cita_comparendo_webhook_respuesta(400, __('fecha_envio ausente o inválida.', 'cita-comparendo'), false);
    }

    if (abs(time() - $marca) > HOUR_IN_SECONDS) {
        return cita_comparendo_webhook_respuesta(400, __('fecha_envio fuera del rango permitido.', 'cita-comparendo'), false);
    }

    $sedes = isset($datos['sedes']) ? $datos['sedes'] : null;

    // Nunca borrar nada si el payload llega vacío.
    if (! is_array($sedes) || empty($sedes)) {
        return cita_comparendo_webhook_respuesta(400, __('No se recibieron sedes.', 'cita-comparendo'), false);
    }

    $stats = array(
        'sedes'       => array('created' => 0, 'updated' => 0, 'deleted' => 0),
        'ciudades'    => array('created' => 0, 'updated' => 0),
        'localidades' => array('created' => 0, 'updated' => 0),
        'errors'      => array(),
    );

    // Número principal: solo si el sitio aún no tiene uno configurado.
    $numero = isset($datos['numero_principal']) && is_scalar($datos['numero_principal'])
        ? preg_replace('/\D/', '', (string) $datos['numero_principal'])
        : '';

    if ('' !== $numero && '' === trim((string) get_option('cita_whatsapp', ''))) {
        update_option('cita_whatsapp', $numero);
    }

    $ids_payload = array();

    foreach ($sedes as $indice => $item) {
        if (! is_array($item)) {
            $stats['errors'][] = sprintf(
                /* translators: %d: posición de la sede. */
                __('Sede %d ignorada: formato inválido.', 'cita-comparendo'),
                (int) $indice + 1
            );
            continue;
        }

        $sede = isset($item['sede']) && is_array($item['sede']) ? $item['sede'] : $item;

        $id_api = isset($sede['id']) && is_scalar($sede['id']) ? trim((string) $sede['id']) : '';

        // WhatsApp de la sede: el del payload, el principal o su teléfono
        // (mismo criterio que el receptor anterior).
        $whatsapp_sede = isset($sede['whatsapp'])
            ? $sede['whatsapp']
            : ('' !== $numero ? $numero : (isset($sede['telefono']) ? $sede['telefono'] : ''));

        if ('' !== $id_api) {
            $ids_payload[] = $id_api;
        }

        $res = cita_comparendo_upsert_sede(array(
            'id_api'            => $id_api,
            'id_empresa'        => isset($sede['id_empresa']) ? (int) $sede['id_empresa'] : null,
            'titulo'            => isset($sede['nombre']) ? $sede['nombre'] : '',
            'direccion'         => isset($sede['direccion']) ? $sede['direccion'] : '',
            'telefono'          => isset($sede['telefono']) ? $sede['telefono'] : '',
            'horario'           => isset($sede['horario']) ? $sede['horario'] : '',
            'barrio'            => isset($sede['barrio']) ? $sede['barrio'] : '',
            'url_video'         => isset($sede['url_video']) ? $sede['url_video'] : '',
            'url_imagen'        => isset($sede['url_imagen']) ? $sede['url_imagen'] : '',
            'lat'               => isset($sede['latitud']) ? $sede['latitud'] : (isset($sede['lat']) ? $sede['lat'] : ''),
            'lng'               => isset($sede['longitud']) ? $sede['longitud'] : (isset($sede['lng']) ? $sede['lng'] : ''),
            'estado_sede_motor' => isset($sede['estado']) ? $sede['estado'] : '',
            'whatsapp'          => $whatsapp_sede,
            'zona'              => isset($sede['zona']) ? $sede['zona'] : '',
            'ciudad'            => isset($item['ciudad']) && is_array($item['ciudad']) ? $item['ciudad'] : null,
            'localidad'         => isset($item['localidad']) && is_array($item['localidad']) ? $item['localidad'] : null,
        ));

        if (! $res['success']) {
            $stats['errors'][] = $res['message'];
            continue;
        }

        if ('' !== $res['warning']) {
            $stats['errors'][] = $res['warning'];
        }

        if ('' !== $res['message']) {
            $stats['errors'][] = $res['message'];
        }

        if ('created' === $res['action']) {
            $stats['sedes']['created']++;
        } else {
            $stats['sedes']['updated']++;
        }

        if (! empty($res['ciudad']['action']) && isset($stats['ciudades'][$res['ciudad']['action']])) {
            $stats['ciudades'][$res['ciudad']['action']]++;
        }

        if (! empty($res['localidad']['action']) && isset($stats['localidades'][$res['localidad']['action']])) {
            $stats['localidades'][$res['localidad']['action']]++;
        }
    }

    // Borrado por scope: solo sedes con _sede_id_api que no vinieron.
    $scope = isset($datos['scope']) && is_array($datos['scope']) ? $datos['scope'] : array();
    $tipo_scope = isset($scope['tipo']) && is_string($scope['tipo']) ? sanitize_key($scope['tipo']) : '';
    $scope_empresas = isset($scope['empresa_ids']) && is_array($scope['empresa_ids'])
        ? array_map('intval', $scope['empresa_ids'])
        : array();
    $sedes_excluidas = isset($datos['sedes_excluidas']) && is_array($datos['sedes_excluidas'])
        ? array_map('intval', $datos['sedes_excluidas'])
        : array();

    if (in_array($tipo_scope, array('empresa', 'global'), true)) {
        $posts = get_posts(array(
            'post_type'      => 'sede',
            'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ));

        foreach ($posts as $post_id) {
            $post_id = (int) $post_id;
            $api_id = (string) get_post_meta($post_id, '_sede_id_api', true);

            // Las sedes manuales (sin id del CRM) nunca se tocan.
            if ('' === $api_id) {
                continue;
            }

            // Presente en el payload: se conserva.
            if (in_array($api_id, $ids_payload, true)) {
                continue;
            }

            $empresa = (int) get_post_meta($post_id, '_sede_id_empresa', true);

            if ('empresa' === $tipo_scope) {
                if (! in_array($empresa, $scope_empresas, true)) {
                    continue;
                }

                wp_delete_post($post_id, true);
                $stats['sedes']['deleted']++;
                continue;
            }

            // Scope global.
            if (in_array((int) $api_id, $sedes_excluidas, true)) {
                wp_delete_post($post_id, true);
                $stats['sedes']['deleted']++;
                continue;
            }

            // Sedes sin empresa: no se borran a ciegas.
            if ($empresa > 0) {
                wp_delete_post($post_id, true);
                $stats['sedes']['deleted']++;
            }
        }
    }

    do_action('podpixel_after_decrypt', $datos, $stats);

    return cita_comparendo_webhook_respuesta(
        200,
        __('Sedes sincronizadas.', 'cita-comparendo'),
        true,
        $stats
    );
}
