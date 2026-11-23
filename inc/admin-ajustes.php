<?php
/**
 * Ajustes → Cita Comparendo.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registra la página y las opciones.
 *
 * @return void
 */
function cita_comparendo_registrar_ajustes()
{
    register_setting('cita_comparendo_ajustes', 'cita_crm_url', array(
        'type'              => 'string',
        'sanitize_callback' => 'cita_comparendo_sanitizar_url_crm',
        'default'           => CITA_COMPARENDO_CRM_URL,
        'show_in_rest'      => false,
    ));

    register_setting('cita_comparendo_ajustes', 'cita_formulario_slug', array(
        'type'              => 'string',
        'sanitize_callback' => 'cita_comparendo_sanitizar_slug',
        'default'           => '',
        'show_in_rest'      => false,
    ));

    register_setting('cita_comparendo_ajustes', 'cita_redirect_load', array(
        'type'              => 'string',
        'sanitize_callback' => 'cita_comparendo_sanitizar_url',
        'default'           => '',
        'show_in_rest'      => false,
    ));

    register_setting('cita_comparendo_ajustes', 'cita_redirect_gracias', array(
        'type'              => 'string',
        'sanitize_callback' => 'cita_comparendo_sanitizar_url',
        'default'           => '',
        'show_in_rest'      => false,
    ));

    register_setting('cita_comparendo_ajustes', 'cita_sync_limite', array(
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 0,
        'show_in_rest'      => false,
    ));

    register_setting('cita_comparendo_ajustes', 'cita_whatsapp', array(
        'type'              => 'string',
        'sanitize_callback' => 'cita_comparendo_sanitizar_whatsapp',
        'default'           => '',
        'show_in_rest'      => false,
    ));

    register_setting('cita_comparendo_ajustes', 'cita_webhook_secret', array(
        'type'              => 'string',
        'sanitize_callback' => 'cita_comparendo_sanitizar_secreto',
        'default'           => '',
        'show_in_rest'      => false,
    ));
}
add_action('admin_init', 'cita_comparendo_registrar_ajustes');

/**
 * Sanitiza la URL del CRM (sin barra final): exige https y host público.
 *
 * @param mixed $valor Valor enviado.
 * @return string URL válida o el default del tema.
 */
function cita_comparendo_sanitizar_url_crm($valor)
{
    $valor = is_string($valor) ? trim($valor) : '';

    if ('' === $valor) {
        return CITA_COMPARENDO_CRM_URL;
    }

    $url = untrailingslashit(esc_url_raw($valor));

    return cita_comparendo_url_crm_valida($url) ? $url : CITA_COMPARENDO_CRM_URL;
}

/**
 * Sanitiza el número de WhatsApp (dígitos) o una URL de wa.me.
 *
 * @param mixed $valor Valor enviado.
 * @return string Vacío para usar el número por defecto del tema.
 */
function cita_comparendo_sanitizar_whatsapp($valor)
{
    $valor = is_string($valor) ? trim($valor) : '';

    if ('' === $valor) {
        return '';
    }

    if (preg_match('#^https?://#i', $valor)) {
        return esc_url_raw($valor);
    }

    $digitos = preg_replace('/\D/', '', $valor);

    return '' === $digitos ? '' : $digitos;
}

/**
 * Sanitiza el secreto del webhook de sedes.
 *
 * @param mixed $valor Valor enviado.
 * @return string
 */
function cita_comparendo_sanitizar_secreto($valor)
{
    return is_string($valor) ? sanitize_text_field(trim($valor)) : '';
}

/**
 * Sanitiza una URL opcional de ajustes. Vacío es válido.
 *
 * @param mixed $valor Valor enviado.
 * @return string
 */
function cita_comparendo_sanitizar_url($valor)
{
    $valor = is_string($valor) ? trim($valor) : '';

    return '' === $valor ? '' : esc_url_raw($valor);
}

/**
 * Sanitiza el slug del formulario. Vacío es válido (sin formulario).
 *
 * @param mixed $valor Valor enviado.
 * @return string
 */
function cita_comparendo_sanitizar_slug($valor)
{
    return is_string($valor) ? sanitize_title($valor) : '';
}

/**
 * Añade la página a Ajustes.
 *
 * @return void
 */
function cita_comparendo_add_ajustes_page()
{
    add_options_page(
        __('Cita Comparendo', 'cita-comparendo'),
        __('Cita Comparendo', 'cita-comparendo'),
        'manage_options',
        'cita-comparendo-ajustes',
        'cita_comparendo_render_ajustes_page'
    );
}
add_action('admin_menu', 'cita_comparendo_add_ajustes_page');

/**
 * Pinta la página de ajustes.
 *
 * @return void
 */
function cita_comparendo_render_ajustes_page()
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para ver esta página.', 'cita-comparendo'));
    }

    $sync_url = admin_url('edit.php?post_type=sede&page=cita-sincronizar-sedes');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Cita Comparendo', 'cita-comparendo'); ?></h1>

        <form action="<?php echo esc_url(admin_url('options.php')); ?>" method="post">
            <?php settings_fields('cita_comparendo_ajustes'); ?>

            <h2 class="title"><?php esc_html_e('Formulario del load', 'cita-comparendo'); ?></h2>
            <p class="description">
                <?php esc_html_e('Modal de agendamiento que abre el botón del load en la landing.', 'cita-comparendo'); ?>
            </p>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="cita-formulario-slug"><?php esc_html_e('Slug del formulario (modal)', 'cita-comparendo'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cita-formulario-slug" name="cita_formulario_slug" class="regular-text"
                                value="<?php echo esc_attr(cita_comparendo_formulario_slug()); ?>"
                                placeholder="curso-comparendo">
                            <p class="description">
                                <?php esc_html_e('Slug de la página que se carga en el modal del load. Vacío = el modal muestra el aviso de que no hay formulario configurado (no se carga ningún iframe).', 'cita-comparendo'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cita-redirect-load"><?php esc_html_e('URL de gracias del load', 'cita-comparendo'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="cita-redirect-load" name="cita_redirect_load" class="regular-text"
                                value="<?php echo esc_attr(get_option('cita_redirect_load', '')); ?>"
                                placeholder="<?php echo esc_attr(home_url('/gracias/')); ?>">
                            <p class="description">
                                <?php esc_html_e('Vacío = no redirigir; el formulario muestra su mensaje de confirmación.', 'cita-comparendo'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2 class="title"><?php esc_html_e('Compra de paquetes', 'cita-comparendo'); ?></h2>
            <p class="description">
                <?php esc_html_e('Formulario de compra para empresas (página configurada por su slug).', 'cita-comparendo'); ?>
            </p>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="cita-redirect-gracias"><?php esc_html_e('URL de gracias de compra de paquetes', 'cita-comparendo'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="cita-redirect-gracias" name="cita_redirect_gracias" class="regular-text"
                                value="<?php echo esc_attr(get_option('cita_redirect_gracias', '')); ?>"
                                placeholder="<?php echo esc_attr(home_url('/gracias-compra-paquete/')); ?>">
                            <p class="description">
                                <?php esc_html_e('A dónde se redirige al terminar la compra de un paquete. Vacío = /gracias-compra-paquete/.', 'cita-comparendo'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2 class="title"><?php esc_html_e('Contacto', 'cita-comparendo'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="cita-whatsapp"><?php esc_html_e('Número de WhatsApp', 'cita-comparendo'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cita-whatsapp" name="cita_whatsapp" class="regular-text"
                                value="<?php echo esc_attr((string) get_option('cita_whatsapp', '')); ?>"
                                placeholder="573168721085">
                            <p class="description">
                                <?php esc_html_e('Número con indicativo de país (por ejemplo 573168721085) o una URL de wa.me. Vacío = 573168721085.', 'cita-comparendo'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2 class="title"><?php esc_html_e('Conexión con el CRM', 'cita-comparendo'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="cita-crm-url"><?php esc_html_e('URL del CRM', 'cita-comparendo'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="cita-crm-url" name="cita_crm_url" class="regular-text"
                                value="<?php echo esc_attr(cita_comparendo_crm_url()); ?>" placeholder="<?php echo esc_attr(CITA_COMPARENDO_CRM_URL); ?>">
                            <p class="description">
                                <?php esc_html_e('Base de los endpoints /api/obtener-ubicaciones, /api/verificar-empresa y /api/registrar-empresa-paquete. Sin barra final.', 'cita-comparendo'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cita-sync-limite"><?php esc_html_e('Límite de sincronización', 'cita-comparendo'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cita-sync-limite" name="cita_sync_limite" class="small-text" min="0" step="1"
                                value="<?php echo esc_attr((string) cita_comparendo_sync_limite()); ?>">
                            <p class="description">
                                <?php esc_html_e('Máximo de sedes que procesa cada sincronización. 0 = sin límite.', 'cita-comparendo'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2 class="title"><?php esc_html_e('Webhook de sedes (push)', 'cita-comparendo'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="cita-webhook-secret"><?php esc_html_e('Secreto del webhook de sedes', 'cita-comparendo'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="cita-webhook-secret" name="cita_webhook_secret" class="regular-text code"
                                value="<?php echo esc_attr((string) get_option('cita_webhook_secret', '')); ?>"
                                autocomplete="new-password">
                            <p class="description">
                                <?php esc_html_e('Debe coincidir con el secreto del sitio en el CRM (dashboard/wordpress) o con encryption_key de tb_config.', 'cita-comparendo'); ?>
                            </p>
                            <?php if (defined('PODPIXEL_SHARED_SECRET')) : ?>
                                <p class="description">
                                    <strong><?php esc_html_e('PODPIXEL_SHARED_SECRET está definida en wp-config.php: el webhook usa esa constante y este campo se ignora.', 'cita-comparendo'); ?></strong>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('URL del endpoint', 'cita-comparendo'); ?></th>
                        <td>
                            <input type="text" readonly class="large-text code"
                                value="<?php echo esc_attr(rest_url('podpixel/v1/receive')); ?>"
                                onclick="this.select();">
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: URL de ping. */
                                    esc_html__('Cópiala en el CRM (dashboard/wordpress) como URL del sitio. Ping: %s', 'cita-comparendo'),
                                    '<code>' . esc_html(rest_url('podpixel/v1/ping')) . '</code>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button(); ?>
        </form>

        <hr>

        <p>
            <a class="button" href="<?php echo esc_url($sync_url); ?>">
                <?php esc_html_e('Ir a Sincronizar sedes', 'cita-comparendo'); ?>
            </a>
        </p>
    </div>
    <?php
}
