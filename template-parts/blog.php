<?php
/**
 * Blog de la landing: 4 entradas recientes o el diseño por defecto.
 *
 * @package Cita_Comparendo
 */

$blog_query = new WP_Query(array(
    'posts_per_page'      => 4,
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
));
?>
<section id="blog" class="section-py">
    <div class="container">

        <div class="d-flex align-items-center justify-content-center gap-3 gap-md-4 mb-4 mb-lg-5" data-reveal>
            <span class="section-title__line" aria-hidden="true"></span>
            <h2 class="section-title"><?php echo esc_html(cita_field('blog_titulo', 'Blog')); ?></h2>
            <span class="section-title__line" aria-hidden="true"></span>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 g-lg-4">
            <?php if ($blog_query->have_posts()) : ?>
                <?php while ($blog_query->have_posts()) : $blog_query->the_post(); ?>
                    <div class="col" data-reveal>
                        <?php get_template_part('template-parts/card-post', null, array('post_id' => get_the_ID())); ?>
                    </div>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <?php foreach (cita_default_posts() as $index => $post) : ?>
                    <div class="col" data-reveal style="--reveal-delay:<?php echo esc_attr(70 * $index); ?>ms;">
                        <?php get_template_part('template-parts/card-post', null, $post); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</section>
