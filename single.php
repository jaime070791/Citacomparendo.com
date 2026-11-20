<?php
/**
 * Entrada individual.
 *
 * @package Cita_Comparendo
 */

get_header();
?>

<main id="contenido">
    <?php while (have_posts()) : the_post(); ?>

        <div class="page-hero">
            <div class="container">
                <p class="text-yellow small fw-semibold mb-2"><?php echo esc_html(get_the_date('d M, Y')); ?></p>
                <h1><?php the_title(); ?></h1>
            </div>
        </div>

        <article <?php post_class('section-py'); ?>>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-9">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="mb-4">
                                <?php the_post_thumbnail('cita-wide', array('class' => 'img-fluid rounded-cita', 'loading' => 'lazy')); ?>
                            </div>
                        <?php endif; ?>

                        <div class="entry-content">
                            <?php the_content(); ?>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mt-5">
                            <a class="btn btn-primary" href="<?php echo esc_url(home_url('/')); ?>">
                                <?php esc_html_e('Volver al blog', 'cita-comparendo'); ?>
                            </a>
                            <a class="btn btn-warning" href="<?php echo esc_url(cita_whatsapp_url()); ?>" target="_blank" rel="noopener">
                                <?php esc_html_e('Agendar mi curso', 'cita-comparendo'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </article>

    <?php endwhile; ?>
</main>

<?php
get_footer();
