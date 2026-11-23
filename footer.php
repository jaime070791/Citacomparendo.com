    <?php
    $footer_logos = array('simit.svg', 'runt.svg', 'mintransporte.svg', 'visa.svg', 'mastercard.svg', 'pse.svg');
    $footer_nav   = array(
        '#inicio'        => 'Calcular mi descuento',
        '#sedes'         => 'Sedes y horarios',
        '#como-funciona' => '¿Cómo funciona?',
        '#experiencias'  => 'Experiencias',
        // '#blog'       => 'Blog', // oculto temporalmente
    );

    // Formulario de agendamiento del CRM (modal #modal-load).
    $load_crm      = function_exists('cita_comparendo_crm_url') ? (string) cita_comparendo_crm_url() : 'https://crm.motordigital.co';
    $load_slug     = function_exists('cita_comparendo_formulario_slug') ? trim((string) cita_comparendo_formulario_slug()) : '';
    $load_redirect = function_exists('cita_comparendo_redirect') ? (string) cita_comparendo_redirect() : '';
    $load_script   = untrailingslashit($load_crm) . '/assets/js/loadclub.js';
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

    <?php if ('' !== $load_slug) : ?>
        <div class="modal fade modal-load" id="modal-load" tabindex="-1" aria-labelledby="modal-load-titulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-load__dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="modal-load-titulo">
                            <?php esc_html_e('Agenda tu curso comparendo', 'cita-comparendo'); ?>
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="<?php esc_attr_e('Cerrar', 'cita-comparendo'); ?>"></button>
                    </div>
                    <div class="modal-body">
                        <div id="loadCalendar" class="modal-load__calendar"></div>
                    </div>
                </div>
            </div>
        </div>

        <script src="<?php echo esc_url($load_script); ?>"
                url="<?php echo esc_url(untrailingslashit($load_crm)); ?>"
                formulario="<?php echo esc_attr($load_slug); ?>"
                dom_content="#loadCalendar"
                dom_listener=".sede-agendar"
                redirect="<?php echo esc_url($load_redirect); ?>"
                defer></script>
    <?php else : ?>
        <div class="modal fade modal-load" id="modal-load-aviso" tabindex="-1" aria-labelledby="modal-load-aviso-titulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="modal-load-aviso-titulo">
                            <?php esc_html_e('Formulario no disponible', 'cita-comparendo'); ?>
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="<?php esc_attr_e('Cerrar', 'cita-comparendo'); ?>"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            <?php esc_html_e('El formulario no está configurado. Escríbenos y te ayudamos a agendar tu curso.', 'cita-comparendo'); ?>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-warning" href="<?php echo esc_url(cita_whatsapp_url()); ?>" target="_blank" rel="noopener">
                            <i class="fa-brands fa-whatsapp me-1" aria-hidden="true"></i>
                            <?php esc_html_e('Escribir por WhatsApp', 'cita-comparendo'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php wp_footer(); ?>
    </body>

    </html>
