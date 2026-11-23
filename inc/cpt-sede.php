<?php
/**
 * CPT `sede` y taxonomías `ciudad`, `localidad` y `zona`.
 *
 * Las sedes se sincronizan desde el CRM (ver inc/sync-sedes.php) pero se
 * editan en WordPress: la sincronización solo escribe los campos que trae la
 * API y nunca borra el contenido del editor.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registra el post type `sede` y sus taxonomías.
 *
 * @return void
 */
function cita_comparendo_register_sede_cpt()
{
    register_post_type('sede', array(
        'labels' => array(
            'name'               => __('Sedes', 'cita-comparendo'),
            'singular_name'      => __('Sede', 'cita-comparendo'),
            'menu_name'          => __('Sedes', 'cita-comparendo'),
            'name_admin_bar'     => __('Sede', 'cita-comparendo'),
            'add_new'            => __('Añadir sede', 'cita-comparendo'),
            'add_new_item'       => __('Añadir nueva sede', 'cita-comparendo'),
            'edit_item'          => __('Editar sede', 'cita-comparendo'),
            'new_item'           => __('Nueva sede', 'cita-comparendo'),
            'view_item'          => __('Ver sede', 'cita-comparendo'),
            'view_items'         => __('Ver sedes', 'cita-comparendo'),
            'search_items'       => __('Buscar sedes', 'cita-comparendo'),
            'not_found'          => __('No se encontraron sedes', 'cita-comparendo'),
            'not_found_in_trash' => __('No hay sedes en la papelera', 'cita-comparendo'),
            'all_items'          => __('Todas las sedes', 'cita-comparendo'),
            'archives'           => __('Archivo de sedes', 'cita-comparendo'),
            'attributes'         => __('Atributos de la sede', 'cita-comparendo'),
            'featured_image'     => __('Imagen de la sede', 'cita-comparendo'),
        ),
        'description'         => __('Sedes donde se realiza el curso comparendo.', 'cita-comparendo'),
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 21,
        'menu_icon'           => 'dashicons-location-alt',
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
        'taxonomies'          => array('ciudad', 'localidad', 'zona'),
        'rewrite'             => array(
            'slug'       => 'sede',
            'with_front' => false,
        ),
    ));

    register_taxonomy('ciudad', array('sede'), array(
        'labels' => array(
            'name'              => __('Ciudades', 'cita-comparendo'),
            'singular_name'     => __('Ciudad', 'cita-comparendo'),
            'search_items'      => __('Buscar ciudades', 'cita-comparendo'),
            'all_items'         => __('Todas las ciudades', 'cita-comparendo'),
            'parent_item'       => __('Ciudad superior', 'cita-comparendo'),
            'parent_item_colon' => __('Ciudad superior:', 'cita-comparendo'),
            'edit_item'         => __('Editar ciudad', 'cita-comparendo'),
            'update_item'       => __('Actualizar ciudad', 'cita-comparendo'),
            'add_new_item'      => __('Añadir nueva ciudad', 'cita-comparendo'),
            'new_item_name'     => __('Nombre de la nueva ciudad', 'cita-comparendo'),
            'menu_name'         => __('Ciudades', 'cita-comparendo'),
            'back_to_items'     => __('Volver a las ciudades', 'cita-comparendo'),
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'ciudad', 'with_front' => false),
    ));

    register_taxonomy('localidad', array('sede'), array(
        'labels' => array(
            'name'              => __('Localidades', 'cita-comparendo'),
            'singular_name'     => __('Localidad', 'cita-comparendo'),
            'search_items'      => __('Buscar localidades', 'cita-comparendo'),
            'all_items'         => __('Todas las localidades', 'cita-comparendo'),
            'parent_item'       => __('Localidad superior', 'cita-comparendo'),
            'parent_item_colon' => __('Localidad superior:', 'cita-comparendo'),
            'edit_item'         => __('Editar localidad', 'cita-comparendo'),
            'update_item'       => __('Actualizar localidad', 'cita-comparendo'),
            'add_new_item'      => __('Añadir nueva localidad', 'cita-comparendo'),
            'new_item_name'     => __('Nombre de la nueva localidad', 'cita-comparendo'),
            'menu_name'         => __('Localidades', 'cita-comparendo'),
            'back_to_items'     => __('Volver a las localidades', 'cita-comparendo'),
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'localidad', 'with_front' => false),
    ));

    // La zona es una clasificación de marketing/UX: se asigna a mano en
    // WordPress porque la API del CRM no la trae.
    register_taxonomy('zona', array('sede'), array(
        'labels' => array(
            'name'          => __('Zonas', 'cita-comparendo'),
            'singular_name' => __('Zona', 'cita-comparendo'),
            'search_items'  => __('Buscar zonas', 'cita-comparendo'),
            'all_items'     => __('Todas las zonas', 'cita-comparendo'),
            'edit_item'     => __('Editar zona', 'cita-comparendo'),
            'update_item'   => __('Actualizar zona', 'cita-comparendo'),
            'add_new_item'  => __('Añadir nueva zona', 'cita-comparendo'),
            'new_item_name' => __('Nombre de la nueva zona', 'cita-comparendo'),
            'menu_name'     => __('Zonas', 'cita-comparendo'),
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'zona', 'with_front' => false),
    ));
}
add_action('init', 'cita_comparendo_register_sede_cpt', 5);

/**
 * Zonas por defecto (slug => etiqueta visible).
 *
 * Es la única fuente de verdad: la usan los términos que se crean al activar
 * el tema y el fallback de cita_comparendo_zonas().
 *
 * @return array<string, string>
 */
function cita_comparendo_zonas_labels()
{
    return array(
        'norte'     => __('Norte', 'cita-comparendo'),
        'sur'       => __('Sur', 'cita-comparendo'),
        'centro'    => __('Centro', 'cita-comparendo'),
        'occidente' => __('Occidente', 'cita-comparendo'),
        'oriente'   => __('Oriente', 'cita-comparendo'),
    );
}



/**
 * Refresca las reglas de reescritura al activar el tema.
 *
 * after_switch_theme corre en init:99, después de registrar el CPT (init:5),
 * así que /sede/{slug}/ funciona sin volver a guardar los permalinks.
 *
 * @return void
 */
function cita_comparendo_flush_rewrite_rules()
{
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'cita_comparendo_flush_rewrite_rules');

/**
 * Limpia las zonas globales antiguas (Norte, Centro, …).
 *
 * Las zonas ahora cuelgan de su ciudad dentro de la misma taxonomía `zona`.
 * Los términos globales solo se borran si son huérfanos (sin padre), no
 * tienen hijas y no tienen ninguna sede asignada; si tienen sedes se dejan.
 * Se ejecuta una sola vez.
 *
 * @return void
 */
function cita_comparendo_migrar_zonas_globales()
{
    if (get_option('cita_zonas_migradas')) {
        return;
    }

    if (! taxonomy_exists('zona')) {
        return;
    }

    foreach (array_keys(cita_comparendo_zonas_labels()) as $slug) {
        $term = get_term_by('slug', $slug, 'zona');

        if (! $term || is_wp_error($term)) {
            continue;
        }

        // Solo huérfanos y sin sedes.
        if (0 !== (int) $term->parent || 0 !== (int) $term->count) {
            continue;
        }

        $hijas = get_terms(array(
            'taxonomy'   => 'zona',
            'parent'     => $term->term_id,
            'hide_empty' => false,
            'fields'     => 'ids',
        ));

        if (! empty($hijas) && ! is_wp_error($hijas)) {
            continue;
        }

        wp_delete_term($term->term_id, 'zona');
    }

    update_option('cita_zonas_migradas', 1);
}
add_action('admin_init', 'cita_comparendo_migrar_zonas_globales');

/**
 * Meta de los términos `ciudad`: coordenadas del mapa y estado en el CRM.
 *
 * @return void
 */
function cita_comparendo_register_ciudad_meta()
{
    $args = array(
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function () {
            return current_user_can('manage_categories');
        },
    );

    register_term_meta('ciudad', 'latitud', $args);
    register_term_meta('ciudad', 'longitud', $args);
    register_term_meta('ciudad', 'nivel_zoom', $args);
    register_term_meta('ciudad', 'estado', $args);
}
add_action('init', 'cita_comparendo_register_ciudad_meta', 6);

/**
 * Campos de la ciudad en el formulario de creación de término.
 *
 * @return void
 */
function cita_comparendo_ciudad_add_fields()
{
    wp_nonce_field('cita_ciudad_meta', 'cita_ciudad_meta_nonce');
    ?>
    <div class="form-field">
        <label for="cita-ciudad-latitud"><?php esc_html_e('Latitud', 'cita-comparendo'); ?></label>
        <input type="text" name="cita_ciudad_meta[latitud]" id="cita-ciudad-latitud" value="" inputmode="decimal">
        <p><?php esc_html_e('Ejemplo: 4.7110. Se acepta coma decimal.', 'cita-comparendo'); ?></p>
    </div>
    <div class="form-field">
        <label for="cita-ciudad-longitud"><?php esc_html_e('Longitud', 'cita-comparendo'); ?></label>
        <input type="text" name="cita_ciudad_meta[longitud]" id="cita-ciudad-longitud" value="" inputmode="decimal">
        <p><?php esc_html_e('Ejemplo: -74.0721. Se acepta coma decimal.', 'cita-comparendo'); ?></p>
    </div>
    <div class="form-field">
        <label for="cita-ciudad-zoom"><?php esc_html_e('Nivel de zoom', 'cita-comparendo'); ?></label>
        <input type="number" name="cita_ciudad_meta[nivel_zoom]" id="cita-ciudad-zoom" value="" min="1" max="20" step="1">
        <p><?php esc_html_e('Zoom inicial del mapa para esta ciudad (1-20).', 'cita-comparendo'); ?></p>
    </div>
    <div class="form-field">
        <label for="cita-ciudad-estado"><?php esc_html_e('Estado en el CRM', 'cita-comparendo'); ?></label>
        <input type="text" name="cita_ciudad_meta[estado]" id="cita-ciudad-estado" value="">
        <p><?php esc_html_e('Valor que reporta el CRM para la ubicación. Déjalo vacío si no aplica.', 'cita-comparendo'); ?></p>
    </div>
    <?php
}
add_action('ciudad_add_form_fields', 'cita_comparendo_ciudad_add_fields');

/**
 * Campos de la ciudad en el formulario de edición de término.
 *
 * @param WP_Term $term Término que se edita.
 * @return void
 */
function cita_comparendo_ciudad_edit_fields($term)
{
    $latitud = get_term_meta($term->term_id, 'latitud', true);
    $longitud = get_term_meta($term->term_id, 'longitud', true);
    $zoom = get_term_meta($term->term_id, 'nivel_zoom', true);
    $estado = get_term_meta($term->term_id, 'estado', true);

    wp_nonce_field('cita_ciudad_meta', 'cita_ciudad_meta_nonce');
    ?>
    <tr class="form-field">
        <th scope="row"><label for="cita-ciudad-latitud"><?php esc_html_e('Latitud', 'cita-comparendo'); ?></label></th>
        <td>
            <input type="text" name="cita_ciudad_meta[latitud]" id="cita-ciudad-latitud" value="<?php echo esc_attr((string) $latitud); ?>" inputmode="decimal">
            <p class="description"><?php esc_html_e('Ejemplo: 4.7110. Se acepta coma decimal.', 'cita-comparendo'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="cita-ciudad-longitud"><?php esc_html_e('Longitud', 'cita-comparendo'); ?></label></th>
        <td>
            <input type="text" name="cita_ciudad_meta[longitud]" id="cita-ciudad-longitud" value="<?php echo esc_attr((string) $longitud); ?>" inputmode="decimal">
            <p class="description"><?php esc_html_e('Ejemplo: -74.0721. Se acepta coma decimal.', 'cita-comparendo'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="cita-ciudad-zoom"><?php esc_html_e('Nivel de zoom', 'cita-comparendo'); ?></label></th>
        <td>
            <input type="number" name="cita_ciudad_meta[nivel_zoom]" id="cita-ciudad-zoom" value="<?php echo esc_attr((string) $zoom); ?>" min="1" max="20" step="1">
            <p class="description"><?php esc_html_e('Zoom inicial del mapa para esta ciudad (1-20).', 'cita-comparendo'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="cita-ciudad-estado"><?php esc_html_e('Estado en el CRM', 'cita-comparendo'); ?></label></th>
        <td>
            <input type="text" name="cita_ciudad_meta[estado]" id="cita-ciudad-estado" value="<?php echo esc_attr((string) $estado); ?>">
            <p class="description"><?php esc_html_e('Valor que reporta el CRM para la ubicación. Déjalo vacío si no aplica.', 'cita-comparendo'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('ciudad_edit_form_fields', 'cita_comparendo_ciudad_edit_fields');

/**
 * Guarda los campos extra del término `ciudad`.
 *
 * @param int $term_id ID del término.
 * @return void
 */
function cita_comparendo_save_ciudad_meta($term_id)
{
    if (! isset($_POST['cita_ciudad_meta_nonce'])
        || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cita_ciudad_meta_nonce'])), 'cita_ciudad_meta')
    ) {
        return;
    }

    if (! current_user_can('manage_categories')) {
        return;
    }

    $meta = isset($_POST['cita_ciudad_meta']) && is_array($_POST['cita_ciudad_meta'])
        ? wp_unslash($_POST['cita_ciudad_meta'])
        : array();

    foreach (array('latitud', 'longitud') as $campo) {
        $valor = isset($meta[$campo]) ? cita_comparendo_normalizar_coordenada($meta[$campo], $campo) : null;
        if (null === $valor || '' === $valor) {
            delete_term_meta($term_id, $campo);
            continue;
        }
        update_term_meta($term_id, $campo, (string) $valor);
    }

    if (isset($meta['nivel_zoom']) && '' !== $meta['nivel_zoom']) {
        $zoom = (int) $meta['nivel_zoom'];
        update_term_meta($term_id, 'nivel_zoom', (string) max(1, min(20, $zoom)));
    } else {
        delete_term_meta($term_id, 'nivel_zoom');
    }

    if (isset($meta['estado'])) {
        $estado = sanitize_text_field($meta['estado']);
        if ('' === $estado) {
            delete_term_meta($term_id, 'estado');
        } else {
            update_term_meta($term_id, 'estado', $estado);
        }
    }
}
add_action('created_ciudad', 'cita_comparendo_save_ciudad_meta');
add_action('edited_ciudad', 'cita_comparendo_save_ciudad_meta');
