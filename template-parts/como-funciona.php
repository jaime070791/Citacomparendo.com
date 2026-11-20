<?php
/**
 * Cómo funciona.
 *
 * @package Cita_Comparendo
 */

$pasos    = cita_field('pasos', cita_default_pasos());
$pasos_bg = cita_imagen_url('pasos_imagen', '');
?>
<section id="como-funciona" class="section-py section--steps">
    <?php if ($pasos_bg) : ?>
        <img class="steps__bg" src="<?php echo esc_url($pasos_bg); ?>" alt="" aria-hidden="true" loading="lazy">
    <?php endif; ?>

    <div class="container position-relative">

        <div class="mb-4 mb-lg-5" data-reveal>
            <p class="eyebrow mb-1"><?php echo esc_html(cita_field('pasos_eyebrow', '¿Cómo funciona?')); ?></p>
            <h2 class="section-title"><?php echo esc_html(cita_field('pasos_titulo', 'Hazlo fácil y rápido')); ?></h2>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 g-lg-4">
            <?php foreach ($pasos as $index => $paso) : ?>
                <div class="col">
                    <article class="step-card" data-reveal style="--reveal-delay:<?php echo esc_attr(80 * $index); ?>ms;">
                        <div class="step-card__icon" aria-hidden="true">
                            <i class="fa-solid <?php echo esc_attr(isset($paso['icono']) ? $paso['icono'] : 'fa-star'); ?>"></i>
                        </div>
                        <h3 class="step-card__title"><?php echo esc_html($paso['titulo']); ?></h3>
                        <div class="step-card__divider" aria-hidden="true"></div>
                        <p class="step-card__text"><?php echo esc_html($paso['texto']); ?></p>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4 mt-lg-5" data-reveal>
            <a class="btn btn-warning" href="#sedes"><?php echo esc_html(cita_field('pasos_boton', 'Agendar mi curso')); ?></a>
        </div>

    </div>
</section>
