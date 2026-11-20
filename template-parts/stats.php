<?php
/**
 * Cifras.
 *
 * @package Cita_Comparendo
 */

$stats_title = cita_field('cifras_titulo', 'Cifras que nos posicionan como una de las plataformas más confiables desde el 2025');
$stats_cta   = cita_field('cifras_boton', 'Agendar mi curso');
$cifras      = cita_field('cifras', cita_default_cifras());
?>
<section id="cifras" class="py-3 py-lg-4">
    <div class="container">
        <div class="stats" data-reveal>
            <div class="row align-items-center g-3 g-lg-4 mb-4 mb-lg-5">
                <div class="col-12 col-lg-8">
                    <h2 class="stats__title mb-0"><?php echo esc_html($stats_title); ?></h2>
                </div>
                <div class="col-12 col-lg-4 text-lg-end">
                    <a class="btn btn-primary" href="#sedes"><?php echo esc_html($stats_cta); ?></a>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-4 text-center">
                <?php foreach ($cifras as $cifra) :
                    $valor = isset($cifra['valor']) ? $cifra['valor'] : '';
                    $icono = isset($cifra['icono']) ? $cifra['icono'] : 'fa-star';
                    $es_rating = (false !== strpos($valor, '/'));
                    $numero = (float) preg_replace('/[^0-9,\.]/', '', str_replace(',', '.', $valor));
                ?>
                    <div class="col">
                        <div class="d-flex flex-column align-items-center">
                            <span class="stats__icon d-inline-flex align-items-center justify-content-center mb-3" aria-hidden="true">
                                <i class="fa-solid <?php echo esc_attr($icono); ?>"></i>
                            </span>
                            <p class="stats__value js-count mb-2"
                                data-target="<?php echo esc_attr($es_rating ? $numero : preg_replace('/\D/', '', $valor)); ?>"
                                data-prefix="<?php echo esc_attr(strpos($valor, '+') === 0 ? '+' : ''); ?>"
                                data-suffix="<?php echo esc_attr($es_rating ? '/5' : ''); ?>"
                                data-decimals="<?php echo esc_attr($es_rating ? 1 : 0); ?>">
                                <?php echo esc_html($valor); ?>
                            </p>
                            <p class="stats__label"><?php echo esc_html(isset($cifra['etiqueta']) ? $cifra['etiqueta'] : ''); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
