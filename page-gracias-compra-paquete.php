<?php
/**
 * Template Name: Gracias compra paquete
 *
 * Confirmación de la solicitud de compra de un paquete.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

$paquete_param = isset($_GET['paquete']) ? sanitize_text_field(wp_unslash($_GET['paquete'])) : '';

$paquetes = array(
    'pack-plus'   => 'Pack Plus',
    'pack-silver' => 'Pack Silver',
    'pack-gold'   => 'Pack Gold',
);

// Solo se refleja el paquete del contrato; cualquier otro valor es genérico.
$paquete          = isset($paquetes[$paquete_param]) ? $paquetes[$paquete_param] : '';
$paquete_etiqueta = '' !== $paquete ? $paquete : __('Paquete de leads', 'cita-comparendo');

// Mensaje fijo: no se refleja nada de la URL (evita content spoofing).
$mensaje = __('Tu solicitud de compra quedó registrada. Te contactaremos para completar el pago.', 'cita-comparendo');

get_header();
?>

<main id="contenido" class="gracias">

    <header class="page-hero gracias__hero">
        <div class="container">
            <div class="gracias__hero-contenido">
                <span class="gracias__icono" aria-hidden="true">
                    <i class="fa-solid fa-check"></i>
                </span>
                <p class="eyebrow mb-2"><?php esc_html_e('Compra de paquete', 'cita-comparendo'); ?></p>
                <h1><?php esc_html_e('¡Recibimos tu solicitud!', 'cita-comparendo'); ?></h1>
                <p class="gracias__bajada mb-0">
                    <?php esc_html_e('Gracias por confiar en Cita Comparendo. Estamos validando la información para activar tu paquete.', 'cita-comparendo'); ?>
                </p>
            </div>
        </div>
    </header>

    <section class="section-py">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">

                    <article class="gracias__card">
                        <p class="gracias__paquete mb-3">
                            <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                            <span><?php echo esc_html($paquete_etiqueta); ?></span>
                        </p>

                        <p class="gracias__mensaje" role="status"><?php echo esc_html($mensaje); ?></p>

                        <h2 class="gracias__subtitulo"><?php esc_html_e('¿Qué sigue?', 'cita-comparendo'); ?></h2>
                        <ol class="gracias__pasos">
                            <li>
                                <span class="gracias__paso-numero" aria-hidden="true">1</span>
                                <span>
                                    <strong><?php esc_html_e('Validamos el pago.', 'cita-comparendo'); ?></strong>
                                    <?php esc_html_e('Si elegiste pagar en línea, te llevamos a la pasarela segura. Si hiciste la solicitud sin pago, un asesor te enviará el enlace.', 'cita-comparendo'); ?>
                                </span>
                            </li>
                            <li>
                                <span class="gracias__paso-numero" aria-hidden="true">2</span>
                                <span>
                                    <strong><?php esc_html_e('Activamos tu empresa.', 'cita-comparendo'); ?></strong>
                                    <?php esc_html_e('Cargamos tus datos y activamos los leads del paquete en tu cuenta.', 'cita-comparendo'); ?>
                                </span>
                            </li>
                            <li>
                                <span class="gracias__paso-numero" aria-hidden="true">3</span>
                                <span>
                                    <strong><?php esc_html_e('Te acompañamos.', 'cita-comparendo'); ?></strong>
                                    <?php esc_html_e('Un asesor te contacta para explicarte el panel y empezar a recibir usuarios.', 'cita-comparendo'); ?>
                                </span>
                            </li>
                        </ol>

                        <div class="gracias__acciones">
                            <a class="btn btn-primary" href="<?php echo esc_url(home_url('/')); ?>">
                                <?php esc_html_e('Volver al inicio', 'cita-comparendo'); ?>
                            </a>
                            <a class="btn gracias__whatsapp" href="<?php echo esc_url(cita_whatsapp_url('compra de paquete')); ?>"
                                target="_blank" rel="noopener">
                                <i class="fa-brands fa-whatsapp me-1" aria-hidden="true"></i>
                                <?php esc_html_e('Escribir a un asesor', 'cita-comparendo'); ?>
                            </a>
                        </div>
                    </article>

                    <p class="gracias__nota">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <?php esc_html_e('Si no ves el correo de confirmación en unos minutos, revisa la carpeta de spam o escríbenos por WhatsApp.', 'cita-comparendo'); ?>
                    </p>

                </div>
            </div>
        </div>
    </section>

</main>

<?php
get_footer();
