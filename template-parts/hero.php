<?php
/**
 * Hero.
 *
 * @package Cita_Comparendo
 */

$hero_title_1    = cita_field('hero_title_1', 'Agenda tu curso y');
$hero_title_high = cita_field('hero_title_high', 'ahorra hasta 50%');
$hero_title_2    = cita_field('hero_title_2', 'en tu comparendo.');
$hero_cta        = cita_field('hero_cta_texto', 'Elige tu horario');
$hero_nota       = cita_field('hero_nota', 'Consulta disponibilidad');
$hero_nota_high  = cita_field('hero_nota_high', 'sin compromiso');
$hero_rating_txt = cita_field('hero_rating_texto', 'Nuestros conductores nos califican con');
$hero_rating     = cita_field('hero_rating', '4,8/5');
$hero_conductores = cita_field('hero_conductores_texto', '');
?>
<section id="inicio" class="pt-3 pt-lg-4">
    <div class="container">
        <div class="hero row g-0 rounded-cita overflow-hidden shadow-cita">

            <div class="col-12 col-lg-6 hero__panel d-flex flex-column justify-content-center">
                <h1 class="hero__title" data-reveal style="--reveal-delay:80ms;">
                    <?php echo esc_html($hero_title_1); ?><br>
                    <span class="text-yellow"><?php echo esc_html($hero_title_high); ?></span><br>
                    <?php echo esc_html($hero_title_2); ?>
                </h1>

                <div data-reveal style="--reveal-delay:180ms;">
                    <a class="btn btn-warning hero__cta" href="#sedes"><?php echo esc_html($hero_cta); ?></a>
                </div>

                <p class="hero__note" data-reveal style="--reveal-delay:260ms;">
                    <?php echo esc_html($hero_nota); ?> <span class="text-yellow"><?php echo esc_html($hero_nota_high); ?></span>.
                </p>

                <p class="hero__rating" data-reveal style="--reveal-delay:340ms;">
                    <span class="hero__rating-line hero__rating-line--left" aria-hidden="true"></span>
                    <?php echo esc_html($hero_rating_txt); ?>
                    <span class="text-yellow"><?php echo esc_html($hero_rating); ?></span>
                    <i class="fa-solid fa-star text-yellow animate__animated animate__heartBeat animate__infinite" aria-hidden="true"></i>
                    <span class="hero__rating-line" aria-hidden="true"></span>
                </p>

                <?php if ('' !== trim((string) $hero_conductores)) : ?>
                    <p class="hero__conductores" data-reveal style="--reveal-delay:420ms;">
                        <?php echo esc_html($hero_conductores); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="col-12 col-lg-6 hero__aside d-flex flex-column">
                <img class="hero__photo flex-grow-1"
                    src="<?php echo esc_url(cita_imagen_url('hero_imagen', 'landings/home/hero-collage.png')); ?>"
                    alt="<?php esc_attr_e('Personas tomando el curso comparendo en clase', 'cita-comparendo'); ?>"
                    width="900" height="700" fetchpriority="high">

                <div class="hero__partners">
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-between gap-3 mb-3">
                        <?php
                        $aliados = array(
                            'simit-logo.png'           => array('alt' => 'SIMIT', 'clase' => 'hero__partner--simit'),
                            'logo runt.png'            => array('alt' => 'RUNT', 'clase' => 'hero__partner--runt'),
                            'logo-min-transporte.jpeg' => array('alt' => 'Mintransporte', 'clase' => 'hero__partner--mintransporte'),
                        );
                        ?>
                        <?php foreach ($aliados as $archivo => $aliado) : ?>
                            <img class="hero__partner <?php echo esc_attr($aliado['clase']); ?>"
                                src="<?php echo esc_url(CITA_COMPARENDO_IMG_URI . 'logos/' . $archivo); ?>"
                                alt="<?php echo esc_attr($aliado['alt']); ?>" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                    <p class="hero__partners-caption">
                        <?php echo esc_html(cita_field('hero_legal', 'Todas nuestras sedes aliadas son avalados y vigilados por estas entidades')); ?>
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>
