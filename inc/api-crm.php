<?php
/**
 * Cliente HTTP del CRM y ajustes del tema.
 *
 * Todas las llamadas al CRM pasan por cita_comparendo_api() para tener un
 * único punto de timeout, cabeceras y traducción de errores.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('CITA_COMPARENDO_CRM_URL')) {
    define('CITA_COMPARENDO_CRM_URL', 'https://crm.motordigital.co');
}

/**
 * ¿Es una URL de CRM válida? Solo https y host público.
 *
 * Se usa tanto al guardar el ajuste como al leerlo, para que
 * cita_comparendo_crm_url() nunca devuelva http, localhost ni IPs privadas.
 *
 * @param mixed $url URL a validar.
 * @return bool
 */
function cita_comparendo_url_crm_valida($url)
{
    if (! is_string($url) || '' === trim($url)) {
        return false;
    }

    $partes = wp_parse_url(trim($url));

    if (! is_array($partes) || empty($partes['scheme']) || empty($partes['host'])) {
        return false;
    }

    if ('https' !== strtolower((string) $partes['scheme'])) {
        return false;
    }

    if (! empty($partes['user']) || ! empty($partes['pass'])) {
        return false;
    }

    $host = strtolower((string) $partes['host']);
    $host_ip = trim($host, '[]');

    if (filter_var($host_ip, FILTER_VALIDATE_IP)) {
        return (bool) filter_var(
            $host_ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    if (in_array($host, array('localhost', 'localhost.localdomain'), true)) {
        return false;
    }

    // Host de una sola etiqueta (intranet) o TLD inválido.
    if (false === strpos($host, '.') || ! preg_match('/\.[a-z0-9-]{2,}$/i', $host)) {
        return false;
    }

    return true;
}

/**
 * URL base del CRM, sin barra final. Siempre https y host público.
 *
 * @return string
 */
function cita_comparendo_crm_url()
{
    $url = get_option('cita_crm_url', CITA_COMPARENDO_CRM_URL);
    $url = is_string($url) ? trim($url) : '';

    if (! cita_comparendo_url_crm_valida($url)) {
        $url = CITA_COMPARENDO_CRM_URL;
    }

    /**
     * Permite cambiar la URL del CRM sin tocar ajustes (entornos de prueba).
     *
     * @param string $url URL base.
     */
    $url = (string) apply_filters('cita_comparendo_crm_url', $url);

    if (! cita_comparendo_url_crm_valida($url)) {
        $url = CITA_COMPARENDO_CRM_URL;
    }

    return untrailingslashit($url);
}

/**
 * Slug del formulario que se carga en el modal de la landing.
 *
 * Vacío por defecto: sin configurar, el modal muestra el aviso de que no hay
 * formulario y no se carga ningún iframe (evita un 404 dentro del modal).
 *
 * @return string
 */
function cita_comparendo_formulario_slug()
{
    $slug = get_option('cita_formulario_slug', '');

    return is_string($slug) ? sanitize_title($slug) : '';
}

/**
 * URL a la que redirige el flujo del load (modal) tras agendar.
 *
 * Solo lee `cita_redirect_load`. Vacío = no redirigir: el runtime del load
 * muestra su mensaje de confirmación. La compra de paquetes tiene su propia
 * opción (`cita_redirect_gracias`) y no pasa por este helper.
 *
 * @return string URL absoluta o '' si no hay redirect configurado.
 */
function cita_comparendo_redirect()
{
    $redirect = get_option('cita_redirect_load', '');
    $redirect = is_string($redirect) ? trim($redirect) : '';

    /**
     * Permite cambiar la URL de gracias del load.
     *
     * @param string $redirect URL absoluta o '' para no redirigir.
     */
    $redirect = (string) apply_filters('cita_comparendo_redirect', $redirect);

    return '' === trim($redirect) ? '' : esc_url_raw($redirect);
}

/**
 * Máximo de sedes que procesa una sincronización. 0 = sin límite.
 *
 * @return int
 */
function cita_comparendo_sync_limite()
{
    return max(0, (int) get_option('cita_sync_limite', 0));
}

/**
 * Construye la URL de un endpoint del CRM.
 *
 * @param string $ruta Ruta relativa ("/api/...") o URL absoluta.
 * @return string
 */
function cita_comparendo_api_url($ruta)
{
    $ruta = trim((string) $ruta);

    if (preg_match('#^https?://#i', $ruta)) {
        return $ruta;
    }

    return cita_comparendo_crm_url() . '/' . ltrim($ruta, '/');
}

/**
 * Llama a un endpoint del CRM.
 *
 * @param string     $ruta    Ruta relativa al CRM o URL absoluta.
 * @param array|null $payload Datos a enviar. En GET se agregan como query string.
 * @param string     $metodo  GET, POST, PUT, PATCH o DELETE.
 * @return array|WP_Error Arreglo decodificado de la respuesta o WP_Error.
 */
function cita_comparendo_api($ruta, $payload = null, $metodo = 'GET')
{
    $metodo = strtoupper((string) $metodo);
    $permitidos = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE');

    if (! in_array($metodo, $permitidos, true)) {
        return new WP_Error(
            'cita_crm_metodo',
            __('Método HTTP no soportado para el CRM.', 'cita-comparendo'),
            array('status' => 500)
        );
    }

    $ruta = trim((string) $ruta);
    if ('' === $ruta) {
        return new WP_Error(
            'cita_crm_ruta',
            __('No se indicó la ruta del CRM a consultar.', 'cita-comparendo'),
            array('status' => 500)
        );
    }

    $url = cita_comparendo_api_url($ruta);

    /**
     * Permite ajustar los argumentos de la petición (pruebas, proxies, etc.).
     *
     * @param array  $args   Argumentos de wp_remote_request().
     * @param string $url    URL final.
     * @param string $metodo Método HTTP.
     */
    $args = apply_filters('cita_comparendo_api_args', array(
        'method'             => $metodo,
        'timeout'            => 20,
        'redirection'        => 0,
        'reject_unsafe_urls' => true,
        'headers'            => array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ),
    ), $url, $metodo);

    if (is_array($payload)) {
        if ('GET' === $metodo) {
            $url = add_query_arg(array_map('strval', $payload), $url);
        } else {
            $cuerpo = wp_json_encode($payload);
            if (false === $cuerpo) {
                return new WP_Error(
                    'cita_crm_payload',
                    __('No se pudo preparar la información para el CRM.', 'cita-comparendo'),
                    array('status' => 500)
                );
            }
            $args['body'] = $cuerpo;
        }
    }

    $respuesta = wp_remote_request($url, $args);

    if (is_wp_error($respuesta)) {
        // El detalle técnico va al log (sin PII ni la URL, que puede llevar token).
        error_log('cita_comparendo_api: error de conexión con el CRM: ' . $respuesta->get_error_message());

        return new WP_Error(
            'cita_crm_conexion',
            __('No se pudo conectar con el CRM.', 'cita-comparendo'),
            array('status' => 502)
        );
    }

    $codigo = (int) wp_remote_retrieve_response_code($respuesta);
    $cuerpo = (string) wp_remote_retrieve_body($respuesta);
    $datos = json_decode($cuerpo, true);

    if ($codigo < 200 || $codigo >= 300) {
        return new WP_Error(
            'cita_crm_http',
            sprintf(
                /* translators: %d: código HTTP. */
                __('El CRM respondió con error HTTP %d.', 'cita-comparendo'),
                $codigo
            ),
            array(
                'status'    => 502,
                'http_code' => $codigo,
                // Solo para uso interno (mensajes de negocio de 400/404).
                'body'      => substr($cuerpo, 0, 2000),
            )
        );
    }

    if (! is_array($datos)) {
        return new WP_Error(
            'cita_crm_json',
            __('El CRM devolvió una respuesta que no es JSON válido.', 'cita-comparendo'),
            array('status' => 502)
        );
    }

    return $datos;
}

/**
 * GET /api/obtener-ubicaciones.
 *
 * @return array|WP_Error
 */
function cita_comparendo_api_ubicaciones()
{
    return cita_comparendo_api('/api/obtener-ubicaciones');
}

/**
 * POST /api/verificar-empresa.
 *
 * @param string $tipo_documento Tipo de documento.
 * @param string $documento      Número de documento.
 * @return array|WP_Error
 */
function cita_comparendo_api_verificar_empresa($tipo_documento, $documento)
{
    return cita_comparendo_api('/api/verificar-empresa', array(
        'tipo_documento_empresa' => (string) $tipo_documento,
        'documento_empresa'      => (string) $documento,
    ), 'POST');
}

/**
 * POST /api/registrar-empresa-paquete.
 *
 * @param array $payload Cuerpo que espera el CRM.
 * @return array|WP_Error
 */
function cita_comparendo_api_registrar_paquete(array $payload)
{
    return cita_comparendo_api('/api/registrar-empresa-paquete', $payload, 'POST');
}
