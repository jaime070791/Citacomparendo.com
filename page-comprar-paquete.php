<?php
/**
 * Template Name: Comprar paquete
 *
 * Compra de paquetes de leads para empresas. El formulario se verifica y se
 * envía por AJAX contra inc/paquetes.php.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

$nonce_formulario = wp_create_nonce('nonce-contact-form');

$redirect_gracias = trim((string) get_option('cita_redirect_gracias', ''));
if ('' === $redirect_gracias) {
    $redirect_gracias = home_url('/gracias-compra-paquete/');
}

wp_enqueue_script(
    'cita-paquetes',
    CITA_COMPARENDO_JS_URI . 'paquetes.js',
    array('cita-main'),
    cita_comparendo_asset_version('/assets/js/paquetes.js'),
    true
);

wp_localize_script('cita-paquetes', 'CITA_PAQUETES', array(
    'ajaxUrl'  => admin_url('admin-ajax.php'),
    'redirect' => $redirect_gracias,
    'i18n'     => array(
        'enviando'        => __('Enviando…', 'cita-comparendo'),
        'comprar'         => __('Comprar paquete', 'cita-comparendo'),
        'verificando'     => __('Consultando empresa…', 'cita-comparendo'),
        'empresaOk'       => __('Empresa encontrada. Revisa los datos y completa la compra.', 'cita-comparendo'),
        'empresaNueva'    => __('No encontramos una empresa con ese documento. Completa los datos para continuar.', 'cita-comparendo'),
        'errorRed'        => __('No pudimos enviar tu solicitud. Revisa tu conexión e inténtalo de nuevo.', 'cita-comparendo'),
        'verificaPrimero' => __('Verifica tu empresa para continuar con la compra.', 'cita-comparendo'),
    ),
));

$paquetes = array(
    array(
        'slug'      => 'pack-plus',
        'nombre'    => 'Pack Plus',
        'leads'     => '50',
        'precio'    => '$925.000',
        'resumen'   => __('Para empresas que están empezando a recibir solicitudes.', 'cita-comparendo'),
        'destacado' => false,
    ),
    array(
        'slug'      => 'pack-silver',
        'nombre'    => 'Pack Silver',
        'leads'     => '100',
        'precio'    => '$1.850.000',
        'resumen'   => __('El equilibrio entre alcance y presupuesto para crecer.', 'cita-comparendo'),
        'destacado' => true,
    ),
    array(
        'slug'      => 'pack-gold',
        'nombre'    => 'Pack Gold',
        'leads'     => '150',
        'precio'    => '$2.775.000',
        'resumen'   => __('Mayor volumen de usuarios para operaciones grandes.', 'cita-comparendo'),
        'destacado' => false,
    ),
);

$politica_url = get_privacy_policy_url();
if ('' === $politica_url) {
    $politica_url = home_url('/politica-de-privacidad/');
}

get_header();
?>

<main id="contenido">

    <header class="page-hero paquetes-hero">
        <div class="container">
            <nav class="sede-hero__migas" aria-label="<?php esc_attr_e('Ruta de navegación', 'cita-comparendo'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Inicio', 'cita-comparendo'); ?></a>
                <span aria-hidden="true">/</span>
                <span aria-current="page"><?php esc_html_e('Comprar paquete', 'cita-comparendo'); ?></span>
            </nav>

            <div class="row align-items-end g-4">
                <div class="col-12 col-lg-8">
                    <p class="eyebrow mb-2"><?php esc_html_e('Para empresas', 'cita-comparendo'); ?></p>
                    <h1 class="mb-3">
                        <?php esc_html_e('Elige el paquete ideal para tu empresa', 'cita-comparendo'); ?>
                    </h1>
                    <p class="paquetes-hero__texto mb-0">
                        <?php esc_html_e('Inscribe tu empresa y recibe usuarios interesados en el curso comparendo. Tú te encargas de la atención; nosotros de conectar a las personas con tu operación.', 'cita-comparendo'); ?>
                    </p>
                </div>
                <div class="col-12 col-lg-4">
                    <ul class="paquetes-hero__sellos list-unstyled mb-0">
                        <li><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> <?php esc_html_e('Pago en línea seguro', 'cita-comparendo'); ?></li>
                        <li><i class="fa-solid fa-bolt" aria-hidden="true"></i> <?php esc_html_e('Activación tras la validación', 'cita-comparendo'); ?></li>
                        <li><i class="fa-solid fa-headset" aria-hidden="true"></i> <?php esc_html_e('Acompañamiento comercial', 'cita-comparendo'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <section class="section-py" id="paquetes">
        <div class="container">
            <div class="row g-4 justify-content-center">

                <?php foreach ($paquetes as $paquete) : ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="pack-card h-100 d-flex flex-column<?php echo $paquete['destacado'] ? ' pack-card--featured' : ''; ?>"
                            data-paquete="<?php echo esc_attr($paquete['slug']); ?>">

                            <?php if ($paquete['destacado']) : ?>
                                <span class="pack-card__badge"><?php esc_html_e('Más elegido', 'cita-comparendo'); ?></span>
                            <?php endif; ?>

                            <h2 class="pack-card__nombre"><?php echo esc_html($paquete['nombre']); ?></h2>
                            <p class="pack-card__resumen"><?php echo esc_html($paquete['resumen']); ?></p>

                            <p class="pack-card__leads mb-0">
                                <span class="pack-card__numero"><?php echo esc_html($paquete['leads']); ?></span>
                                <span class="pack-card__unidad"><?php esc_html_e('leads', 'cita-comparendo'); ?></span>
                            </p>
                            <p class="pack-card__detalle"><?php esc_html_e('usuarios efectivos', 'cita-comparendo'); ?></p>

                            <p class="pack-card__precio mb-1"><?php echo esc_html($paquete['precio']); ?></p>
                            <p class="pack-card__detalle mb-4"><?php esc_html_e('$18.500 por usuario efectivo', 'cita-comparendo'); ?></p>

                            <button type="button" class="btn pack-card__cta mt-auto"
                                data-paquete="<?php echo esc_attr($paquete['slug']); ?>"
                                data-nombre="<?php echo esc_attr($paquete['nombre']); ?>">
                                <?php esc_html_e('Seleccionar paquete', 'cita-comparendo'); ?>
                            </button>
                        </article>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </section>

    <section class="section-py paquetes-form-seccion" id="comprar-paquete">
        <div class="container">
            <div class="row g-4 g-lg-5 justify-content-center align-items-start">

                <div class="col-12 col-lg-7">
                    <div class="paquete-form">
                        <h2 class="paquete-form__titulo"><?php esc_html_e('Completa la compra', 'cita-comparendo'); ?></h2>
                        <p class="paquete-form__intro">
                            <?php esc_html_e('Primero verificamos tu empresa con el número de documento. Si ya trabajas con nosotros, completamos tus datos automáticamente.', 'cita-comparendo'); ?>
                        </p>

                        <p class="paquete-form__elegido" id="paquete-elegido" role="status" aria-live="polite">
                            <?php esc_html_e('Aún no has elegido un paquete.', 'cita-comparendo'); ?>
                        </p>

                        <form id="compraPaqueteForm" class="paquete-form__form" method="post" novalidate>
                            <input type="hidden" name="nonceContactForm" value="<?php echo esc_attr($nonce_formulario); ?>">
                            <input type="hidden" name="action" value="SendFormToDatabaseAndAPI">

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="select-paquete"><?php esc_html_e('Paquete', 'cita-comparendo'); ?></label>
                                    <select class="form-select" id="select-paquete" name="paquete" required>
                                        <option value="" selected disabled><?php esc_html_e('Selecciona un paquete', 'cita-comparendo'); ?></option>
                                        <?php foreach ($paquetes as $paquete) : ?>
                                            <option value="<?php echo esc_attr($paquete['slug']); ?>">
                                                <?php echo esc_html($paquete['nombre'] . ' · ' . $paquete['leads'] . ' leads · ' . $paquete['precio']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-sm-5">
                                    <label class="form-label" for="tipo-documento"><?php esc_html_e('Tipo de documento', 'cita-comparendo'); ?></label>
                                    <select class="form-select" id="tipo-documento" name="tipo-documento" required>
                                        <option value="" selected disabled><?php esc_html_e('Selecciona', 'cita-comparendo'); ?></option>
                                        <option value="NIT">NIT</option>
                                        <option value="RUT">RUT</option>
                                    </select>
                                </div>

                                <div class="col-12 col-sm-7">
                                    <label class="form-label" for="numero-documento"><?php esc_html_e('Número de documento', 'cita-comparendo'); ?></label>
                                    <input type="text" class="form-control" id="numero-documento" name="numero-documento"
                                        inputmode="numeric" autocomplete="off" minlength="4" maxlength="40" required
                                        placeholder="<?php esc_attr_e('Ejemplo: 900123456', 'cita-comparendo'); ?>">
                                </div>
                            </div>

                            <p class="paquete-form__estado" id="paquete-verificacion" role="status" aria-live="polite"></p>

                            <div class="paquete-form__revelado" id="paquete-datos-empresa" hidden>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label" for="nombre-empresa"><?php esc_html_e('Nombre de la empresa', 'cita-comparendo'); ?></label>
                                        <input type="text" class="form-control" id="nombre-empresa" name="nombre-empresa"
                                            autocomplete="organization" maxlength="200" required>
                                        <p class="invalid-feedback" data-error="nombre-empresa"></p>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label class="form-label" for="Telefono-empresa"><?php esc_html_e('Teléfono', 'cita-comparendo'); ?></label>
                                        <input type="tel" class="form-control" id="Telefono-empresa" name="Telefono-empresa"
                                            autocomplete="tel" maxlength="40" required>
                                        <p class="invalid-feedback" data-error="Telefono-empresa"></p>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label class="form-label" for="email-empresa"><?php esc_html_e('Correo electrónico', 'cita-comparendo'); ?></label>
                                        <input type="email" class="form-control" id="email-empresa" name="email-empresa"
                                            autocomplete="email" maxlength="200" required>
                                        <p class="invalid-feedback" data-error="email-empresa"></p>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="descripcion"><?php esc_html_e('¿En qué ciudades operas?', 'cita-comparendo'); ?></label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                            maxlength="500" required
                                            placeholder="<?php esc_attr_e('Ejemplo: Medellín, Bogotá y Pereira', 'cita-comparendo'); ?>"></textarea>
                                        <p class="invalid-feedback" data-error="descripcion"></p>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check paquete-form__terminos">
                                            <input class="form-check-input" type="checkbox" value="1" id="terminos" name="terminos" required>
                                            <label class="form-check-label" for="terminos">
                                                <?php
                                                printf(
                                                    /* translators: %s: enlace a la política de privacidad. */
                                                    esc_html__('Acepto los %s y el tratamiento de mis datos personales.', 'cita-comparendo'),
                                                    '<a href="' . esc_url($politica_url) . '" target="_blank" rel="noopener">' . esc_html__('términos y condiciones', 'cita-comparendo') . '</a>'
                                                );
                                                ?>
                                            </label>
                                            <p class="invalid-feedback" data-error="terminos"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="paquete-alerta" id="paquete-alerta" role="alert" hidden></div>

                            <button type="submit" class="btn btn-warning paquete-form__submit" id="paquete-submit">
                                <span class="spinner-border spinner-border-sm me-2" id="paquete-spinner" aria-hidden="true" hidden></span>
                                <span id="paquete-submit-texto"><?php esc_html_e('Comprar paquete', 'cita-comparendo'); ?></span>
                            </button>

                            <p class="paquete-form__nota">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                <?php esc_html_e('Después de enviar la solicitud te llevaremos a la pasarela de pago segura.', 'cita-comparendo'); ?>
                            </p>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <aside class="paquete-aside" aria-labelledby="paquete-aside-titulo">
                        <h2 class="paquete-aside__titulo" id="paquete-aside-titulo">
                            <?php esc_html_e('¿Qué incluye tu paquete?', 'cita-comparendo'); ?>
                        </h2>
                        <ul class="paquete-aside__lista list-unstyled mb-0">
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?php esc_html_e('Usuarios efectivos para tu empresa.', 'cita-comparendo'); ?></li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?php esc_html_e('Panel para gestionar las solicitudes.', 'cita-comparendo'); ?></li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?php esc_html_e('Acompañamiento del equipo comercial.', 'cita-comparendo'); ?></li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?php esc_html_e('Activación después de validar el pago.', 'cita-comparendo'); ?></li>
                        </ul>

                        <div class="paquete-aside__ayuda">
                            <p class="mb-2"><?php esc_html_e('¿Tienes dudas antes de comprar?', 'cita-comparendo'); ?></p>
                            <a class="btn btn-outline-light" href="<?php echo esc_url(cita_whatsapp_url()); ?>" target="_blank" rel="noopener">
                                <i class="fa-brands fa-whatsapp me-1" aria-hidden="true"></i>
                                <?php esc_html_e('Hablar con un asesor', 'cita-comparendo'); ?>
                            </a>
                        </div>
                    </aside>
                </div>

            </div>
        </div>
    </section>

</main>

<?php
get_footer();
