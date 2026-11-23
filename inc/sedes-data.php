<?php
/**
 * Datos que consume la landing.
 *
 * Contrato público (el frontend no consulta WordPress directamente):
 *
 * - cita_comparendo_sedes():       lista de sedes publicadas.
 * - cita_comparendo_ciudades():    ciudades con sedes publicadas + centro del mapa.
 * - cita_comparendo_zonas():       slug => etiqueta de las zonas.
 * - cita_comparendo_mapa_centro(): centro/zoom por defecto (Colombia).
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Compara dos cadenas sin distinguir mayúsculas ni tildes.
 *
 * @param string $a Primera cadena.
 * @param string $b Segunda cadena.
 * @return int
 */
function cita_comparendo_ordenar_texto($a, $b)
{
    $a = strtolower(remove_accents((string) $a));
    $b = strtolower(remove_accents((string) $b));

    return strcmp($a, $b);
}

/**
 * Convierte un valor de meta en float o null.
 *
 * @param mixed $valor Valor de entrada (puede traer coma decimal).
 * @return float|null
 */
function cita_comparendo_float_o_null($valor)
{
    if (is_null($valor) || is_bool($valor) || is_array($valor)) {
        return null;
    }

    $valor = trim(str_replace(',', '.', (string) $valor));

    return is_numeric($valor) ? (float) $valor : null;
}

/**
 * Primer término de una taxonomía para un post.
 *
 * @param int    $post_id  ID del post.
 * @param string $taxonomy Taxonomía.
 * @return WP_Term|null
 */
function cita_comparendo_primer_termino($post_id, $taxonomy)
{
    $terminos = get_the_terms($post_id, $taxonomy);

    if (empty($terminos) || is_wp_error($terminos)) {
        return null;
    }

    $terminos = array_values($terminos);

    return $terminos[0];
}

/**
 * Lee un post meta de texto sin riesgo de avisos por valores no escalares.
 *
 * @param int    $post_id ID del post.
 * @param string $clave   Clave del meta.
 * @return string
 */
function cita_comparendo_meta_texto($post_id, $clave)
{
    $valor = get_post_meta($post_id, $clave, true);

    if (is_array($valor)) {
        $valor = reset($valor);
    }

    return is_scalar($valor) ? (string) $valor : '';
}

/**
 * Normaliza el estado del CRM a 'activo', 'inactivo' o '' (desconocido).
 *
 * @param mixed $valor Valor de estado_sede_motor.
 * @return string activo|inactivo|''
 */
function cita_comparendo_estado_normalizado($valor)
{
    if (is_array($valor) || is_object($valor) || is_bool($valor) || is_null($valor)) {
        return '';
    }

    $valor = strtolower(remove_accents(trim((string) $valor)));

    if ('' === $valor) {
        return '';
    }

    $activos = array('activo', 'activa', 'abierta', 'habilitada', '1', 'true', 'si');
    $inactivos = array('inactivo', 'inactiva', 'cerrada', 'suspendida', 'deshabilitada', '0', 'false', 'no');

    if (in_array($valor, $activos, true)) {
        return 'activo';
    }

    if (in_array($valor, $inactivos, true)) {
        return 'inactivo';
    }

    return '';
}

/**
 * ¿El estado significa inactiva? Un valor desconocido no lo es.
 *
 * @param mixed $valor Valor de estado_sede_motor.
 * @return bool
 */
function cita_comparendo_estado_inactivo($valor)
{
    return 'inactivo' === cita_comparendo_estado_normalizado($valor);
}

/**
 * Registra una sola vez por request un estado desconocido (compatibilidad:
 * se sigue tratando como activo).
 *
 * @param string $valor Valor desconocido.
 * @return void
 */
function cita_comparendo_log_estado_desconocido($valor)
{
    static $vistos = array();

    $clave = strtolower(trim((string) $valor));

    if ('' === $clave || isset($vistos[$clave])) {
        return;
    }

    $vistos[$clave] = true;

    error_log('cita_comparendo: estado_sede_motor desconocido (se trata como activo): ' . sanitize_text_field((string) $valor));
}

/**
 * Sedes publicadas, ordenadas por ciudad y luego por nombre.
 *
 * Devuelve [] si todavía no hay sedes publicadas: el frontend tiene su propio
 * contenido de respaldo. Memoizado por request.
 *
 * @return array<int, array<string, mixed>>
 */
function cita_comparendo_sedes()
{
    static $cache = null;

    if (null !== $cache) {
        return $cache;
    }

    $posts = get_posts(array(
        'post_type'              => 'sede',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'title',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    ));

    if (empty($posts)) {
        $cache = array();

        return $cache;
    }

    $zonas = cita_comparendo_zonas();
    $sedes = array();

    foreach ($posts as $post) {
        $ciudad = cita_comparendo_primer_termino($post->ID, 'ciudad');
        $localidad = cita_comparendo_primer_termino($post->ID, 'localidad');
        $zona = cita_comparendo_primer_termino($post->ID, 'zona');

        $zona_slug = $zona ? (string) $zona->slug : '';
        $crm_id = cita_comparendo_meta_texto($post->ID, 'complemento_iframe');
        $estado = cita_comparendo_meta_texto($post->ID, 'estado_sede_motor');
        $estado_norm = cita_comparendo_estado_normalizado($estado);

        if ('' === $estado_norm && '' !== trim($estado)) {
            cita_comparendo_log_estado_desconocido($estado);
        }

        $sedes[] = array(
            'id'             => (int) $post->ID,
            'nombre'         => get_the_title($post),
            'slug'           => (string) $post->post_name,
            'direccion'      => cita_comparendo_meta_texto($post->ID, 'direccion'),
            'horario'        => cita_comparendo_meta_texto($post->ID, 'horario'),
            'telefono'       => cita_comparendo_meta_texto($post->ID, 'telefono'),
            'lat'            => cita_comparendo_float_o_null(cita_comparendo_meta_texto($post->ID, 'lat')),
            'lng'            => cita_comparendo_float_o_null(cita_comparendo_meta_texto($post->ID, 'lng')),
            'ciudad'         => $ciudad ? (string) $ciudad->name : '',
            'ciudad_slug'    => $ciudad ? (string) $ciudad->slug : '',
            'localidad'      => $localidad ? (string) $localidad->name : '',
            'localidad_slug' => $localidad ? (string) $localidad->slug : '',
            'zona'           => $zona_slug,
            'zona_label'     => $zona_slug ? (isset($zonas[$zona_slug]) ? $zonas[$zona_slug] : (string) $zona->name) : '',
            'activa'         => 'inactivo' !== $estado_norm,
            'sede_crm'       => '' !== $crm_id ? $crm_id : (string) $post->ID,
        );
    }

    usort($sedes, function ($a, $b) {
        $por_ciudad = cita_comparendo_ordenar_texto($a['ciudad'], $b['ciudad']);

        if (0 !== $por_ciudad) {
            return $por_ciudad;
        }

        $por_nombre = cita_comparendo_ordenar_texto($a['nombre'], $b['nombre']);

        return 0 !== $por_nombre ? $por_nombre : ($a['id'] <=> $b['id']);
    });

    $cache = $sedes;

    return $cache;
}

/**
 * Ciudades que tienen al menos una sede publicada. Memoizado por request.
 *
 * @return array<int, array<string, mixed>>
 */
function cita_comparendo_ciudades()
{
    static $cache = null;

    if (null !== $cache) {
        return $cache;
    }

    $ciudades = array();
    $slugs = array();

    foreach (cita_comparendo_sedes() as $sede) {
        if ('' !== $sede['ciudad_slug']) {
            $slugs[$sede['ciudad_slug']] = true;
        }
    }

    foreach (array_keys($slugs) as $slug) {
        $termino = get_term_by('slug', $slug, 'ciudad');

        if (! $termino || is_wp_error($termino)) {
            continue;
        }

        $zoom = (int) get_term_meta($termino->term_id, 'nivel_zoom', true);

        $ciudades[] = array(
            'slug'   => (string) $termino->slug,
            'nombre' => (string) $termino->name,
            'lat'    => cita_comparendo_float_o_null(get_term_meta($termino->term_id, 'latitud', true)),
            'lng'    => cita_comparendo_float_o_null(get_term_meta($termino->term_id, 'longitud', true)),
            'zoom'   => $zoom > 0 ? $zoom : 0,
        );
    }

    usort($ciudades, function ($a, $b) {
        return cita_comparendo_ordenar_texto($a['nombre'], $b['nombre']);
    });

    $cache = $ciudades;

    return $cache;
}

/**
 * Zonas disponibles como slug => etiqueta. Memoizado por request.
 *
 * Respeta el orden de las zonas por defecto; las zonas añadidas a mano van al
 * final, en orden alfabético.
 *
 * @return array<string, string>
 */
function cita_comparendo_zonas()
{
    static $cache = null;

    if (null !== $cache) {
        return $cache;
    }

    $default = function_exists('cita_comparendo_zonas_labels')
        ? cita_comparendo_zonas_labels()
        : array();

    $terminos = get_terms(array(
        'taxonomy'   => 'zona',
        'hide_empty' => false,
    ));

    if (empty($terminos) || is_wp_error($terminos)) {
        $cache = $default;

        return $cache;
    }

    $por_slug = array();
    foreach ($terminos as $termino) {
        $por_slug[(string) $termino->slug] = (string) $termino->name;
    }

    $zonas = array();

    foreach (array_keys($default) as $slug) {
        if (isset($por_slug[$slug])) {
            $zonas[$slug] = $por_slug[$slug];
            unset($por_slug[$slug]);
        }
    }

    uksort($por_slug, 'cita_comparendo_ordenar_texto');

    foreach ($por_slug as $slug => $nombre) {
        $zonas[$slug] = $nombre;
    }

    $cache = $zonas;

    return $cache;
}

/**
 * Centro y zoom por defecto del mapa (Colombia).
 *
 * @return array<string, float|int>
 */
function cita_comparendo_mapa_centro()
{
    return array(
        'lat'  => 4.5709,
        'lng'  => -74.2973,
        'zoom' => 5,
    );
}
