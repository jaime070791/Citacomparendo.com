<?php
/**
 * Compra de paquetes para empresas.
 *
 * Dos endpoints AJAX (públicos, con nonce y rate limit):
 *
 * - verificar_empresa: consulta /api/verificar-empresa del CRM.
 *   Bucket propio: 30 intentos / 10 min por IP.
 * - SendFormToDatabaseAndAPI: guarda la solicitud y llama a
 *   /api/registrar-empresa-paquete. Bucket propio: 5 intentos / 10 min por IP
 *   y honeypot/timestamp como segunda barrera.
 *
 * Contrato de respuesta del formulario: {success, data:{message, redirect}}.
 * El contrato de verificar_empresa mantiene la forma {success, Data} del tema
 * anterior y añade {data:{empresa, message}} para el frontend nuevo.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('CITA_COMPARENDO_PAQUETES_DB_VERSION')) {
    define('CITA_COMPARENDO_PAQUETES_DB_VERSION', '1');
}

/**
 * Nombre de la tabla de compras.
 *
 * @return string
 */
function cita_comparendo_tabla_paquetes()
{
    global $wpdb;

    return $wpdb->prefix . 'compra_paquete';
}

/**
 * Mapa de paquetes del formulario a los ids que espera el CRM.
 *
 * Copiado del tema anterior: no inventar valores.
 *
 * @return array<string, int>
 */
function cita_comparendo_paquetes_ids()
{
    return array(
        'pack-plus'   => 2,
        'pack-silver' => 3,
        'pack-gold'   => 4,
    );
}

/**
 * Crea/actualiza la tabla de compras. Idempotente (dbDelta).
 *
 * @return void
 */
function cita_comparendo_crear_tabla_paquetes()
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $tabla = cita_comparendo_tabla_paquetes();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$tabla} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        paquete_seleccionado varchar(40) NOT NULL DEFAULT '',
        tipo_documento_empresa varchar(20) NOT NULL DEFAULT '',
        documento_empresa varchar(40) NOT NULL DEFAULT '',
        Nombre varchar(200) NOT NULL DEFAULT '',
        Descripcion varchar(500) NOT NULL DEFAULT '',
        Telefono varchar(40) NOT NULL DEFAULT '',
        Correo varchar(200) NOT NULL DEFAULT '',
        estado varchar(20) NOT NULL DEFAULT 'pendiente',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY estado (estado)
    ) {$charset_collate};";

    dbDelta($sql);

    update_option('cita_paquetes_db_version', CITA_COMPARENDO_PAQUETES_DB_VERSION);
}

/**
 * Crea la tabla cuando falta o cambió el esquema.
 *
 * @return void
 */
function cita_comparendo_maybe_crear_tabla_paquetes()
{
    if (CITA_COMPARENDO_PAQUETES_DB_VERSION === get_option('cita_paquetes_db_version')) {
        return;
    }

    cita_comparendo_crear_tabla_paquetes();
}
add_action('admin_init', 'cita_comparendo_maybe_crear_tabla_paquetes');

/**
 * Rangos públicos de Cloudflare (IPv4 e IPv6).
 *
 * @return string[]
 */
function cita_comparendo_cloudflare_rangos()
{
    return array(
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    );
}

/**
 * ¿La IP está dentro de un rango CIDR?
 *
 * @param string $ip   IP.
 * @param string $cidr Rango CIDR.
 * @return bool
 */
function cita_comparendo_ip_en_cidr($ip, $cidr)
{
    $partes = explode('/', (string) $cidr, 2);

    if (2 !== count($partes)) {
        return false;
    }

    $red = @inet_pton($partes[0]);
    $bin = @inet_pton($ip);
    $bits = (int) $partes[1];

    if (false === $red || false === $bin || strlen($red) !== strlen($bin)) {
        return false;
    }

    $bytes = intdiv($bits, 8);
    $resto = $bits % 8;

    if ($bytes > 0 && substr($bin, 0, $bytes) !== substr($red, 0, $bytes)) {
        return false;
    }

    if ($resto > 0) {
        $mascara = (0xFF << (8 - $resto)) & 0xFF;

        if ((ord($bin[$bytes]) & $mascara) !== (ord($red[$bytes]) & $mascara)) {
            return false;
        }
    }

    return true;
}

/**
 * ¿La IP es de Cloudflare?
 *
 * @param string $ip IP a comprobar.
 * @return bool
 */
function cita_comparendo_ip_es_cloudflare($ip)
{
    foreach (cita_comparendo_cloudflare_rangos() as $cidr) {
        if (cita_comparendo_ip_en_cidr($ip, $cidr)) {
            return true;
        }
    }

    return false;
}

/**
 * IP del visitante para el rate limit.
 *
 * Usa REMOTE_ADDR y solo acepta HTTP_CF_CONNECTING_IP si la conexión viene de
 * un rango de Cloudflare (nunca X-Forwarded-For sin proxy de confianza).
 *
 * @return string
 */
function cita_comparendo_ip()
{
    $remota = isset($_SERVER['REMOTE_ADDR']) && is_scalar($_SERVER['REMOTE_ADDR'])
        ? trim((string) wp_unslash($_SERVER['REMOTE_ADDR']))
        : '';
    $remota = filter_var($remota, FILTER_VALIDATE_IP) ? $remota : '';

    if ('' === $remota) {
        return '0.0.0.0';
    }

    if (! empty($_SERVER['HTTP_CF_CONNECTING_IP']) && is_scalar($_SERVER['HTTP_CF_CONNECTING_IP']) && cita_comparendo_ip_es_cloudflare($remota)) {
        $cf = trim((string) wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));

        if (filter_var($cf, FILTER_VALIDATE_IP)) {
            return $cf;
        }
    }

    return $remota;
}

/**
 * Alias histórico de cita_comparendo_ip().
 *
 * @return string
 */
function cita_comparendo_cliente_ip()
{
    return cita_comparendo_ip();
}

/**
 * Lee un valor de $_POST probando varios nombres de campo.
 *
 * @param array<int, string> $campos Nombres posibles, en orden de preferencia.
 * @return string Valor sin slashear, sin sanitizar.
 */
function cita_comparendo_post_texto(array $campos)
{
    foreach ($campos as $campo) {
        if (isset($_POST[$campo]) && is_scalar($_POST[$campo])) {
            return trim((string) wp_unslash($_POST[$campo]));
        }
    }

    return '';
}

/**
 * Rate limit simple por IP y acción (transient).
 *
 * @param string $accion  Acción que se limita.
 * @param int    $limite  Intentos permitidos por ventana.
 * @param int    $ventana Ventana en segundos.
 * @return true|WP_Error
 */
function cita_comparendo_rate_limit($accion, $limite = 5, $ventana = 600)
{
    $clave = 'cita_rl_' . md5($accion . '|' . cita_comparendo_ip());
    $ahora = time();
    $datos = get_transient($clave);

    if (! is_array($datos) || empty($datos['inicio']) || ($ahora - (int) $datos['inicio']) > $ventana) {
        $datos = array(
            'inicio' => $ahora,
            'conteo' => 0,
        );
    }

    $datos['conteo'] = (int) $datos['conteo'] + 1;
    $restante = max(0, $ventana - ($ahora - (int) $datos['inicio']));

    set_transient($clave, $datos, $restante > 0 ? $restante : $ventana);

    if ($datos['conteo'] > $limite) {
        return new WP_Error(
            'cita_rate_limit',
            sprintf(
                /* translators: %d: minutos de espera. */
                __('Demasiados intentos. Inténtalo de nuevo en %d minutos.', 'cita-comparendo'),
                max(1, (int) ceil($restante / 60))
            ),
            array('status' => 429, 'retry_after' => $restante)
        );
    }

    return true;
}

/**
 * Verifica el nonce del formulario de contacto.
 *
 * @return bool
 */
function cita_comparendo_nonce_formulario_valido()
{
    $nonce = cita_comparendo_post_texto(array('nonceContactForm', 'nonce', '_wpnonce'));

    return '' !== $nonce && wp_verify_nonce($nonce, 'nonce-contact-form');
}

/**
 * Normaliza la respuesta del CRM para verificar empresa.
 *
 * @param array|WP_Error $respuesta Respuesta de cita_comparendo_api().
 * @return array{empresa: array|null, message: string}
 */
function cita_comparendo_extraer_empresa($respuesta)
{
    if (! is_array($respuesta)) {
        return array('empresa' => null, 'message' => '');
    }

    $empresa = null;
    if (isset($respuesta['Data']) && is_array($respuesta['Data'])) {
        $empresa = $respuesta['Data'];
    } elseif (isset($respuesta['data']) && is_array($respuesta['data'])) {
        $empresa = $respuesta['data'];
    }

    $message = '';
    if (isset($respuesta['Message']) && is_scalar($respuesta['Message'])) {
        $message = (string) $respuesta['Message'];
    } elseif (isset($respuesta['message']) && is_scalar($respuesta['message'])) {
        $message = (string) $respuesta['message'];
    }

    return array(
        'empresa' => $empresa,
        'message' => sanitize_text_field($message),
    );
}

/**
 * Mensaje genérico para errores internos visibles al visitante.
 *
 * @return string
 */
function cita_comparendo_mensaje_generico()
{
    return __('No pudimos completar la operación. Intenta de nuevo o escríbenos.', 'cita-comparendo');
}

/**
 * Deja en el log el detalle de un error del CRM sin datos personales ni URL.
 *
 * @param string       $contexto Contexto de la operación.
 * @param WP_Error|string $error Error.
 * @return void
 */
function cita_comparendo_log_error($contexto, $error)
{
    $codigo = 'desconocido';
    $http = 0;

    if ($error instanceof WP_Error) {
        $codigo = $error->get_error_code();
        $datos = $error->get_error_data();

        if (is_array($datos) && isset($datos['http_code'])) {
            $http = (int) $datos['http_code'];
        }
    } elseif (is_string($error) && '' !== $error) {
        $codigo = $error;
    }

    error_log(sprintf('cita_comparendo: %s (%s, HTTP %d)', $contexto, $codigo, $http));
}

/**
 * Reduce la empresa del CRM a los campos que pinta la UI.
 *
 * @param array $empresa Datos crudos del CRM.
 * @return array{nombre:string, telefono:string, correo:string, descripcion:string}
 */
function cita_comparendo_empresa_publica(array $empresa)
{
    $valor = function (array $claves) use ($empresa) {
        foreach ($claves as $clave) {
            if (isset($empresa[$clave]) && is_scalar($empresa[$clave])) {
                return (string) $empresa[$clave];
            }
        }

        return '';
    };

    return array(
        'nombre'      => sanitize_text_field($valor(array('Nombre', 'nombre', 'nombre_empresa'))),
        'telefono'    => preg_replace('/[^0-9+]/', '', $valor(array('telefono_empresa', 'Telefono', 'telefono'))),
        'correo'      => sanitize_email($valor(array('correo_empresa', 'Correo', 'email', 'correo'))),
        'descripcion' => sanitize_textarea_field($valor(array('Descripcion', 'descripcion'))),
    );
}

/**
 * AJAX: verifica una empresa por tipo y número de documento.
 *
 * @return void
 */
function cita_comparendo_ajax_verificar_empresa()
{
    if (! cita_comparendo_nonce_formulario_valido()) {
        wp_send_json(array(
            'success' => false,
            'error'   => __('La sesión expiró. Recarga la página e inténtalo de nuevo.', 'cita-comparendo'),
            'data'    => array(
                'message' => __('La sesión expiró. Recarga la página e inténtalo de nuevo.', 'cita-comparendo'),
                'code'    => 'nonce',
            ),
        ), 403);
    }

    $tipo = sanitize_text_field(cita_comparendo_post_texto(array('tipo-documento', 'tipo_documento_empresa', 'tipo_documento')));
    $documento = sanitize_text_field(cita_comparendo_post_texto(array('numero-documento', 'documento_empresa', 'documento')));

    if ('' === $tipo || '' === $documento) {
        $mensaje = __('Ingresa el tipo y el número de documento.', 'cita-comparendo');
        wp_send_json(array(
            'success' => false,
            'error'   => $mensaje,
            'data'    => array('message' => $mensaje, 'code' => 'datos_incompletos'),
        ), 200);
    }

    $limite = cita_comparendo_rate_limit('verificar_empresa', 30, 600);

    if (is_wp_error($limite)) {
        wp_send_json(array(
            'success' => false,
            'error'   => $limite->get_error_message(),
            'data'    => array(
                'message' => $limite->get_error_message(),
                'code'    => $limite->get_error_code(),
            ),
        ), 429);
    }

    $respuesta = cita_comparendo_api_verificar_empresa($tipo, $documento);

    if (is_wp_error($respuesta)) {
        cita_comparendo_log_error('verificar_empresa', $respuesta);
        wp_send_json(array(
            'success' => false,
            'error'   => cita_comparendo_mensaje_generico(),
            'data'    => array(
                'message' => cita_comparendo_mensaje_generico(),
                'code'    => 'crm_error',
            ),
        ), 200);
    }

    $extraida = cita_comparendo_extraer_empresa($respuesta);

    if (empty($extraida['empresa'])) {
        $mensaje = '' !== $extraida['message']
            ? $extraida['message']
            : __('No se encontró una empresa con ese documento.', 'cita-comparendo');

        wp_send_json(array(
            'success' => false,
            'error'   => $mensaje,
            'data'    => array(
                'message'   => $mensaje,
                'code'      => 'empresa_no_encontrada',
                'encontrada' => false,
            ),
        ), 200);
    }

    $mensaje = '' !== $extraida['message']
        ? $extraida['message']
        : __('Empresa encontrada.', 'cita-comparendo');

    $empresa = cita_comparendo_empresa_publica($extraida['empresa']);

    wp_send_json(array(
        'success' => true,
        'Data'    => $empresa,
        'data'    => array(
            'empresa'   => $empresa,
            'message'   => $mensaje,
            'encontrada' => true,
        ),
    ), 200);
}
add_action('wp_ajax_verificar_empresa', 'cita_comparendo_ajax_verificar_empresa');
add_action('wp_ajax_nopriv_verificar_empresa', 'cita_comparendo_ajax_verificar_empresa');

// Compatibilidad con el JS del tema anterior (acción VerificarEmpresaAjax).
add_action('wp_ajax_VerificarEmpresaAjax', 'cita_comparendo_ajax_verificar_empresa');
add_action('wp_ajax_nopriv_VerificarEmpresaAjax', 'cita_comparendo_ajax_verificar_empresa');

/**
 * Longitud de un texto en caracteres (con fallback si no hay mbstring).
 *
 * @param string $texto Texto a medir.
 * @return int
 */
function cita_comparendo_longitud($texto)
{
    return function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
}

/**
 * ¿El envío parece spam? Honeypot + tiempo mínimo/máximo del formulario.
 *
 * Si los campos no vienen (formulario antiguo) no se bloquea; en cuanto el
 * formulario los incluya, la barrera queda activa.
 *
 * @return bool
 */
function cita_comparendo_parece_spam()
{
    $honeypot = cita_comparendo_post_texto(array('cita_hp'));

    if ('' !== $honeypot) {
        return true;
    }

    $marca = cita_comparendo_post_texto(array('cita_ts'));

    if ('' === $marca) {
        return false;
    }

    if (! ctype_digit($marca)) {
        return true;
    }

    $transcurrido = time() - (int) $marca;

    return $transcurrido < 2 || $transcurrido > 2 * HOUR_IN_SECONDS;
}

/**
 * Añade los campos anti-spam al formulario de compra sin tocar la plantilla.
 *
 * @return void
 */
function cita_comparendo_inyectar_antispam()
{
    if (! is_page_template('page-comprar-paquete.php')) {
        return;
    }

    $marca = time();
    ?>
    <script>
    (function () {
        var form = document.getElementById('compraPaqueteForm');
        if (!form || form.querySelector('[name="cita_hp"]')) {
            return;
        }
        var honeypot = document.createElement('input');
        honeypot.type = 'text';
        honeypot.name = 'cita_hp';
        honeypot.value = '';
        honeypot.tabIndex = -1;
        honeypot.autocomplete = 'off';
        honeypot.setAttribute('aria-hidden', 'true');
        honeypot.style.cssText = 'position:absolute;left:-9999px;width:1px;height:1px;opacity:0;';
        var marca = document.createElement('input');
        marca.type = 'hidden';
        marca.name = 'cita_ts';
        marca.value = '<?php echo (int) $marca; ?>';
        form.appendChild(honeypot);
        form.appendChild(marca);
    })();
    </script>
    <?php
}
add_action('wp_footer', 'cita_comparendo_inyectar_antispam');

/**
 * Valida y limpia los datos del formulario de compra.
 *
 * @return array|WP_Error Datos limpios o error con `fields`.
 */
function cita_comparendo_validar_formulario_paquete()
{
    $paquetes = cita_comparendo_paquetes_ids();

    $datos = array(
        'paquete'     => strtolower(sanitize_text_field(cita_comparendo_post_texto(array('paquete', 'paquete_seleccionado')))),
        'tipo'        => sanitize_text_field(cita_comparendo_post_texto(array('tipo-documento', 'tipo_documento_empresa', 'tipo_documento'))),
        'documento'   => sanitize_text_field(cita_comparendo_post_texto(array('numero-documento', 'documento_empresa', 'documento'))),
        'nombre'      => sanitize_text_field(cita_comparendo_post_texto(array('nombre-empresa', 'nombre_empresa', 'Nombre'))),
        'telefono'    => sanitize_text_field(cita_comparendo_post_texto(array('Telefono-empresa', 'telefono_empresa', 'Telefono'))),
        'email'       => sanitize_email(cita_comparendo_post_texto(array('email-empresa', 'correo_empresa', 'Correo', 'email'))),
        'descripcion' => sanitize_textarea_field(cita_comparendo_post_texto(array('descripcion', 'Descripcion'))),
        'terminos'    => strtolower(sanitize_text_field(cita_comparendo_post_texto(array('terminos', 'acepto_terminos')))),
    );

    $errores = array();

    if (! isset($paquetes[$datos['paquete']])) {
        $errores['paquete'] = __('Selecciona un paquete válido.', 'cita-comparendo');
    }

    if (! preg_match('/^[A-Za-z0-9 _.\-]{1,20}$/', $datos['tipo'])) {
        $errores['tipo-documento'] = __('Selecciona un tipo de documento válido.', 'cita-comparendo');
    }

    if (! preg_match('/^[0-9A-Za-z.\-]{4,40}$/', $datos['documento'])) {
        $errores['numero-documento'] = __('Ingresa un número de documento válido (4 a 40 caracteres).', 'cita-comparendo');
    }

    if (cita_comparendo_longitud($datos['nombre']) < 3 || cita_comparendo_longitud($datos['nombre']) > 200) {
        $errores['nombre-empresa'] = __('El nombre de la empresa debe tener entre 3 y 200 caracteres.', 'cita-comparendo');
    }

    if (! preg_match('/^[0-9+()\s.\-]{7,40}$/', $datos['telefono'])) {
        $errores['Telefono-empresa'] = __('Ingresa un teléfono válido.', 'cita-comparendo');
    }

    if (! is_email($datos['email'])) {
        $errores['email-empresa'] = __('Ingresa un correo electrónico válido.', 'cita-comparendo');
    }

    if (cita_comparendo_longitud($datos['descripcion']) < 5 || cita_comparendo_longitud($datos['descripcion']) > 500) {
        $errores['descripcion'] = __('Cuéntanos en qué ciudades operas (5 a 500 caracteres).', 'cita-comparendo');
    }

    if (! in_array($datos['terminos'], array('1', 'on', 'true', 'si', 'sí', 'acepto', 'yes'), true)) {
        $errores['terminos'] = __('Debes aceptar el tratamiento de datos.', 'cita-comparendo');
    }

    if (! empty($errores)) {
        return new WP_Error(
            'cita_form_invalido',
            __('Revisa los datos del formulario.', 'cita-comparendo'),
            array('status' => 200, 'fields' => $errores)
        );
    }

    return $datos;
}

/**
 * AJAX: guarda la solicitud de compra y la envía al CRM.
 *
 * @return void
 */
function cita_comparendo_ajax_enviar_paquete()
{
    if (! cita_comparendo_nonce_formulario_valido()) {
        wp_send_json_error(array(
            'message' => __('La sesión expiró. Recarga la página e inténtalo de nuevo.', 'cita-comparendo'),
            'code'    => 'nonce',
        ), 403);
    }

    if (cita_comparendo_parece_spam()) {
        error_log('cita_comparendo: envío de paquete rechazado por anti-spam.');
        wp_send_json_error(array(
            'message' => cita_comparendo_mensaje_generico(),
            'code'    => 'spam',
        ), 200);
    }

    $limite = cita_comparendo_rate_limit('enviar_paquete', 5, 600);

    if (is_wp_error($limite)) {
        wp_send_json_error(array(
            'message' => $limite->get_error_message(),
            'code'    => $limite->get_error_code(),
        ), 429);
    }

    $datos = cita_comparendo_validar_formulario_paquete();

    if (is_wp_error($datos)) {
        $info = $datos->get_error_data();
        wp_send_json_error(array(
            'message' => $datos->get_error_message(),
            'code'    => $datos->get_error_code(),
            'fields'  => isset($info['fields']) ? $info['fields'] : array(),
        ), 200);
    }

    global $wpdb;

    $tabla = cita_comparendo_tabla_paquetes();
    $insertado = $wpdb->insert(
        $tabla,
        array(
            'paquete_seleccionado'   => $datos['paquete'],
            'tipo_documento_empresa' => $datos['tipo'],
            'documento_empresa'      => $datos['documento'],
            'Nombre'                 => $datos['nombre'],
            'Descripcion'            => $datos['descripcion'],
            'Telefono'               => $datos['telefono'],
            'Correo'                 => $datos['email'],
            'estado'                 => 'pendiente',
            'created_at'             => current_time('mysql'),
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if (false === $insertado) {
        error_log('cita_comparendo_ajax_enviar_paquete: no se pudo insertar la solicitud. ' . $wpdb->last_error);
        wp_send_json_error(array(
            'message' => cita_comparendo_mensaje_generico(),
            'code'    => 'db_error',
        ), 200);
    }

    $fila_id = (int) $wpdb->insert_id;

    $payload = array(
        'data_empresa'         => array(
            'tipo_documento_empresa' => $datos['tipo'],
            'documento_empresa'      => $datos['documento'],
            'Nombre'                 => $datos['nombre'],
            'Descripcion'            => $datos['descripcion'],
            'plan_empresa'           => 'PREPAGO',
            'telefono_empresa'       => $datos['telefono'],
            'correo_empresa'         => $datos['email'],
        ),
        'paquete_seleccionado' => cita_comparendo_paquetes_ids()[$datos['paquete']],
    );

    $respuesta = cita_comparendo_api_registrar_paquete($payload);

    if (is_wp_error($respuesta)) {
        cita_comparendo_log_error('registrar_empresa_paquete', $respuesta);

        $wpdb->update(
            $tabla,
            array('estado' => 'error'),
            array('id' => $fila_id),
            array('%s'),
            array('%d')
        );

        wp_send_json_error(array(
            'message' => cita_comparendo_mensaje_generico(),
            'code'    => 'crm_error',
        ), 200);
    }

    $wpdb->update(
        $tabla,
        array('estado' => 'enviado'),
        array('id' => $fila_id),
        array('%s'),
        array('%d')
    );

    $mensaje = __('¡Listo! Recibimos tu solicitud y te contactaremos para completar la compra del paquete.', 'cita-comparendo');
    if (isset($respuesta['Message']) && is_scalar($respuesta['Message']) && '' !== trim((string) $respuesta['Message'])) {
        $mensaje = sanitize_text_field((string) $respuesta['Message']);
    }

    $wompi = '';
    if (isset($respuesta['Data']['wompi_redirect']) && is_string($respuesta['Data']['wompi_redirect'])) {
        $wompi = esc_url_raw($respuesta['Data']['wompi_redirect']);
    }

    // La compra tiene su propia página de gracias (independiente del load).
    $redirect_paquete = trim((string) get_option('cita_redirect_gracias', ''));
    $redirect_paquete = '' !== $redirect_paquete
        ? esc_url_raw($redirect_paquete)
        : home_url('/gracias-compra-paquete/');

    wp_send_json_success(array(
        'message'        => $mensaje,
        'redirect'       => $redirect_paquete,
        'wompi_redirect' => $wompi,
        'estado'         => 'enviado',
    ));
}
add_action('wp_ajax_SendFormToDatabaseAndAPI', 'cita_comparendo_ajax_enviar_paquete');
add_action('wp_ajax_nopriv_SendFormToDatabaseAndAPI', 'cita_comparendo_ajax_enviar_paquete');
