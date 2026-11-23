<?php
/**
 * Ficha individual de una sede (CPT `sede`).
 *
 * Reutiliza el modal #modal-load del footer para agendar en el CRM.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

// Leaflet solo se encola en la landing; aquí se necesita para el mapa de la sede.
wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

get_header();

$sede_id = (int) get_queried_object_id();
$sedes   = function_exists('cita_comparendo_sedes') ? (array) cita_comparendo_sedes() : array();
$sede    = null;

foreach ($sedes as $item) {
    if ((int) $item['id'] === $sede_id) {
        $sede = $item;
        break;
    }
}

// Respaldo: si el helper aún no incluye esta sede, se lee directo del post.
if (! $sede) {
    $ciudad    = get_the_terms($sede_id, 'ciudad');
    $zona      = get_the_terms($sede_id, 'zona');
    $localidad = get_the_terms($sede_id, 'localidad');

    $sede = array(
        'id'             => $sede_id,
        'nombre'         => get_the_title($sede_id),
        'slug'           => (string) get_post_field('post_name', $sede_id),
        'direccion'      => (string) get_post_meta($sede_id, 'direccion', true),
        'horario'        => (string) get_post_meta($sede_id, 'horario', true),
        'telefono'       => (string) get_post_meta($sede_id, 'telefono', true),
        'lat'            => get_post_meta($sede_id, 'lat', true),
        'lng'            => get_post_meta($sede_id, 'lng', true),
        'ciudad'         => (! empty($ciudad) && ! is_wp_error($ciudad)) ? $ciudad[0]->name : '',
        'ciudad_slug'    => (! empty($ciudad) && ! is_wp_error($ciudad)) ? $ciudad[0]->slug : '',
        'localidad'      => (! empty($localidad) && ! is_wp_error($localidad)) ? $localidad[0]->name : '',
        'localidad_slug' => (! empty($localidad) && ! is_wp_error($localidad)) ? $localidad[0]->slug : '',
        'zona'           => (! empty($zona) && ! is_wp_error($zona)) ? $zona[0]->slug : '',
        'zona_label'     => (! empty($zona) && ! is_wp_error($zona)) ? $zona[0]->name : '',
        'activa'         => true,
        'sede_crm'       => (string) get_post_meta($sede_id, 'complemento_iframe', true),
    );
}

$sede = wp_parse_args($sede, array(
    'nombre'     => get_the_title($sede_id),
    'direccion'  => '',
    'horario'    => '',
    'telefono'   => '',
    'lat'        => null,
    'lng'        => null,
    'ciudad'     => '',
    'zona_label' => '',
    'localidad'  => '',
    'activa'     => true,
    'sede_crm'   => '',
));

$lat = is_numeric($sede['lat']) ? (float) $sede['lat'] : null;
$lng = is_numeric($sede['lng']) ? (float) $sede['lng'] : null;

$sede_crm      = trim((string) get_post_meta($sede_id, 'complemento_iframe', true));
$crm_valido    = '' !== $sede_crm && 0 !== strpos($sede_crm, 'demo-');
$puede_agendar = ! empty($sede['activa']) && $crm_valido;

$telefono_url = preg_replace('/[^\d+]/', '', (string) $sede['telefono']);

$formulario_slug = function_exists('cita_comparendo_formulario_slug')
    ? trim((string) cita_comparendo_formulario_slug())
    : '';
$modal_destino = '' !== $formulario_slug ? '#modal-load' : '#modal-load-aviso';

$contenido = apply_filters('the_content', get_the_content());
$mapa_url  = null;

if (null !== $lat && null !== $lng) {
    $mapa_url = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($lat . ',' . $lng);
}
?>

<main id="contenido">

    <header class="page-hero sede-hero">
        <div class="container">
            <nav class="sede-hero__migas" aria-label="<?php esc_attr_e('Ruta de navegación', 'cita-comparendo'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Inicio', 'cita-comparendo'); ?></a>
                <span aria-hidden="true">/</span>
                <a href="<?php echo esc_url(home_url('/#sedes')); ?>"><?php esc_html_e('Sedes', 'cita-comparendo'); ?></a>
                <span aria-hidden="true">/</span>
                <span aria-current="page"><?php echo esc_html($sede['nombre']); ?></span>
            </nav>

            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                <div>
                    <?php if ('' !== $sede['ciudad'] || '' !== $sede['zona_label']) : ?>
                        <p class="eyebrow mb-2">
                            <?php echo esc_html(trim($sede['ciudad'] . ' · ' . $sede['zona_label'], ' ·')); ?>
                        </p>
                    <?php endif; ?>
                    <h1><?php echo esc_html($sede['nombre']); ?></h1>
                </div>

                <a class="sede-hero__volver" href="<?php echo esc_url(home_url('/#sedes')); ?>">
                    <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>
                    <?php esc_html_e('Ver todas las sedes', 'cita-comparendo'); ?>
                </a>
            </div>
        </div>
    </header>

    <section class="section-py">
        <div class="container">
            <div class="row g-4 g-lg-5 align-items-stretch">

                <div class="col-12 col-lg-5">
                    <article class="sede-ficha h-100">
                        <h2 class="sede-ficha__titulo"><?php esc_html_e('Datos de la sede', 'cita-comparendo'); ?></h2>

                        <ul class="sede-datos list-unstyled mb-0">
                            <?php if ('' !== $sede['direccion']) : ?>
                                <li class="sede-dato">
                                    <span class="sede-dato__icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                                    <span>
                                        <span class="sede-dato__label"><?php esc_html_e('Dirección', 'cita-comparendo'); ?></span>
                                        <span class="sede-dato__valor"><?php echo esc_html($sede['direccion']); ?></span>
                                    </span>
                                </li>
                            <?php endif; ?>

                            <?php if ('' !== $sede['horario']) : ?>
                                <li class="sede-dato">
                                    <span class="sede-dato__icon" aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
                                    <span>
                                        <span class="sede-dato__label"><?php esc_html_e('Horario', 'cita-comparendo'); ?></span>
                                        <span class="sede-dato__valor"><?php echo nl2br(esc_html($sede['horario'])); ?></span>
                                    </span>
                                </li>
                            <?php endif; ?>

                            <?php if ('' !== $sede['telefono']) : ?>
                                <li class="sede-dato">
                                    <span class="sede-dato__icon" aria-hidden="true"><i class="fa-solid fa-phone"></i></span>
                                    <span>
                                        <span class="sede-dato__label"><?php esc_html_e('Teléfono', 'cita-comparendo'); ?></span>
                                        <a class="sede-dato__valor" href="tel:<?php echo esc_attr($telefono_url); ?>">
                                            <?php echo esc_html($sede['telefono']); ?>
                                        </a>
                                    </span>
                                </li>
                            <?php endif; ?>

                            <?php if ('' !== $sede['ciudad'] || '' !== $sede['localidad']) : ?>
                                <li class="sede-dato">
                                    <span class="sede-dato__icon" aria-hidden="true"><i class="fa-solid fa-city"></i></span>
                                    <span>
                                        <span class="sede-dato__label"><?php esc_html_e('Ubicación', 'cita-comparendo'); ?></span>
                                        <span class="sede-dato__valor">
                                            <?php echo esc_html(trim($sede['ciudad'] . ' · ' . $sede['localidad'], ' ·')); ?>
                                        </span>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="sede-ficha__acciones">
                            <?php if ($puede_agendar) : ?>
                                <button type="button"
                                    class="btn btn-warning sede-agendar"
                                    sede="<?php echo esc_attr($sede_crm); ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="<?php echo esc_attr($modal_destino); ?>">
                                    <?php esc_html_e('Agendar en esta sede', 'cita-comparendo'); ?>
                                </button>
                            <?php elseif (! empty($sede['activa'])) : ?>
                                <span class="sede-ficha__no-disponible">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    <?php esc_html_e('Próximamente', 'cita-comparendo'); ?>
                                </span>
                            <?php else : ?>
                                <span class="sede-ficha__no-disponible">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    <?php esc_html_e('Esta sede no está recibiendo agendamientos por ahora.', 'cita-comparendo'); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($mapa_url) : ?>
                                <a class="btn sede-ficha__ruta" href="<?php echo esc_url($mapa_url); ?>" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-diamond-turn-right me-1" aria-hidden="true"></i>
                                    <?php esc_html_e('Cómo llegar', 'cita-comparendo'); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ('' !== trim(wp_strip_all_tags($contenido))) : ?>
                            <div class="sede-ficha__contenido">
                                <?php echo $contenido; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>

                <div class="col-12 col-lg-7 d-flex">
                    <div class="map map--sede w-100" id="mapa-sede" role="region"
                        aria-label="<?php esc_attr_e('Mapa de la sede', 'cita-comparendo'); ?>"
                        data-lat="<?php echo esc_attr(null !== $lat ? (string) $lat : ''); ?>"
                        data-lng="<?php echo esc_attr(null !== $lng ? (string) $lng : ''); ?>"
                        data-zoom="15"
                        data-nombre="<?php echo esc_attr($sede['nombre']); ?>"
                        data-direccion="<?php echo esc_attr($sede['direccion']); ?>"
                        data-pin-red="<?php echo esc_url(CITA_COMPARENDO_IMG_URI . 'elements/pin-red.svg'); ?>">
                        <div class="map__fallback d-flex flex-column align-items-center justify-content-center text-navy text-center px-3"
                            id="mapa-sede-fallback">
                            <i class="fa-solid fa-map-location-dot fs-4 mb-2" aria-hidden="true"></i>
                            <?php if (null !== $lat && null !== $lng) : ?>
                                <?php esc_html_e('Mapa de la sede', 'cita-comparendo'); ?>
                            <?php else : ?>
                                <?php esc_html_e('Esta sede aún no tiene ubicación en el mapa', 'cita-comparendo'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<?php
get_footer();
