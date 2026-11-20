<?php
/**
 * Testimonios.
 *
 * @package Cita_Comparendo
 */

$testimonios = cita_field('testimonios', cita_default_testimonios());
?>
<section id="experiencias" class="section-py">
    <div class="container">

        <div class="text-center mb-4 mb-lg-5" data-reveal>
            <p class="eyebrow mb-1"><?php echo esc_html(cita_field('experiencias_eyebrow', 'Experiencias')); ?></p>
            <div class="d-flex align-items-center justify-content-center gap-3 gap-md-4">
                <span class="section-title__line" aria-hidden="true"></span>
                <h2 class="section-title"><?php echo esc_html(cita_field('experiencias_titulo', 'Lo que dicen de nosotros')); ?></h2>
                <span class="section-title__line" aria-hidden="true"></span>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3 g-lg-4">
            <?php foreach ($testimonios as $index => $testimonio) : ?>
                <div class="col">
                    <article class="testimonial-card d-flex flex-column" data-reveal style="--reveal-delay:<?php echo esc_attr(70 * $index); ?>ms;">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="testimonial-card__icon d-inline-flex align-items-center justify-content-center" aria-hidden="true">
                                <i class="fa-solid fa-quote-left"></i>
                            </span>
                            <span class="testimonial-card__rating">
                                <?php echo wp_kses_post(cita_estrellas_html(isset($testimonio['rating']) ? $testimonio['rating'] : '5')); ?>
                            </span>
                        </div>
                        <p class="testimonial-card__text flex-grow-1"><?php echo esc_html($testimonio['texto']); ?></p>
                        <footer>
                            <p class="testimonial-card__name"><?php echo esc_html($testimonio['nombre']); ?></p>
                            <p class="testimonial-card__role"><?php echo esc_html($testimonio['rol']); ?></p>
                        </footer>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cta-bar d-flex flex-column flex-lg-row align-items-center justify-content-center gap-3 gap-lg-4 mt-4 mt-lg-5" data-reveal>
            <p class="cta-bar__text text-center text-lg-start">
                <strong><?php echo esc_html(cita_field('cta_bar_high', 'Más de 100 mil')); ?></strong>
                <?php echo esc_html(cita_field('cta_bar_texto', 'conductores y motociclistas satisfechos')); ?>
            </p>
            <a class="btn btn-primary" href="#sedes"><?php echo esc_html(cita_field('cta_bar_boton', 'Agendar mi curso')); ?></a>
        </div>

    </div>
</section>
