<?php
/**
 * Tarjeta de post del blog.
 *
 * @package Cita_Comparendo
 */

$args = wp_parse_args($args ?? array(), array(
    'post_id'  => 0,
    'titulo'   => '',
    'extracto' => '',
    'imagen'   => '',
    'url'      => '#blog',
));

if ($args['post_id']) {
    $post_id  = (int) $args['post_id'];
    $titulo   = get_the_title($post_id);
    $extracto = get_the_excerpt($post_id);
    $url      = get_permalink($post_id);
    $imagen   = get_the_post_thumbnail_url($post_id, 'cita-card');
    if (! $imagen) {
        $imagen = CITA_COMPARENDO_IMG_URI . 'landings/home/blog-1.jpg';
    }
} else {
    $titulo   = $args['titulo'];
    $extracto = $args['extracto'];
    $url      = $args['url'];
    $imagen   = CITA_COMPARENDO_IMG_URI . 'landings/home/' . $args['imagen'];
}
?>
<article class="blog-card">
    <a class="blog-card__media d-block" href="<?php echo esc_url($url); ?>">
        <img class="blog-card__img" src="<?php echo esc_url($imagen); ?>"
            alt="<?php echo esc_attr($titulo); ?>" loading="lazy" width="600" height="400">
        <span class="blog-card__badge"><?php echo esc_html($titulo); ?></span>
    </a>
    <p class="blog-card__excerpt"><?php echo esc_html($extracto); ?></p>
    <a class="blog-card__link d-inline-flex align-items-center gap-2" href="<?php echo esc_url($url); ?>">
        <?php esc_html_e('Ver más', 'cita-comparendo'); ?>
        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
    </a>
</article>
