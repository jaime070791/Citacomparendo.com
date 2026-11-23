<?php
/**
 * Template Name: Gracias registro
 *
 * Confirmación del registro de una cita. El token `citaConfirm` de la URL se
 * consulta por AJAX (action `cita_detalles`) y el resumen se pinta con JS:
 * el HTML inicial no contiene datos personales.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

nocache_headers();

add_filter('wp_robots', function ($robots) {
    $robots['noindex']  = true;
    $robots['nofollow'] = true;

    return $robots;
});

$whatsapp_url = function_exists('cita_whatsapp_url')
    ? cita_whatsapp_url()
    : 'https://wa.me/573168721085';

wp_enqueue_script(
    'cita-gracias',
    CITA_COMPARENDO_JS_URI . 'gracias.js',
    array('cita-main'),
    cita_comparendo_asset_version('/assets/js/gracias.js'),
    true
);

wp_localize_script('cita-gracias', 'CITA_GRACIAS', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'i18n'    => array(
        'errorTitulo'   => __('No encontramos tu cita', 'cita-comparendo'),
        'errorGeneral'  => __('No encontramos tu cita. Verifica el enlace o escríbenos.', 'cita-comparendo'),
        'errorEnlace'   => __('Este enlace no incluye los datos de tu cita. Ábrelo de nuevo desde la confirmación o escríbenos.', 'cita-comparendo'),
        'errorSinCitas' => __('No encontramos citas asociadas a este enlace. Verifica que sea el enlace de tu confirmación.', 'cita-comparendo'),
        'errorRed'      => __('No pudimos consultar tu cita. Revisa tu conexión e inténtalo de nuevo.', 'cita-comparendo'),
        'errorServidor' => __('El servicio de citas no está disponible en este momento. Inténtalo de nuevo en unos minutos.', 'cita-comparendo'),
        'errorLimite'   => __('Hiciste varias consultas seguidas. Espera un momento y vuelve a intentarlo.', 'cita-comparendo'),
        'datosTitulo'   => __('Tus datos', 'cita-comparendo'),
        'citaTitulo'    => __('Tu cita', 'cita-comparendo'),
        'citasTitulo'   => __('Tus citas', 'cita-comparendo'),
        'cita'          => __('Cita', 'cita-comparendo'),
        'refCita'       => __('Cita #%s', 'cita-comparendo'),
        'nombre'        => __('Nombre completo', 'cita-comparendo'),
        'tipoDocumento' => __('Tipo de documento', 'cita-comparendo'),
        'documento'     => __('Número de documento', 'cita-comparendo'),
        'telefono'      => __('Teléfono', 'cita-comparendo'),
        'correo'        => __('Correo electrónico', 'cita-comparendo'),
        'direccion'     => __('Dirección', 'cita-comparendo'),
        'fecha'         => __('Fecha', 'cita-comparendo'),
        'horario'       => __('Franja horaria', 'cita-comparendo'),
        'noDisponible'  => __('No disponible', 'cita-comparendo'),
        'sedePendiente' => __('Sede por confirmar', 'cita-comparendo'),
    ),
));

get_header();
?>

<main id="contenido" class="gracias gracias-registro">

    <header class="page-hero gracias__hero gracias-registro__hero">
        <div class="container">
            <div class="gracias__hero-contenido">
                <span class="gracias__icono" aria-hidden="true">
                    <i class="fa-solid fa-check"></i>
                </span>
                <p class="eyebrow mb-2"><?php esc_html_e('Registro de cita', 'cita-comparendo'); ?></p>
                <h1 id="gr-titulo" tabindex="-1"><?php esc_html_e('¡Tu cita quedó agendada!', 'cita-comparendo'); ?></h1>
                <p class="gracias__bajada mb-0">
                    <?php esc_html_e('Te esperamos en la sede que elegiste.', 'cita-comparendo'); ?>
                </p>
            </div>
        </div>
    </header>

    <section class="section-py gracias-registro__seccion">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10 col-xl-8">

                    <div id="gr-estado" class="gr-estado" role="status" aria-live="polite" aria-atomic="true">
                        <div class="gr-skel" aria-hidden="true">
                            <span class="gr-skel__linea gr-skel__linea--titulo"></span>
                            <span class="gr-skel__linea"></span>
                            <span class="gr-skel__linea gr-skel__linea--corta"></span>
                        </div>
                        <p class="gr-estado__texto"><?php esc_html_e('Consultando tu cita…', 'cita-comparendo'); ?></p>
                    </div>

                    <div id="gr-resumen" class="gr-resumen" hidden></div>

                    <noscript>
                        <div class="gr-aviso">
                            <span class="gr-aviso__icono" aria-hidden="true">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </span>
                            <div class="gr-aviso__contenido">
                                <p class="gr-aviso__texto mb-0">
                                    <?php esc_html_e('Activa JavaScript para ver los datos de tu cita. También puedes escribirnos por WhatsApp.', 'cita-comparendo'); ?>
                                </p>
                            </div>
                        </div>
                    </noscript>

                    <div class="gracias__acciones gr-acciones">
                        <button type="button" class="btn btn-warning" id="gr-imprimir">
                            <i class="fa-solid fa-print me-1" aria-hidden="true"></i>
                            <?php esc_html_e('Imprimir / Guardar PDF', 'cita-comparendo'); ?>
                        </button>
                        <a class="btn gracias__whatsapp" href="<?php echo esc_url($whatsapp_url); ?>"
                            target="_blank" rel="noopener">
                            <i class="fa-brands fa-whatsapp me-1" aria-hidden="true"></i>
                            <?php esc_html_e('Escríbenos por WhatsApp', 'cita-comparendo'); ?>
                        </a>
                        <a class="gr-acciones__volver" href="<?php echo esc_url(home_url('/')); ?>">
                            <i class="fa-solid fa-house" aria-hidden="true"></i>
                            <?php esc_html_e('Volver al inicio', 'cita-comparendo'); ?>
                        </a>
                    </div>

                    <p class="gracias__nota">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <?php esc_html_e('Llega 10 minutos antes con tu documento de identidad. Si necesitas cambiar la cita, escríbenos por WhatsApp.', 'cita-comparendo'); ?>
                    </p>

                </div>
            </div>
        </div>
    </section>

</main>

<?php
get_footer();
