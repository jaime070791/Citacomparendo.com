<?php
/**
 * CPT `testimonio` y su meta box nativa (sin ACF).
 *
 * Título = nombre de la persona, contenido = texto del testimonio, imagen
 * destacada = foto. Campos: rol, rating, audio y orden.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registra el post type `testimonio`.
 *
 * @return void
 */
function cita_comparendo_register_testimonio_cpt()
{
    register_post_type('testimonio', array(
        'labels' => array(
            'name'               => __('Testimonios', 'cita-comparendo'),
            'singular_name'      => __('Testimonio', 'cita-comparendo'),
            'menu_name'          => __('Testimonios', 'cita-comparendo'),
            'name_admin_bar'     => __('Testimonio', 'cita-comparendo'),
            'add_new'            => __('Añadir testimonio', 'cita-comparendo'),
            'add_new_item'       => __('Añadir nuevo testimonio', 'cita-comparendo'),
            'edit_item'          => __('Editar testimonio', 'cita-comparendo'),
            'new_item'           => __('Nuevo testimonio', 'cita-comparendo'),
            'view_item'          => __('Ver testimonio', 'cita-comparendo'),
            'search_items'       => __('Buscar testimonios', 'cita-comparendo'),
            'not_found'          => __('No se encontraron testimonios', 'cita-comparendo'),
            'not_found_in_trash' => __('No hay testimonios en la papelera', 'cita-comparendo'),
            'all_items'          => __('Todos los testimonios', 'cita-comparendo'),
            'featured_image'     => __('Foto de la persona', 'cita-comparendo'),
        ),
        'description'         => __('Testimonios de la landing.', 'cita-comparendo'),
        'public'              => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_rest'        => true,
        'query_var'           => false,
        'rewrite'             => false,
        'menu_position'       => 22,
        'menu_icon'           => 'dashicons-format-quote',
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'supports'            => array('title', 'editor', 'thumbnail'),
    ));
}
add_action('init', 'cita_comparendo_register_testimonio_cpt', 5);

/**
 * Carga la biblioteca de medios en la pantalla del testimonio.
 *
 * @param string $hook_suffix Pantalla actual.
 * @return void
 */
function cita_comparendo_testimonio_admin_assets($hook_suffix)
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (! $screen || 'testimonio' !== $screen->post_type) {
        return;
    }

    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'cita_comparendo_testimonio_admin_assets');

/**
 * Normaliza el rating a una décima con coma (0–5).
 *
 * @param mixed $valor Valor de entrada (admite punto o coma).
 * @return string Ej. "4,8"; vacío si no es numérico.
 */
function cita_comparendo_normalizar_rating($valor)
{
    if (! is_scalar($valor)) {
        return '';
    }

    $valor = trim(str_replace(',', '.', (string) $valor));

    if ('' === $valor || ! is_numeric($valor)) {
        return '';
    }

    $numero = (float) $valor;
    $numero = max(0.0, min(5.0, $numero));

    return number_format($numero, 1, ',', '');
}

/**
 * Sanitiza el audio: ID de adjunto (dígitos) o URL.
 *
 * @param mixed $valor Valor de entrada.
 * @return string
 */
function cita_comparendo_sanitizar_audio($valor)
{
    if (! is_scalar($valor)) {
        return '';
    }

    $valor = trim((string) $valor);

    if ('' === $valor) {
        return '';
    }

    if (ctype_digit($valor)) {
        return (string) absint($valor);
    }

    return esc_url_raw($valor);
}

/**
 * Registra la meta box.
 *
 * @return void
 */
function cita_comparendo_add_testimonio_meta_box()
{
    add_meta_box(
        'cita-testimonio-datos',
        __('Datos del testimonio', 'cita-comparendo'),
        'cita_comparendo_render_testimonio_meta_box',
        'testimonio',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_testimonio', 'cita_comparendo_add_testimonio_meta_box');

/**
 * Pinta la meta box.
 *
 * @param WP_Post $post Post que se edita.
 * @return void
 */
function cita_comparendo_render_testimonio_meta_box($post)
{
    $rol = (string) get_post_meta($post->ID, 'rol', true);
    $rating = (string) get_post_meta($post->ID, 'rating', true);
    $audio = (string) get_post_meta($post->ID, 'audio', true);
    $orden = (string) get_post_meta($post->ID, 'orden', true);

    wp_nonce_field('cita_testimonio_meta', 'cita_testimonio_meta_nonce');

    $preview = '';
    if ('' !== $audio) {
        $preview = ctype_digit($audio)
            ? (string) wp_get_attachment_url((int) $audio)
            : $audio;
    }
    ?>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="cita-testimonio-rol"><?php esc_html_e('Rol / ocupación', 'cita-comparendo'); ?></label></th>
                <td>
                    <input type="text" id="cita-testimonio-rol" name="cita-testimonio-rol" class="regular-text" value="<?php echo esc_attr($rol); ?>" placeholder="<?php esc_attr_e('Estudiante, Motociclista', 'cita-comparendo'); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cita-testimonio-rating"><?php esc_html_e('Calificación', 'cita-comparendo'); ?></label></th>
                <td>
                    <input type="text" id="cita-testimonio-rating" name="cita-testimonio-rating" class="small-text" value="<?php echo esc_attr($rating); ?>" placeholder="4,8">
                    <p class="description"><?php esc_html_e('De 0 a 5. Se acepta coma o punto; se guarda con una décima (ej. 4,8).', 'cita-comparendo'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cita-testimonio-audio"><?php esc_html_e('Audio', 'cita-comparendo'); ?></label></th>
                <td>
                    <input type="text" id="cita-testimonio-audio" name="cita-testimonio-audio" class="regular-text" value="<?php echo esc_attr($audio); ?>">
                    <button type="button" class="button" id="cita-testimonio-audio-btn"><?php esc_html_e('Seleccionar audio', 'cita-comparendo'); ?></button>
                    <button type="button" class="button-link" id="cita-testimonio-audio-quitar"><?php esc_html_e('Quitar', 'cita-comparendo'); ?></button>
                    <p class="description" id="cita-testimonio-audio-preview"><?php echo esc_html($preview); ?></p>
                    <p class="description"><?php esc_html_e('URL del audio o ID de adjunto de la biblioteca de medios.', 'cita-comparendo'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cita-testimonio-orden"><?php esc_html_e('Orden', 'cita-comparendo'); ?></label></th>
                <td>
                    <input type="number" id="cita-testimonio-orden" name="cita-testimonio-orden" class="small-text" min="0" step="1" value="<?php echo esc_attr($orden); ?>">
                    <p class="description"><?php esc_html_e('Opcional. Menor número aparece primero; sin orden van al final.', 'cita-comparendo'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <script>
    (function () {
        var campo = document.getElementById('cita-testimonio-audio');
        var boton = document.getElementById('cita-testimonio-audio-btn');
        var quitar = document.getElementById('cita-testimonio-audio-quitar');
        var preview = document.getElementById('cita-testimonio-audio-preview');
        var marco = null;

        if (!campo || !boton || !quitar || !window.wp || !wp.media) {
            return;
        }

        boton.addEventListener('click', function (evento) {
            evento.preventDefault();

            if (!marco) {
                marco = wp.media({
                    title: <?php echo wp_json_encode(__('Elegir audio del testimonio', 'cita-comparendo')); ?>,
                    button: { text: <?php echo wp_json_encode(__('Usar este audio', 'cita-comparendo')); ?> },
                    library: { type: 'audio' },
                    multiple: false
                });

                marco.on('select', function () {
                    var adjunto = marco.state().get('selection').first().toJSON();
                    campo.value = adjunto.id;
                    if (preview) {
                        preview.textContent = adjunto.url || adjunto.filename || '';
                    }
                });
            }

            marco.open();
        });

        quitar.addEventListener('click', function (evento) {
            evento.preventDefault();
            campo.value = '';
            if (preview) {
                preview.textContent = '';
            }
        });
    })();
    </script>
    <?php
}

/**
 * Guarda la meta box.
 *
 * @param int $post_id ID del post.
 * @return void
 */
function cita_comparendo_save_testimonio_meta($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    if (! isset($_POST['cita_testimonio_meta_nonce'])
        || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cita_testimonio_meta_nonce'])), 'cita_testimonio_meta')
    ) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $rol = isset($_POST['cita-testimonio-rol']) ? sanitize_text_field(wp_unslash($_POST['cita-testimonio-rol'])) : '';
    $rating = isset($_POST['cita-testimonio-rating']) ? cita_comparendo_normalizar_rating(wp_unslash($_POST['cita-testimonio-rating'])) : '';
    $audio = isset($_POST['cita-testimonio-audio']) ? cita_comparendo_sanitizar_audio(wp_unslash($_POST['cita-testimonio-audio'])) : '';
    $orden = isset($_POST['cita-testimonio-orden']) ? trim((string) wp_unslash($_POST['cita-testimonio-orden'])) : '';

    if ('' === $rol) {
        delete_post_meta($post_id, 'rol');
    } else {
        update_post_meta($post_id, 'rol', $rol);
    }

    if ('' === $rating) {
        delete_post_meta($post_id, 'rating');
    } else {
        update_post_meta($post_id, 'rating', $rating);
    }

    if ('' === $audio) {
        delete_post_meta($post_id, 'audio');
    } else {
        update_post_meta($post_id, 'audio', $audio);
    }

    if ('' === $orden) {
        delete_post_meta($post_id, 'orden');
    } else {
        update_post_meta($post_id, 'orden', (string) absint($orden));
    }
}
add_action('save_post_testimonio', 'cita_comparendo_save_testimonio_meta');
