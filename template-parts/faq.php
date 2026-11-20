<?php
/**
 * Preguntas frecuentes (tabs verticales de Bootstrap).
 *
 * @package Cita_Comparendo
 */

$faqs = cita_field('faqs', cita_default_faq());
?>
<section id="faq" class="section-py section--faq">
    <img class="faq__photo" src="<?php echo esc_url(cita_imagen_url('faq_imagen', 'landings/home/faq.jpg')); ?>" alt="" aria-hidden="true" loading="lazy">
    <span class="faq__mark" aria-hidden="true">?</span>

    <div class="container position-relative">
        <div class="mb-4 mb-lg-5" data-reveal>
            <p class="eyebrow mb-1"><?php echo esc_html(cita_field('faq_eyebrow', '¿Tienes dudas?')); ?></p>
            <h2 class="section-title"><?php echo esc_html(cita_field('faq_titulo', 'Preguntas frecuentes')); ?></h2>
        </div>

        <div class="row g-4 g-lg-5">
            <div class="col-12 col-lg-5">
                <div class="nav flex-column faq__list" role="tablist" aria-orientation="vertical">
                    <?php foreach ($faqs as $index => $faq) : ?>
                        <button class="faq__question<?php echo 0 === $index ? ' active' : ''; ?>"
                            id="faq-tab-<?php echo esc_attr($index); ?>"
                            data-bs-toggle="tab" data-bs-target="#faq-pane-<?php echo esc_attr($index); ?>"
                            type="button" role="tab"
                            aria-controls="faq-pane-<?php echo esc_attr($index); ?>"
                            aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>">
                            <?php echo esc_html($faq['pregunta']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="tab-content">
                    <?php foreach ($faqs as $index => $faq) : ?>
                        <div class="tab-pane fade<?php echo 0 === $index ? ' show active' : ''; ?>"
                            id="faq-pane-<?php echo esc_attr($index); ?>" role="tabpanel"
                            aria-labelledby="faq-tab-<?php echo esc_attr($index); ?>" tabindex="0">
                            <p class="faq__answer mb-0"><?php echo esc_html($faq['respuesta']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a class="btn btn-outline-light mt-4" href="#faq">
                    <?php echo esc_html(cita_field('faq_boton', 'Ver todas las preguntas')); ?>
                </a>
            </div>
        </div>
    </div>
</section>
