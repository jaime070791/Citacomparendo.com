<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <script>
        document.documentElement.classList.add('js');
    </script>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?> data-bs-spy="scroll" data-bs-target="#nav-principal" data-bs-offset="110" tabindex="0">
    <?php wp_body_open(); ?>

    <?php get_template_part('template-parts/nav'); ?>
