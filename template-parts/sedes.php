<?php
/**
 * Sedes y horarios.
 *
 * @package Cita_Comparendo
 */

$sedes = cita_field('sedes', cita_default_sedes());
$zonas = array(
    'medellin'  => 'Medellín',
    'todas'     => 'Todas',
    'cercade'   => 'Cerca de mí',
    'norte'     => 'Norte',
    'centro'    => 'Centro',
    'occidente' => 'Occidente',
    'sur'       => 'Sur',
);
?>
<section id="sedes" class="section-py">
    <div class="container">

        <div class="d-flex align-items-center justify-content-center gap-3 gap-md-4 mb-4 mb-lg-5" data-reveal>
            <span class="section-title__line" aria-hidden="true"></span>
            <h2 class="section-title"><?php echo esc_html(cita_field('sedes_titulo', 'Sedes y horarios')); ?></h2>
            <span class="section-title__line" aria-hidden="true"></span>
        </div>

        <div class="d-flex justify-content-center mb-4" data-reveal style="--reveal-delay:100ms;">
            <nav class="sedes__tabs d-flex gap-1" aria-label="<?php esc_attr_e('Filtrar sedes por zona', 'cita-comparendo'); ?>">
                <?php $primero = true; foreach ($zonas as $slug => $label) : ?>
                    <button type="button" class="nav-link<?php echo $primero ? ' active' : ''; ?>"
                        data-zona="<?php echo esc_attr($slug); ?>"
                        aria-pressed="<?php echo $primero ? 'true' : 'false'; ?>">
                        <?php if ('medellin' === $slug) : ?><i class="fa-solid fa-caret-down me-1" aria-hidden="true"></i><?php endif; ?>
                        <?php echo esc_html($label); ?>
                    </button>
                <?php $primero = false; endforeach; ?>
            </nav>
        </div>

        <div class="row g-4 g-lg-5">
            <div class="col-12 col-lg-6">
                <div class="row row-cols-1 row-cols-sm-2 g-3" id="sedes-lista">
                    <?php foreach ($sedes as $index => $sede) :
                        $activa = ! empty($sede['activa']) || 0 === $index;
                        $zona   = isset($sede['zona']) ? $sede['zona'] : 'centro';
                    ?>
                        <div class="col sede-item" data-zona="<?php echo esc_attr($zona); ?>">
                            <article class="sede-card d-flex flex-column<?php echo $activa ? ' is-active' : ''; ?>">
                                <h3 class="sede-card__name"><?php echo esc_html($sede['nombre']); ?></h3>
                                <p class="sede-card__meta"><?php echo esc_html($sede['direccion']); ?></p>
                                <p class="sede-card__meta mb-3"><?php echo esc_html($sede['horario']); ?></p>
                                <a class="btn <?php echo $activa ? 'btn-primary' : 'btn-outline-primary'; ?> mt-auto align-self-start"
                                    href="<?php echo esc_url(cita_whatsapp_url($sede['nombre'])); ?>" target="_blank" rel="noopener">
                                    <?php esc_html_e('Agendar', 'cita-comparendo'); ?>
                                </a>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="map" id="mapa-sedes" data-reveal style="--reveal-delay:160ms;">
                    <div class="map__fallback d-flex align-items-center justify-content-center text-navy" id="mapa-fallback">
                        <?php esc_html_e('Mapa de sedes', 'cita-comparendo'); ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
