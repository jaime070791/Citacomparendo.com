<?php
/**
 * 404.
 *
 * @package Cita_Comparendo
 */

get_header();
?>

<main id="contenido">
    <div class="page-hero">
        <div class="container">
            <p class="eyebrow mb-1">404</p>
            <h1><?php esc_html_e('Página no encontrada', 'cita-comparendo'); ?></h1>
        </div>
    </div>

    <div class="section-py">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-12 col-lg-6">
                    <p class="mb-4"><?php esc_html_e('La página que buscas no existe o cambió de dirección.', 'cita-comparendo'); ?></p>
                    <a class="btn btn-warning" href="<?php echo esc_url(home_url('/')); ?>">
                        <?php esc_html_e('Volver al inicio', 'cita-comparendo'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();
