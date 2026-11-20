<?php
/**
 * Listado de entradas (fallback de WordPress).
 *
 * @package Cita_Comparendo
 */

get_header();

$titulo = get_the_archive_title();
if (! $titulo) {
    $titulo = get_bloginfo('name');
}
?>

<div class="page-hero">
    <div class="container">
        <h1><?php echo esc_html(wp_strip_all_tags($titulo)); ?></h1>
    </div>
</div>

<main id="contenido" class="section-py">
    <div class="container blog-list">
        <?php if (have_posts()) : ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php while (have_posts()) : the_post(); ?>
                    <div class="col">
                        <article <?php post_class('card h-100'); ?>>
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('cita-card', array('class' => 'card-img-top', 'loading' => 'lazy')); ?>
                                </a>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <p class="text-navy small fw-semibold mb-1"><?php echo esc_html(get_the_date('d M, Y')); ?></p>
                                <h2 class="h5 mb-2">
                                    <a class="text-navy" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                <p class="flex-grow-1"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
                                <a class="blog-card__link d-inline-flex align-items-center gap-2" href="<?php the_permalink(); ?>">
                                    <?php esc_html_e('Ver más', 'cita-comparendo'); ?>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="mt-4">
                <?php the_posts_pagination(array('mid_size' => 1, 'screen_reader_text' => __('Navegación de entradas', 'cita-comparendo'))); ?>
            </div>
        <?php else : ?>
            <p class="text-center py-5"><?php esc_html_e('No se encontraron entradas.', 'cita-comparendo'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
