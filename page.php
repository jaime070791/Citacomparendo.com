<?php
/**
 * Página estática.
 *
 * @package Cita_Comparendo
 */

get_header();
?>

<div class="page-hero">
    <div class="container">
        <h1><?php echo esc_html(get_the_title()); ?></h1>
    </div>
</div>

<main id="contenido" class="section-py">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('entry-content'); ?>>
                <?php the_content(); ?>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<?php
get_footer();
