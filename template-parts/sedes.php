<?php
/**
 * Sedes y horarios.
 *
 * Los datos vienen del CPT `sede` a través de cita_comparendo_sedes(). Si aún
 * no hay sedes publicadas se usa cita_default_sedes() como respaldo visual.
 *
 * @package Cita_Comparendo
 */

$sedes = function_exists('cita_comparendo_sedes') ? (array) cita_comparendo_sedes() : array();

// Respaldo visual mientras el CRM no tiene sedes publicadas. Solo el admin ve
// las cards de ejemplo; al público se le muestra un estado vacío.
$usa_respaldo     = empty($sedes);
$es_admin         = current_user_can('manage_options');
$mostrar_respaldo = $usa_respaldo && $es_admin;
$mostrar_vacio    = $usa_respaldo && ! $es_admin;

if ($mostrar_respaldo) {
    $sedes = cita_default_sedes();
}

// Zonas: etiquetas del contrato, ordenadas como en el diseño.
$zonas = function_exists('cita_comparendo_zonas') ? (array) cita_comparendo_zonas() : array();
if (empty($zonas)) {
    $zonas = array(
        'norte'     => 'Norte',
        'centro'    => 'Centro',
        'occidente' => 'Occidente',
        'sur'       => 'Sur',
    );
}

$orden_zonas = array('norte', 'centro', 'occidente', 'sur', 'oriente');
$zonas_ordenadas = array();
foreach ($orden_zonas as $slug_zona) {
    if (isset($zonas[$slug_zona])) {
        $zonas_ordenadas[$slug_zona] = $zonas[$slug_zona];
        unset($zonas[$slug_zona]);
    }
}
$zonas = $zonas_ordenadas + $zonas;

// Normaliza cada sede para que la vista no dependa de claves ausentes.
$sedes_normalizadas = array();
$zonas_con_sedes = array();
$ciudades_derivadas = array();

foreach ($sedes as $indice => $sede) {
    $sede = wp_parse_args($sede, array(
        'id'             => 0,
        'nombre'         => '',
        'slug'           => '',
        'direccion'      => '',
        'horario'        => '',
        'telefono'       => '',
        'lat'            => null,
        'lng'            => null,
        'ciudad'         => '',
        'ciudad_slug'    => '',
        'localidad'      => '',
        'localidad_slug' => '',
        'zona'           => '',
        'zona_label'     => '',
        'activa'         => true,
        'sede_crm'       => '',
    ));

    if ($mostrar_respaldo) {
        $sede['ciudad']      = 'Medellín';
        $sede['ciudad_slug'] = 'medellin';
        $sede['sede_crm']    = '';
    }

    if ($mostrar_respaldo && 0 === (int) $sede['id']) {
        $sede['id']   = 'demo-' . ($indice + 1);
        $sede['slug'] = 'sede-demo-' . ($indice + 1);
    }

    $sede['lat'] = is_numeric($sede['lat']) ? (float) $sede['lat'] : null;
    $sede['lng'] = is_numeric($sede['lng']) ? (float) $sede['lng'] : null;

    if ('' === $sede['zona_label'] && isset($zonas[$sede['zona']])) {
        $sede['zona_label'] = $zonas[$sede['zona']];
    }

    if ('' !== $sede['zona']) {
        $zonas_con_sedes[$sede['zona']] = true;
    }

    if ('' !== $sede['ciudad_slug']) {
        if (! isset($ciudades_derivadas[$sede['ciudad_slug']])) {
            $ciudades_derivadas[$sede['ciudad_slug']] = array(
                'slug'   => $sede['ciudad_slug'],
                'nombre' => $sede['ciudad'],
                'lat'    => null,
                'lng'    => null,
                'zoom'   => 12,
                'suma'   => 0,
                'total'  => 0,
            );
        }
        if (null !== $sede['lat'] && null !== $sede['lng']) {
            $ciudades_derivadas[$sede['ciudad_slug']]['lat'] = (float) $ciudades_derivadas[$sede['ciudad_slug']]['lat'] + $sede['lat'];
            $ciudades_derivadas[$sede['ciudad_slug']]['lng'] = (float) $ciudades_derivadas[$sede['ciudad_slug']]['lng'] + $sede['lng'];
            $ciudades_derivadas[$sede['ciudad_slug']]['total']++;
        }
    }

    $sedes_normalizadas[] = $sede;
}

$sedes = $sedes_normalizadas;

// Ciudades del contrato; si no hay helper se derivan de las sedes.
$ciudades = function_exists('cita_comparendo_ciudades') ? (array) cita_comparendo_ciudades() : array();

if (empty($ciudades)) {
    $ciudades = array();
    foreach ($ciudades_derivadas as $ciudad) {
        if ($ciudad['total'] > 0) {
            $ciudad['lat'] = $ciudad['lat'] / $ciudad['total'];
            $ciudad['lng'] = $ciudad['lng'] / $ciudad['total'];
        } else {
            $ciudad['lat'] = null;
            $ciudad['lng'] = null;
        }
        unset($ciudad['suma'], $ciudad['total']);
        $ciudades[] = $ciudad;
    }
}

// Solo se ofrecen las zonas que tienen sedes; si ninguna tiene, se muestran todas.
$zonas_filtradas = array();
if (! empty($zonas_con_sedes)) {
    foreach ($zonas as $slug_zona => $label_zona) {
        if (isset($zonas_con_sedes[$slug_zona])) {
            $zonas_filtradas[$slug_zona] = $label_zona;
        }
    }
}
if (empty($zonas_filtradas)) {
    $zonas_filtradas = $zonas;
}

$mapa_centro = function_exists('cita_comparendo_mapa_centro') ? (array) cita_comparendo_mapa_centro() : array();
$mapa_centro = wp_parse_args($mapa_centro, array('lat' => 4.5709, 'lng' => -74.2973, 'zoom' => 5));
$mapa_centro['lat']  = is_numeric($mapa_centro['lat']) ? (float) $mapa_centro['lat'] : 4.5709;
$mapa_centro['lng']  = is_numeric($mapa_centro['lng']) ? (float) $mapa_centro['lng'] : -74.2973;
$mapa_centro['zoom'] = (int) $mapa_centro['zoom'] > 0 ? (int) $mapa_centro['zoom'] : 5;

$tiene_coordenadas = false;
$sede_inicial      = null;

foreach ($sedes as $sede) {
    if (null !== $sede['lat'] && null !== $sede['lng']) {
        $tiene_coordenadas = true;
    }
    if (null === $sede_inicial && ! empty($sede['activa']) && null !== $sede['lat'] && null !== $sede['lng']) {
        $sede_inicial = $sede['id'];
    }
}
if (null === $sede_inicial) {
    $sede_inicial = $sedes ? $sedes[0]['id'] : 0;
}

$formulario_slug = function_exists('cita_comparendo_formulario_slug')
    ? trim((string) cita_comparendo_formulario_slug())
    : '';
$modal_destino = '' !== $formulario_slug ? '#modal-load' : '#modal-load-aviso';

$titulo_sedes = cita_field('sedes_titulo', 'Sedes y horarios');
?>
<section id="sedes" class="section-py">
    <div class="container">

        <div class="sedes__titulo d-flex align-items-center justify-content-center gap-3 gap-md-4 mb-4 mb-lg-5" data-reveal>
            <span class="section-title__line" aria-hidden="true"></span>
            <h2 class="section-title"><?php echo esc_html($titulo_sedes); ?></h2>
            <span class="section-title__line" aria-hidden="true"></span>
        </div>

        <?php if ($mostrar_respaldo) : ?>
            <p class="sedes__aviso-admin" data-reveal>
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <?php esc_html_e('No hay sedes sincronizadas', 'cita-comparendo'); ?>
            </p>
        <?php endif; ?>

        <?php if ($mostrar_vacio) : ?>

            <div class="sedes__estado-vacio">
                <span class="sedes__estado-vacio__icono" aria-hidden="true">
                    <i class="fa-solid fa-location-dot"></i>
                </span>
                <p class="sedes__estado-vacio__texto">
                    <?php esc_html_e('Estamos actualizando nuestras sedes. Escríbenos y te ayudamos a agendar.', 'cita-comparendo'); ?>
                </p>
                <a class="btn btn-warning" href="<?php echo esc_url(cita_whatsapp_url()); ?>" target="_blank" rel="noopener">
                    <i class="fa-brands fa-whatsapp me-1" aria-hidden="true"></i>
                    <?php esc_html_e('Escríbenos por WhatsApp', 'cita-comparendo'); ?>
                </a>
            </div>

        <?php else : ?>

        <div class="sedes__toolbar" data-reveal>
            <div class="sedes__ciudad">
                <label class="visually-hidden" for="filtro-ciudad">
                    <?php esc_html_e('Filtrar sedes por ciudad', 'cita-comparendo'); ?>
                </label>
                <select id="filtro-ciudad" class="sedes__select">
                    <option value="todas"><?php esc_html_e('Todas', 'cita-comparendo'); ?></option>
                    <?php foreach ($ciudades as $ciudad) :
                        $ciudad = wp_parse_args($ciudad, array('slug' => '', 'nombre' => '', 'lat' => null, 'lng' => null, 'zoom' => 0));
                        if ('' === $ciudad['slug']) {
                            continue;
                        }
                        ?>
                        <option value="<?php echo esc_attr($ciudad['slug']); ?>"
                            data-lat="<?php echo esc_attr((string) (is_numeric($ciudad['lat']) ? $ciudad['lat'] : '')); ?>"
                            data-lng="<?php echo esc_attr((string) (is_numeric($ciudad['lng']) ? $ciudad['lng'] : '')); ?>"
                            data-zoom="<?php echo esc_attr((string) ((int) $ciudad['zoom'] > 0 ? (int) $ciudad['zoom'] : 12)); ?>">
                            <?php echo esc_html('' !== $ciudad['nombre'] ? $ciudad['nombre'] : $ciudad['slug']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="fa-solid fa-caret-down" aria-hidden="true"></i>
            </div>

            <div class="sedes__filtros" role="group" aria-label="<?php esc_attr_e('Filtrar sedes por zona', 'cita-comparendo'); ?>">
                <button type="button" class="sedes__filtro filtro-zona is-active" data-zona="todas"
                    aria-pressed="true"><?php esc_html_e('Todas', 'cita-comparendo'); ?></button>

                <button type="button" class="sedes__filtro filtro-zona" data-zona="cercade" aria-pressed="false">
                    <i class="fa-solid fa-location-crosshairs me-1" aria-hidden="true"></i><?php esc_html_e('Cerca de mí', 'cita-comparendo'); ?>
                </button>

                <?php foreach ($zonas_filtradas as $slug_zona => $label_zona) : ?>
                    <button type="button" class="sedes__filtro filtro-zona" data-zona="<?php echo esc_attr($slug_zona); ?>"
                        aria-pressed="false"><?php echo esc_html($label_zona); ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="sedes__mensaje" id="sedes-mensaje" role="status" aria-live="polite" hidden></p>

        <div class="row g-4 g-lg-5 mt-1 mt-lg-2">
            <div class="col-12 col-xl-6">
                <div class="row row-cols-1 row-cols-sm-2 g-3 g-lg-4" id="sedes-lista">
                    <?php foreach ($sedes as $indice => $sede) :
                        $es_inicial = ((string) $sede['id'] === (string) $sede_inicial);
                        $tiene_ubicacion = null !== $sede['lat'] && null !== $sede['lng'];
                        $url_sede = is_numeric($sede['id']) ? get_permalink((int) $sede['id']) : '';
                        $sede_crm = is_numeric($sede['id'])
                            ? trim((string) get_post_meta((int) $sede['id'], 'complemento_iframe', true))
                            : '';
                        $crm_valido = '' !== $sede_crm && 0 !== strpos($sede_crm, 'demo-');
                        $puede_agendar = ! empty($sede['activa']) && $crm_valido;
                        ?>
                        <div class="col sede-item">
                            <article class="sede-card d-flex flex-column<?php echo $es_inicial ? ' is-active' : ''; ?>"
                                data-sede-id="<?php echo esc_attr((string) $sede['id']); ?>"
                                data-lat="<?php echo esc_attr($tiene_ubicacion ? (string) $sede['lat'] : ''); ?>"
                                data-lng="<?php echo esc_attr($tiene_ubicacion ? (string) $sede['lng'] : ''); ?>"
                                data-ciudad="<?php echo esc_attr($sede['ciudad']); ?>"
                                data-ciudad-slug="<?php echo esc_attr($sede['ciudad_slug']); ?>"
                                data-zona="<?php echo esc_attr($sede['zona']); ?>"
                                data-nombre="<?php echo esc_attr($sede['nombre']); ?>"
                                data-direccion="<?php echo esc_attr($sede['direccion']); ?>"
                                data-horario="<?php echo esc_attr($sede['horario']); ?>"
                                data-url="<?php echo esc_url(is_string($url_sede) ? $url_sede : ''); ?>">

                                <h3 class="sede-card__name">
                                    <button type="button" class="sede-card__activar"
                                        aria-label="<?php echo esc_attr(sprintf(__('%1$s: ver en el mapa', 'cita-comparendo'), $sede['nombre'])); ?>">
                                        <?php echo esc_html($sede['nombre']); ?>
                                    </button>
                                </h3>

                                <?php if ('' !== $sede['direccion']) : ?>
                                    <p class="sede-card__meta"><?php echo esc_html($sede['direccion']); ?></p>
                                <?php endif; ?>
                                <?php if ('' !== $sede['horario']) : ?>
                                    <p class="sede-card__meta mb-3"><?php echo nl2br(esc_html($sede['horario'])); ?></p>
                                <?php endif; ?>

                                <?php if ('' !== $sede['ciudad']) : ?>
                                    <p class="sede-card__lugar mb-3">
                                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                        <?php echo esc_html(trim($sede['ciudad'] . ' · ' . $sede['zona_label'], ' ·')); ?>
                                    </p>
                                <?php endif; ?>

                                <span class="sede-card__dist" hidden></span>

                                <?php if ($puede_agendar) : ?>
                                    <button type="button"
                                        class="btn sede-card__btn sede-agendar mt-auto align-self-start"
                                        sede="<?php echo esc_attr($sede_crm); ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="<?php echo esc_attr($modal_destino); ?>">
                                        <?php esc_html_e('Agendar', 'cita-comparendo'); ?>
                                    </button>
                                <?php elseif (! empty($sede['activa'])) : ?>
                                    <span class="sede-card__no-disponible mt-auto align-self-start">
                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                        <?php esc_html_e('Próximamente', 'cita-comparendo'); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="sede-card__no-disponible mt-auto align-self-start">
                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                        <?php esc_html_e('No disponible', 'cita-comparendo'); ?>
                                    </span>
                                <?php endif; ?>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p class="sedes__vacio" id="sedes-vacio" role="status" hidden>
                    <i class="fa-regular fa-map" aria-hidden="true"></i>
                    <?php esc_html_e('No hay sedes en este filtro', 'cita-comparendo'); ?>
                </p>
            </div>

            <div class="col-12 col-xl-6 d-flex">
                <div class="map map--sedes w-100" id="mapa-sedes" role="region"
                    aria-label="<?php esc_attr_e('Mapa de sedes', 'cita-comparendo'); ?>"
                    data-lat="<?php echo esc_attr((string) $mapa_centro['lat']); ?>"
                    data-lng="<?php echo esc_attr((string) $mapa_centro['lng']); ?>"
                    data-zoom="<?php echo esc_attr((string) $mapa_centro['zoom']); ?>"
                    data-pin-blue="<?php echo esc_url(CITA_COMPARENDO_IMG_URI . 'elements/pin-blue.svg'); ?>"
                    data-pin-red="<?php echo esc_url(CITA_COMPARENDO_IMG_URI . 'elements/pin-red.svg'); ?>"
                    data-reveal style="--reveal-delay:160ms;">
                    <div class="map__fallback d-flex flex-column align-items-center justify-content-center text-navy text-center px-3" id="mapa-fallback">
                        <i class="fa-solid fa-map-location-dot fs-4 mb-2" aria-hidden="true"></i>
                        <?php if ($tiene_coordenadas) : ?>
                            <?php esc_html_e('Mapa de sedes', 'cita-comparendo'); ?>
                        <?php else : ?>
                            <?php esc_html_e('No hay sedes con ubicación en el mapa', 'cita-comparendo'); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </div>
</section>
