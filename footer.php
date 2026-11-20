    <?php
    $footer_logos = array('simit.svg', 'runt.svg', 'mintransporte.svg', 'visa.svg', 'mastercard.svg', 'pse.svg');
    $footer_nav   = array(
        '#inicio'        => 'Calcular mi descuento',
        '#sedes'         => 'Sedes y horarios',
        '#como-funciona' => '¿Cómo funciona?',
        '#experiencias'  => 'Experiencias',
        // '#blog'       => 'Blog', // oculto temporalmente
    );
    ?>

    <footer id="contacto" class="site-footer">
        <div class="container">
            <div class="row align-items-center g-4">

                <div class="col-12 col-lg-3">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="site-footer__logo-chip"
                        aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        <?php if (has_custom_logo()) : ?>
                            <?php the_custom_logo(); ?>
                        <?php else : ?>
                            <img class="site-footer__logo" src="<?php echo esc_url(CITA_COMPARENDO_IMG_URI . 'logos/logo-cita.png'); ?>"
                                alt="<?php echo esc_attr(get_bloginfo('name')); ?>" loading="lazy">
                        <?php endif; ?>
                    </a>
                </div>

                <div class="col-12 col-lg-4">
                    <nav class="site-footer__nav" aria-label="<?php esc_attr_e('Navegación del footer', 'cita-comparendo'); ?>">
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                            <?php foreach ($footer_nav as $href => $label) : ?>
                                <li>
                                    <a class="site-footer__link" href="<?php echo esc_attr($href); ?>">
                                        <?php echo esc_html($label); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                </div>

                <div class="col-lg-1 d-none d-lg-flex justify-content-center">
                    <div class="site-footer__divider"></div>
                </div>

                <div class="col-12 col-lg-4">
                    <p class="site-footer__legal text-lg-end mb-2">
                        <?php echo esc_html(cita_field('footer_legal', 'Todas nuestras sedes aliadas son avalados y vigilados por estas entidades')); ?>
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-2 gap-lg-3 justify-content-lg-end site-footer__logos">
                        <?php foreach ($footer_logos as $logo) : ?>
                            <img src="<?php echo esc_url(CITA_COMPARENDO_IMG_URI . 'logos/' . $logo); ?>" alt="" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </footer>

    <?php wp_footer(); ?>
    </body>

    </html>
