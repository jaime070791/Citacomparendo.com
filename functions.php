<?php
/**
 * CITA Comparendo - funciones del tema.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

define('CITA_COMPARENDO_VERSION', '1.0.0');
define('CITA_COMPARENDO_DIR', get_template_directory());
define('CITA_COMPARENDO_URI', get_template_directory_uri());
define('CITA_COMPARENDO_CSS_URI', CITA_COMPARENDO_URI . '/assets/css/');
define('CITA_COMPARENDO_JS_URI', CITA_COMPARENDO_URI . '/assets/js/');
define('CITA_COMPARENDO_IMG_URI', CITA_COMPARENDO_URI . '/assets/images/');
define('CITA_COMPARENDO_VCACHE', '1.0.0');

require_once CITA_COMPARENDO_DIR . '/inc/class-cita-comparendo-bootstrap-walker.php';
require_once CITA_COMPARENDO_DIR . '/inc/template-tags.php';

// CPT de sedes, sincronización con el CRM y compra de paquetes.
require_once CITA_COMPARENDO_DIR . '/inc/cpt-sede.php';
require_once CITA_COMPARENDO_DIR . '/inc/sede-meta.php';
require_once CITA_COMPARENDO_DIR . '/inc/api-crm.php';
require_once CITA_COMPARENDO_DIR . '/inc/sync-sedes.php';
require_once CITA_COMPARENDO_DIR . '/inc/sedes-data.php';
require_once CITA_COMPARENDO_DIR . '/inc/paquetes.php';
require_once CITA_COMPARENDO_DIR . '/inc/admin-ajustes.php';
require_once CITA_COMPARENDO_DIR . '/inc/gracias.php';
require_once CITA_COMPARENDO_DIR . '/inc/rest-sedes.php';
require_once CITA_COMPARENDO_DIR . '/inc/cpt-testimonio.php';
require_once CITA_COMPARENDO_DIR . '/inc/testimonios-data.php';

/**
 * Configuración base del tema.
 */
function cita_comparendo_setup()
{
    load_theme_textdomain('cita-comparendo', CITA_COMPARENDO_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo', array(
        'height'      => 104,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');

    register_nav_menus(array(
        'primary' => __('Menú principal', 'cita-comparendo'),
        'footer'  => __('Menú del footer', 'cita-comparendo'),
    ));

    add_image_size('cita-card', 640, 427, true);
    add_image_size('cita-wide', 1440, 620, true);
}
add_action('after_setup_theme', 'cita_comparendo_setup');

/**
 * Versión de un asset basada en su fecha de modificación (cache busting).
 *
 * @param string $relative Ruta relativa al directorio del tema.
 * @return string
 */
function cita_comparendo_asset_version($relative)
{
    $path = CITA_COMPARENDO_DIR . $relative;
    return file_exists($path) ? (string) filemtime($path) : CITA_COMPARENDO_VCACHE;
}

/**
 * Hojas de estilo. Orden: bootstrap -> fuentes -> iconos -> style.css -> responsive.
 */
function cita_comparendo_styles()
{
    wp_enqueue_style('bootstrap', CITA_COMPARENDO_CSS_URI . 'bootstrap.min.css', array(), '5.3.8');
    wp_enqueue_style('cita-fuentes', CITA_COMPARENDO_CSS_URI . 'fuentes.css', array(), cita_comparendo_asset_version('/assets/css/fuentes.css'));
    wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
    wp_enqueue_style('cita-style', CITA_COMPARENDO_URI . '/style.css', array('bootstrap', 'cita-fuentes', 'fontawesome'), cita_comparendo_asset_version('/style.css'));
    wp_enqueue_style('cita-responsive', CITA_COMPARENDO_CSS_URI . 'responsive-style.css', array('cita-style'), cita_comparendo_asset_version('/assets/css/responsive-style.css'));

    if (is_front_page()) {
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
    }
}
add_action('wp_enqueue_scripts', 'cita_comparendo_styles');

/**
 * Scripts. Bootstrap bundle + main.js. Leaflet solo en la landing.
 */
function cita_comparendo_scripts()
{
    wp_enqueue_script('bootstrap', CITA_COMPARENDO_JS_URI . 'bootstrap.bundle.min.js', array(), '5.3.8', true);
    wp_enqueue_script('cita-main', CITA_COMPARENDO_JS_URI . 'main.js', array('bootstrap'), cita_comparendo_asset_version('/assets/js/main.js'), true);

    if (is_front_page()) {
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
    }
}
add_action('wp_enqueue_scripts', 'cita_comparendo_scripts');

/**
 * Tabla de compras de paquetes: se crea/actualiza al activar el tema.
 */
add_action('after_switch_theme', 'cita_comparendo_crear_tabla_paquetes');

/**
 * JS de administración para Sedes → Sincronizar sedes.
 *
 * @param string $hook_suffix Pantalla actual del admin.
 * @return void
 */
function cita_comparendo_admin_assets($hook_suffix)
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $es_sede = ($screen && 'sede' === $screen->post_type)
        || false !== strpos((string) $hook_suffix, 'cita-sincronizar-sedes');

    if (! $es_sede) {
        return;
    }

    wp_enqueue_script(
        'cita-sedes-sync',
        CITA_COMPARENDO_JS_URI . 'sedes-sync.js',
        array(),
        cita_comparendo_asset_version('/assets/js/sedes-sync.js'),
        true
    );
    wp_localize_script('cita-sedes-sync', 'CITA_SEDES_SYNC', cita_comparendo_sedes_sync_config());
}
add_action('admin_enqueue_scripts', 'cita_comparendo_admin_assets');

/**
 * Datos del mapa que se localizan como CITA_MAP.
 *
 * Los pines salen del CPT `sede` (solo sedes publicadas con coordenadas) y el
 * centro/zoom por defecto es Colombia. Sin pines devuelve una lista vacía: el
 * frontend tiene su propio fallback visual.
 *
 * @return array
 */
function cita_comparendo_map_data(): array
{
    $pins = [];

    foreach (cita_comparendo_sedes() as $sede) {
        if ($sede['lat'] === null || $sede['lng'] === null) {
            continue;
        }
        $pins[] = [
            'id'          => $sede['id'],
            'nombre'      => $sede['nombre'],
            'direccion'   => $sede['direccion'],
            'horario'     => $sede['horario'],
            'telefono'    => $sede['telefono'],
            'lat'         => $sede['lat'],
            'lng'         => $sede['lng'],
            'activa'      => $sede['activa'],
            'ciudad'      => $sede['ciudad'],
            'ciudad_slug' => $sede['ciudad_slug'],
            'zona'        => $sede['zona'],
            'sede_crm'    => $sede['sede_crm'],
        ];
    }

    $centro = cita_comparendo_mapa_centro();

    return [
        'center'   => ['lat' => $centro['lat'], 'lng' => $centro['lng']],
        'zoom'     => $centro['zoom'],
        'pins'     => $pins,
        'ciudades' => cita_comparendo_ciudades(),
        'pinBlue'  => CITA_COMPARENDO_IMG_URI . 'elements/pin-blue.svg',
        'pinRed'   => CITA_COMPARENDO_IMG_URI . 'elements/pin-red.svg',
    ];
}

/**
 * Ancho del contenido embebido.
 */
function cita_comparendo_content_width()
{
    $GLOBALS['content_width'] = 1320;
}
add_action('after_setup_theme', 'cita_comparendo_content_width', 0);

/**
 * Clases extra del body.
 *
 * @param array $classes Clases actuales.
 * @return array
 */
function cita_comparendo_body_classes($classes)
{
    if (is_front_page()) {
        $classes[] = 'landing-page';
    }
    return $classes;
}
add_filter('body_class', 'cita_comparendo_body_classes');

/**
 * ACF: la base de datos es la única fuente de verdad.
 *
 * El grupo se importó desde acf-json y hoy se edita en el panel. Desactivamos
 * la carga/guardado de JSON para que los cambios del admin se reflejen de
 * inmediato en la web (sin que un archivo viejo los sobrescriba).
 */
add_filter('acf/settings/load_json', '__return_empty_array');
add_filter('acf/settings/save_json', '__return_false');
