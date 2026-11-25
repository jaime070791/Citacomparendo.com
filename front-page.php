<?php
/**
 * Template Name: Inicio
 * Template Post Type: page
 *
 * Landing one-page.
 *
 * @package Cita_Comparendo
 */

get_header();
?>

<main id="contenido">
    <?php
    get_template_part('template-parts/hero');
    get_template_part('template-parts/stats');
    get_template_part('template-parts/sedes');
    get_template_part('template-parts/banner-instalaciones');
    get_template_part('template-parts/como-funciona');
    get_template_part('template-parts/testimonios');
    get_template_part('template-parts/faq');
    // get_template_part('template-parts/blog'); // oculto temporalmente
    ?>
</main>

<?php
get_footer();
