<?php
/**
 * Datos de testimonios para la landing.
 *
 * Contrato:
 * [
 *   ['id' => int, 'nombre' => string, 'rol' => string, 'rating' => string,
 *    'texto' => string, 'foto' => string, 'audio' => string],
 * ]
 *
 * Sin testimonios publicados devuelve []: el frontend usa
 * cita_default_testimonios() como respaldo.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Testimonios publicados, ordenados por `orden` (vacío al final) y fecha.
 *
 * @return array<int, array<string, mixed>>
 */
function cita_comparendo_testimonios()
{
    static $cache = null;

    if (null !== $cache) {
        return $cache;
    }

    $posts = get_posts(array(
        'post_type'              => 'testimonio',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ));

    if (empty($posts)) {
        $cache = array();

        return $cache;
    }

    usort($posts, function ($a, $b) {
        $orden_a = get_post_meta($a->ID, 'orden', true);
        $orden_b = get_post_meta($b->ID, 'orden', true);

        $sin_a = ('' === $orden_a || ! is_numeric($orden_a));
        $sin_b = ('' === $orden_b || ! is_numeric($orden_b));

        // Sin orden, al final.
        if ($sin_a !== $sin_b) {
            return $sin_a ? 1 : -1;
        }

        if (! $sin_a) {
            $comparacion = (int) $orden_a <=> (int) $orden_b;

            if (0 !== $comparacion) {
                return $comparacion;
            }
        }

        // Más recientes primero.
        return strcmp((string) $b->post_date, (string) $a->post_date);
    });

    $testimonios = array();

    foreach ($posts as $post) {
        $audio = get_post_meta($post->ID, 'audio', true);
        $audio_url = '';

        if (is_scalar($audio) && '' !== trim((string) $audio)) {
            $audio = trim((string) $audio);

            $audio_url = ctype_digit($audio)
                ? (string) wp_get_attachment_url((int) $audio)
                : esc_url_raw($audio);
        }

        $foto = get_the_post_thumbnail_url($post->ID, 'cita-card');

        if (! $foto) {
            $foto = get_the_post_thumbnail_url($post->ID, 'medium');
        }

        $testimonios[] = array(
            'id'     => (int) $post->ID,
            'nombre' => get_the_title($post),
            'rol'    => sanitize_text_field((string) get_post_meta($post->ID, 'rol', true)),
            'rating' => function_exists('cita_comparendo_normalizar_rating')
                ? cita_comparendo_normalizar_rating(get_post_meta($post->ID, 'rating', true))
                : (string) get_post_meta($post->ID, 'rating', true),
            'texto'  => trim(wp_strip_all_tags((string) $post->post_content)),
            'foto'   => $foto ? (string) $foto : '',
            'audio'  => $audio_url ? (string) $audio_url : '',
        );
    }

    $cache = $testimonios;

    return $cache;
}
