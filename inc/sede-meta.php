<?php
/**
 * Meta boxes nativos del CPT `sede`.
 *
 * No se usa ACF: los campos se guardan como post meta planos para que la
 * sincronización con el CRM y la edición manual escriban en el mismo sitio.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Normaliza una coordenada escrita por una persona o recibida del CRM.
 *
 * Acepta coma decimal ("4,711"), espacios y el guion que usa el CRM para
 * "sin dato". Devuelve '' cuando el valor no es utilizable.
 *
 * @param mixed  $valor Valor de entrada.
 * @param string $tipo  `lat` o `lng` (para validar el rango).
 * @return string Cadena numérica normalizada con punto decimal, o ''.
 */
function cita_comparendo_normalizar_coordenada($valor, $tipo = 'lat')
{
    if (is_null($valor) || is_bool($valor) || is_array($valor)) {
        return '';
    }

    $valor = trim(str_replace(array(',', ' '), array('.', ''), (string) $valor));

    if ('' === $valor || '-' === $valor || ! is_numeric($valor)) {
        return '';
    }

    $numero = (float) $valor;

    if ('lat' === $tipo && ($numero < -90 || $numero > 90)) {
        return '';
    }

    if ('lng' === $tipo && ($numero < -180 || $numero > 180)) {
        return '';
    }

    return (string) $numero;
}

/**
 * Definición de los campos de la sede.
 *
 * @return array<string, array<string, mixed>>
 */
function cita_comparendo_sede_meta_fields()
{
    return array(
        'direccion' => array(
            'label'       => __('Dirección', 'cita-comparendo'),
            'type'        => 'text',
            'description' => __('Dirección física de la sede.', 'cita-comparendo'),
        ),
        'horario' => array(
            'label'       => __('Horario', 'cita-comparendo'),
            'type'        => 'textarea',
            'description' => __('Horario de atención tal como se muestra en la landing.', 'cita-comparendo'),
        ),
        'telefono' => array(
            'label'       => __('Teléfono', 'cita-comparendo'),
            'type'        => 'text',
            'description' => __('Teléfono o WhatsApp de la sede.', 'cita-comparendo'),
        ),
        'lat' => array(
            'label'       => __('Latitud', 'cita-comparendo'),
            'type'        => 'text',
            'description' => __('Ejemplo: 6.2442. Se acepta coma decimal.', 'cita-comparendo'),
            'inputmode'   => 'decimal',
        ),
        'lng' => array(
            'label'       => __('Longitud', 'cita-comparendo'),
            'type'        => 'text',
            'description' => __('Ejemplo: -75.5812. Se acepta coma decimal.', 'cita-comparendo'),
            'inputmode'   => 'decimal',
        ),
        'complemento_iframe' => array(
            'label'       => __('ID de la sede en el CRM', 'cita-comparendo'),
            'type'        => 'text',
            'description' => __('Valor de complemento_iframe que devuelve el CRM. Es la clave con la que la sincronización reconoce la sede.', 'cita-comparendo'),
        ),
        'estado_sede_motor' => array(
            'label'       => __('Estado en el CRM', 'cita-comparendo'),
            'type'        => 'select',
            'description' => __('Si está inactiva, la landing la muestra sin botón de agendamiento.', 'cita-comparendo'),
            'options'     => array(
                ''         => __('Sin definir (activa)', 'cita-comparendo'),
                'Activo'   => __('Activa', 'cita-comparendo'),
                'Inactivo' => __('Inactiva', 'cita-comparendo'),
            ),
        ),
        'price_range' => array(
            'label'       => __('Rango de precio', 'cita-comparendo'),
            'type'        => 'text',
            'description' => __('Valor para schema.org priceRange. Ejemplo: $.', 'cita-comparendo'),
        ),
        'schema_type' => array(
            'label'       => __('Tipo schema.org', 'cita-comparendo'),
            'type'        => 'select',
            'description' => __('Tipo de LocalBusiness que se publica en el JSON-LD de la sede.', 'cita-comparendo'),
            'options'     => array(
                ''                       => __('LocalBusiness (por defecto)', 'cita-comparendo'),
                'LocalBusiness'          => 'LocalBusiness',
                'HealthClub'             => 'HealthClub',
                'EducationalOrganization' => 'EducationalOrganization',
                'DrivingSchool'          => 'DrivingSchool',
                'ProfessionalService'    => 'ProfessionalService',
            ),
        ),
    );
}

/**
 * Registra la meta box de datos de la sede.
 *
 * @return void
 */
function cita_comparendo_add_sede_meta_box()
{
    add_meta_box(
        'cita-sede-datos',
        __('Datos de la sede', 'cita-comparendo'),
        'cita_comparendo_render_sede_meta_box',
        'sede',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_sede', 'cita_comparendo_add_sede_meta_box');

/**
 * Pinta la meta box.
 *
 * @param WP_Post $post Post que se edita.
 * @return void
 */
function cita_comparendo_render_sede_meta_box($post)
{
    wp_nonce_field('cita_sede_meta', 'cita_sede_meta_nonce');

    echo '<table class="form-table" role="presentation"><tbody>';

    foreach (cita_comparendo_sede_meta_fields() as $clave => $campo) {
        $valor = (string) get_post_meta($post->ID, $clave, true);
        $id = 'cita-sede-' . $clave;

        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($id) . '">' . esc_html($campo['label']) . '</label></th>';
        echo '<td>';

        if ('textarea' === $campo['type']) {
            printf(
                '<textarea id="%1$s" name="%1$s" rows="3" class="large-text code">%2$s</textarea>',
                esc_attr($id),
                esc_textarea($valor)
            );
        } elseif ('select' === $campo['type']) {
            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($id) . '">';
            foreach ($campo['options'] as $opcion => $etiqueta) {
                printf(
                    '<option value="%1$s"%2$s>%3$s</option>',
                    esc_attr($opcion),
                    selected($valor, $opcion, false),
                    esc_html($etiqueta)
                );
            }
            echo '</select>';
        } else {
            printf(
                '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text"%3$s>',
                esc_attr($id),
                esc_attr($valor),
                isset($campo['inputmode']) ? ' inputmode="' . esc_attr($campo['inputmode']) . '"' : ''
            );
        }

        if (! empty($campo['description'])) {
            echo '<p class="description">' . esc_html($campo['description']) . '</p>';
        }

        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

/**
 * Guarda la meta box de la sede.
 *
 * @param int $post_id ID del post.
 * @return void
 */
function cita_comparendo_save_sede_meta($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    if (! isset($_POST['cita_sede_meta_nonce'])
        || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cita_sede_meta_nonce'])), 'cita_sede_meta')
    ) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (cita_comparendo_sede_meta_fields() as $clave => $campo) {
        $nombre_input = 'cita-sede-' . $clave;

        if (! isset($_POST[$nombre_input])) {
            continue;
        }

        $valor_crudo = wp_unslash($_POST[$nombre_input]);

        if (in_array($clave, array('lat', 'lng'), true)) {
            $valor = cita_comparendo_normalizar_coordenada($valor_crudo, $clave);
        } elseif ('textarea' === $campo['type']) {
            $valor = sanitize_textarea_field($valor_crudo);
        } elseif ('select' === $campo['type']) {
            $valor = sanitize_text_field($valor_crudo);
            if (! array_key_exists($valor, $campo['options'])) {
                $valor = '';
            }
        } else {
            $valor = sanitize_text_field($valor_crudo);
        }

        if ('' === $valor) {
            delete_post_meta($post_id, $clave);
            continue;
        }

        update_post_meta($post_id, $clave, $valor);
    }
}
add_action('save_post_sede', 'cita_comparendo_save_sede_meta');
