<?php
/**
 * Página de gracias del load: consulta de la cita en el CRM.
 *
 * El navegador llama a WordPress (mismo origen) y WordPress consulta
 * /api/detalles-cita/{citaConfirm} del CRM. Así no dependemos de CORS y la
 * respuesta se normaliza antes de devolverla.
 *
 * Privacidad: el token y los datos personales no se registran en ningún log.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Envía la respuesta JSON con las cabeceras de la página de gracias.
 *
 * @param array $payload Cuerpo de la respuesta.
 * @param int   $status  Código HTTP.
 * @return void
 */
function cita_comparendo_gracias_json($payload, $status = 200)
{
    if (! headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Robots-Tag: noindex, nofollow');
    }

    wp_send_json($payload, $status);
}

/**
 * Lee `citaConfirm` de POST o GET sin sanear (la validación es estricta).
 *
 * @return string|null Token recibido o null si falta o no es un escalar.
 */
function cita_comparendo_cita_token()
{
    foreach (array('POST', 'GET') as $metodo) {
        $fuente = 'POST' === $metodo ? $_POST : $_GET;

        if (! isset($fuente['citaConfirm'])) {
            continue;
        }

        if (! is_scalar($fuente['citaConfirm'])) {
            return null;
        }

        return trim((string) wp_unslash($fuente['citaConfirm']));
    }

    return null;
}

/**
 * Valida el token cifrado del enlace.
 *
 * @param string|null $token Token recibido.
 * @return bool
 */
function cita_comparendo_cita_token_valido($token)
{
    if (! is_string($token)) {
        return false;
    }

    $longitud = strlen($token);

    if ($longitud < 20 || $longitud > 2000) {
        return false;
    }

    return (bool) preg_match('/^[A-Za-z0-9+\/=._-]+$/', $token);
}

/**
 * Extrae y sanea el mensaje de error que devuelve el CRM.
 *
 * @param mixed  $datos      Respuesta decodificada del CRM.
 * @param string $por_defecto Mensaje a usar si no hay uno utilizable.
 * @return string
 */
function cita_comparendo_gracias_mensaje_error($datos, $por_defecto)
{
    if (is_array($datos)) {
        foreach (array('message', 'Message', 'error') as $clave) {
            if (isset($datos[$clave]) && is_scalar($datos[$clave])) {
                $mensaje = sanitize_text_field(wp_strip_all_tags((string) $datos[$clave]));
                if ('' !== trim($mensaje)) {
                    return trim($mensaje);
                }
            }
        }
    }

    return $por_defecto;
}

/**
 * Sanea una cita del CRM y deja solo los campos del contrato.
 *
 * @param array $cita Cita cruda.
 * @return array
 */
function cita_comparendo_sanear_cita(array $cita)
{
    $texto = function ($clave) use ($cita) {
        return isset($cita[$clave]) && is_scalar($cita[$clave])
            ? sanitize_text_field((string) $cita[$clave])
            : '';
    };

    $reserva = $texto('reserva_cita');

    return array(
        'nombre_cliente'    => $texto('nombre_cliente'),
        'apellido_cliente'  => $texto('apellido_cliente'),
        'tipo_doc_cliente'  => $texto('tipo_doc_cliente'),
        'doc_cliente'       => isset($cita['doc_cliente']) && is_scalar($cita['doc_cliente'])
            ? preg_replace('/[^0-9A-Za-z-]/', '', (string) $cita['doc_cliente'])
            : '',
        'telefono_cliente'  => isset($cita['telefono_cliente']) && is_scalar($cita['telefono_cliente'])
            ? preg_replace('/[^0-9+]/', '', (string) $cita['telefono_cliente'])
            : '',
        'email_cliente'     => isset($cita['email_cliente']) && is_scalar($cita['email_cliente'])
            ? sanitize_email((string) $cita['email_cliente'])
            : '',
        'reserva_cita'      => preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $reserva) ? $reserva : '',
        'rango_horario'     => $texto('rango_horario'),
        'nombre_sede'       => $texto('nombre_sede'),
        'direccion_sede'    => $texto('direccion_sede'),
        'id_cita'           => isset($cita['id_cita']) ? absint($cita['id_cita']) : 0,
    );
}

/**
 * AJAX: devuelve los detalles de una cita a partir del token del enlace.
 *
 * Contrato:
 * - éxito: 200 {success:true,data:{citas:[...],cliente:{...},total:int}}
 * - error: 400|404|429|502 {success:false,data:{message}}
 *
 * @return void
 */
function cita_comparendo_ajax_cita_detalles()
{
    $token = cita_comparendo_cita_token();

    if (! cita_comparendo_cita_token_valido($token)) {
        cita_comparendo_gracias_json(array(
            'success' => false,
            'data'    => array('message' => __('Enlace inválido.', 'cita-comparendo')),
        ), 400);
    }

    $limite = cita_comparendo_rate_limit('cita_detalles', 20, 600);

    if (is_wp_error($limite)) {
        cita_comparendo_gracias_json(array(
            'success' => false,
            'data'    => array('message' => $limite->get_error_message()),
        ), 429);
    }

    $generico = __('No pudimos consultar tu cita. Intenta de nuevo en unos minutos.', 'cita-comparendo');

    $respuesta = cita_comparendo_api('/api/detalles-cita/' . rawurlencode($token));

    if (is_wp_error($respuesta)) {
        $error_data = $respuesta->get_error_data();
        $http_code = is_array($error_data) && isset($error_data['http_code']) ? (int) $error_data['http_code'] : 0;

        // El CRM devuelve los errores de negocio con 400/404 y success:false.
        if (in_array($http_code, array(400, 404), true) && isset($error_data['body'])) {
            $datos = json_decode((string) $error_data['body'], true);
            $mensaje = cita_comparendo_gracias_mensaje_error($datos, '');
            if ('' !== $mensaje) {
                cita_comparendo_gracias_json(array(
                    'success' => false,
                    'data'    => array('message' => $mensaje),
                ), $http_code);
            }
        }

        cita_comparendo_gracias_json(array(
            'success' => false,
            'data'    => array('message' => $generico),
        ), 502);
    }

    $exito = isset($respuesta['success']) ? $respuesta['success'] : (isset($respuesta['Success']) ? $respuesta['Success'] : null);

    if (true !== $exito && 1 !== $exito) {
        $mensaje = cita_comparendo_gracias_mensaje_error($respuesta, $generico);

        cita_comparendo_gracias_json(array(
            'success' => false,
            'data'    => array('message' => $mensaje),
        ), 400);
    }

    $crudas = isset($respuesta['data']) ? $respuesta['data'] : (isset($respuesta['Data']) ? $respuesta['Data'] : array());

    // El contrato devuelve una lista; si llega una sola cita, se envuelve.
    if (is_array($crudas) && isset($crudas['nombre_cliente'])) {
        $crudas = array($crudas);
    }

    if (! is_array($crudas)) {
        $crudas = array();
    }

    $citas = array();

    foreach ($crudas as $cita) {
        if (is_array($cita)) {
            $citas[] = cita_comparendo_sanear_cita($cita);
        }
    }

    $primera = ! empty($citas) ? $citas[0] : null;

    $cliente = array(
        'nombre_completo' => $primera
            ? trim($primera['nombre_cliente'] . ' ' . $primera['apellido_cliente'])
            : '',
        'tipo_doc'        => $primera ? $primera['tipo_doc_cliente'] : '',
        'doc'             => $primera ? $primera['doc_cliente'] : '',
        'telefono'        => $primera ? $primera['telefono_cliente'] : '',
        'email'           => $primera ? $primera['email_cliente'] : '',
    );

    cita_comparendo_gracias_json(array(
        'success' => true,
        'data'    => array(
            'citas'   => $citas,
            'cliente' => $cliente,
            'total'   => count($citas),
        ),
    ), 200);
}
add_action('wp_ajax_nopriv_cita_detalles', 'cita_comparendo_ajax_cita_detalles');
add_action('wp_ajax_cita_detalles', 'cita_comparendo_ajax_cita_detalles');
