<?php
/**
 * Testimonios.
 *
 * Datos del backend (cita_comparendo_testimonios) con respaldo en ACF. Las
 * fotos y los audios son opcionales: sin foto se pinta la inicial y sin audio
 * se conserva el icono de cita.
 *
 * @package Cita_Comparendo
 */

$testimonios = function_exists('cita_comparendo_testimonios')
    ? cita_comparendo_testimonios()
    : array();

if (empty($testimonios) || ! is_array($testimonios)) {
    $testimonios = cita_field('testimonios', cita_default_testimonios());
}

wp_enqueue_script(
    'cita-testimonios',
    CITA_COMPARENDO_JS_URI . 'testimonios.js',
    array('cita-main'),
    cita_comparendo_asset_version('/assets/js/testimonios.js'),
    true
);
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
            <?php
            $contador_audio = 0;

            foreach ($testimonios as $index => $testimonio) :
                if (! is_array($testimonio)) {
                    continue;
                }

                $testimonio = wp_parse_args($testimonio, array(
                    'nombre' => '',
                    'rol'    => '',
                    'rating' => '5',
                    'texto'  => '',
                    'foto'   => '',
                    'audio'  => '',
                ));

                $nombre = trim((string) $testimonio['nombre']);
                $rol    = trim((string) $testimonio['rol']);
                $texto  = trim((string) $testimonio['texto']);
                $rating = '' !== trim((string) $testimonio['rating']) ? $testimonio['rating'] : '5';

                $foto = $testimonio['foto'];
                if (is_array($foto)) {
                    $foto = isset($foto['url']) ? $foto['url'] : '';
                }
                if (is_numeric($foto)) {
                    $foto = wp_get_attachment_image_url((int) $foto, 'medium');
                }
                $foto = is_string($foto) ? trim($foto) : '';

                $audio = is_string($testimonio['audio']) ? trim($testimonio['audio']) : '';

                $inicial = '';
                if ('' !== $nombre) {
                    $inicial = function_exists('mb_substr')
                        ? mb_substr($nombre, 0, 1, 'UTF-8')
                        : substr($nombre, 0, 1);
                    $inicial = function_exists('mb_strtoupper')
                        ? mb_strtoupper($inicial, 'UTF-8')
                        : strtoupper($inicial);
                }

                $contador_audio++;
                $audio_id = 'testimonio-audio-' . $contador_audio;
                $label_play = sprintf(__('Escuchar testimonio de %s', 'cita-comparendo'), $nombre);
                $label_pause = sprintf(__('Pausar testimonio de %s', 'cita-comparendo'), $nombre);
                ?>
                <div class="col">
                    <article class="testimonial-card d-flex flex-column" data-reveal style="--reveal-delay:<?php echo esc_attr(70 * ($contador_audio - 1)); ?>ms;">
                        <div class="testimonial-card__top">
                            <?php if ('' !== $foto) : ?>
                                <img class="testimonial-card__avatar" src="<?php echo esc_url($foto); ?>"
                                    width="56" height="56" loading="lazy" alt="<?php echo esc_attr($nombre); ?>">
                            <?php else : ?>
                                <span class="testimonial-card__avatar testimonial-card__avatar--inicial" aria-hidden="true">
                                    <?php echo esc_html($inicial); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ('' !== $audio) : ?>
                                <button type="button"
                                    class="testimonial-card__icon testimonial-card__audio d-inline-flex align-items-center justify-content-center"
                                    aria-controls="<?php echo esc_attr($audio_id); ?>"
                                    aria-label="<?php echo esc_attr($label_play); ?>"
                                    aria-pressed="false"
                                    data-label-play="<?php echo esc_attr($label_play); ?>"
                                    data-label-pause="<?php echo esc_attr($label_pause); ?>">
                                    <i class="fa-solid fa-play" aria-hidden="true"></i>
                                </button>
                                <audio id="<?php echo esc_attr($audio_id); ?>" class="testimonial-card__audio-fuente"
                                    src="<?php echo esc_url($audio); ?>" preload="none" hidden></audio>
                            <?php else : ?>
                                <span class="testimonial-card__icon d-inline-flex align-items-center justify-content-center" aria-hidden="true">
                                    <i class="fa-solid fa-quote-left"></i>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="testimonial-card__rating">
                            <?php echo wp_kses_post(cita_estrellas_html($rating)); ?>
                        </div>

                        <p class="testimonial-card__text flex-grow-1"><?php echo esc_html($texto); ?></p>

                        <footer>
                            <p class="testimonial-card__name"><?php echo esc_html($nombre); ?></p>
                            <p class="testimonial-card__role"><?php echo esc_html($rol); ?></p>
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
