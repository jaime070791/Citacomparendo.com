<?php
/**
 * Resultados de búsqueda.
 *
 * @package Cita_Comparendo
 */

get_header();
?>

<div class="page-hero">
    <div class="container">
        <h1>
            <?php
            printf(
                /* translators: %s: término buscado. */
                esc_html__('Resultados para: %s', 'cita-comparendo'),
                '<span class="text-yellow">' . esc_html(get_search_query()) . '</span>'
            );
            ?>
        </h1>
    </div>
</div>

<main id="contenido" class="section-py">
    <div class="container">
        <?php if (have_posts()) : ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php while (have_posts()) : the_post(); ?>
                    <div class="col">
                        <article <?php post_class('card h-100'); ?>>
                            <div class="card-body d-flex flex-column">
                                <h2 class="h5 mb-2">
                                    <a class="text-navy" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                <p class="flex-grow-1"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
                                <a class="blog-card__link d-inline-flex align-items-center gap-2" href="<?php the_permalink(); ?>">
                                    <?php esc_html_e('Ver más', 'cita-comparendo'); ?>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="mt-4"><?php the_posts_pagination(array('mid_size' => 1)); ?></div>
        <?php else : ?>
            <div class="row justify-content-center text-center">
                <div class="col-12 col-lg-6">
                    <p class="mb-4"><?php esc_html_e('No encontramos resultados para tu búsqueda.', 'cita-comparendo'); ?></p>
                    <?php get_search_form(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
