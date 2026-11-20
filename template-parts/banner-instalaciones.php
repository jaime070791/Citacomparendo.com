<?php
/**
 * Banner de instalaciones.
 *
 * @package Cita_Comparendo
 */

$banner_titulo = cita_field('banner_titulo', 'Contamos con instalaciones');
$banner_high   = cita_field('banner_high', 'más cómodas y modernas en Medellín.');
$banner_cta    = cita_field('banner_boton', 'Agendar mi curso');
?>
<section id="instalaciones" class="pb-4 pb-lg-5">
    <div class="container">
        <div class="banner" data-reveal>
            <img class="banner__img"
                src="<?php echo esc_url(cita_imagen_url('banner_imagen', 'landings/home/aula.jpg')); ?>"
                alt="<?php esc_attr_e('Aula moderna para el curso comparendo en Medellín', 'cita-comparendo'); ?>"
                width="1400" height="600" loading="lazy">
            <div class="banner__overlay" aria-hidden="true"></div>

            <div class="banner__content d-flex flex-column justify-content-center">
                <h2 class="banner__title mb-4">
                    <?php echo esc_html($banner_titulo); ?><br>
                    <span class="text-yellow"><?php echo esc_html($banner_high); ?></span>
                </h2>
                <div>
                    <a class="btn btn-warning" href="#sedes"><?php echo esc_html($banner_cta); ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
