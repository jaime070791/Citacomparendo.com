<?php
/**
 * Navegación one-page: todos los enlaces apuntan a secciones de la landing.
 *
 * @package Cita_Comparendo
 */

$menu_items = array(
    '#inicio'         => 'Calcular mi descuento',
    '#sedes'          => 'Sedes y horarios',
    '#como-funciona'  => '¿Cómo funciona?',
    '#experiencias'   => 'Experiencias',
    // '#blog'        => 'Blog', // oculto temporalmente
);
?>
<header class="site-header">
    <nav class="navbar navbar-expand-lg py-0" aria-label="<?php esc_attr_e('Navegación principal', 'cita-comparendo'); ?>">
        <div class="container">

            <a class="site-header__logo navbar-brand p-0 me-3" href="#inicio" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <img src="<?php echo esc_url(CITA_COMPARENDO_IMG_URI . 'logos/logo-cita.png'); ?>"
                        alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                <?php endif; ?>
            </a>

            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                data-bs-target="#nav-principal" aria-controls="nav-principal" aria-expanded="false"
                aria-label="<?php esc_attr_e('Abrir menú', 'cita-comparendo'); ?>">
                <span class="navbar-toggler-icon" aria-hidden="true"></span>
            </button>

            <div class="collapse navbar-collapse" id="nav-principal">
                <ul class="navbar-nav mx-lg-auto align-items-lg-center mb-2 mb-lg-0" id="nav-principal-list">
                    <?php foreach ($menu_items as $href => $label) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo esc_attr($href); ?>"><?php echo esc_html($label); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <a class="btn btn-warning d-none d-lg-inline-flex" href="#sedes">
                    <?php esc_html_e('Agendar mi curso', 'cita-comparendo'); ?>
                </a>

                <a class="btn btn-warning w-100 mt-2 d-lg-none" href="#sedes">
                    <?php esc_html_e('Agendar mi curso', 'cita-comparendo'); ?>
                </a>
            </div>

        </div>
    </nav>
</header>
