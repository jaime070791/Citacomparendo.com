<?php
/**
 * Helpers y contenido por defecto del tema.
 *
 * Todos los textos del diseño viven aquí como fallback: si ACF está activo y
 * el campo tiene valor, se usa el valor del campo; si no, se usa el diseño.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Lee un campo ACF con fallback al diseño.
 *
 * @param string $name    Nombre del campo.
 * @param mixed  $default Valor por defecto.
 * @return mixed
 */
function cita_field($name, $default = '')
{
    if (function_exists('get_field')) {
        $value = get_field($name);
        if (! empty($value) || $value === '0' || $value === 0) {
            return $value;
        }
    }
    return $default;
}

/**
 * Botón a WhatsApp (Agendar).
 *
 * @param string $sede Nombre de la sede.
 * @return string
 */
function cita_whatsapp_url($sede = '')
{
    $numero = cita_field('whatsapp_numero', '573168721085');
    $texto  = 'Hola, quiero agendar mi curso comparendo.';
    if ($sede) {
        $texto .= ' Sede: ' . $sede . '.';
    }
    if (preg_match('/^https?:\/\//', $numero)) {
        return $numero;
    }
    return 'https://wa.me/' . preg_replace('/\D/', '', $numero) . '?text=' . rawurlencode($texto);
}

/**
 * URL de una imagen de ACF con fallback a un archivo del tema.
 *
 * Acepta que el campo devuelva URL, ID o array (ACF image).
 *
 * @param string $name Nombre del campo ACF.
 * @param string $file Ruta relativa dentro de assets/images/.
 * @param string $size Tamaño de WordPress para IDs.
 * @return string
 */
function cita_imagen_url($name, $file = '', $size = 'full')
{
    $value = function_exists('get_field') ? get_field($name) : '';

    if (is_array($value)) {
        if (! empty($value['url'])) {
            return $value['url'];
        }
        $value = isset($value['ID']) ? $value['ID'] : 0;
    }

    if (is_numeric($value) && (int) $value > 0) {
        $url = wp_get_attachment_image_url((int) $value, $size);
        if ($url) {
            return $url;
        }
    }

    if (is_string($value) && '' !== $value) {
        return $value;
    }

    return $file ? CITA_COMPARENDO_IMG_URI . $file : '';
}

/**
 * Sedes de la landing.
 *
 * @return array
 */
function cita_default_sedes()
{
    return array(
        array(
            'nombre'    => 'Nombre Sede 01',
            'zona'      => 'centro',
            'direccion' => 'Dirección',
            'horario'   => 'Horario',
            'lat'       => 6.2518,
            'lng'       => -75.5636,
            'activa'    => true,
        ),
        array(
            'nombre'    => 'Nombre Sede 02',
            'zona'      => 'norte',
            'direccion' => 'Dirección',
            'horario'   => 'Horario',
            'lat'       => 6.2900,
            'lng'       => -75.5700,
            'activa'    => false,
        ),
        array(
            'nombre'    => 'Nombre Sede 03',
            'zona'      => 'occidente',
            'direccion' => 'Dirección',
            'horario'   => 'Horario',
            'lat'       => 6.2450,
            'lng'       => -75.6100,
            'activa'    => false,
        ),
        array(
            'nombre'    => 'Nombre Sede 04',
            'zona'      => 'sur',
            'direccion' => 'Dirección',
            'horario'   => 'Horario',
            'lat'       => 6.1900,
            'lng'       => -75.5750,
            'activa'    => false,
        ),
    );
}

/**
 * Cifras de la barra amarilla.
 *
 * @return array
 */
function cita_default_cifras()
{
    return array(
        array('valor' => '+110.000', 'etiqueta' => 'cursos comparados', 'icono' => 'fa-file-circle-check'),
        array('valor' => '+110.000', 'etiqueta' => 'Conductores satisfechos', 'icono' => 'fa-face-smile'),
        array('valor' => '4,8/5', 'etiqueta' => 'de Calificación', 'icono' => 'fa-star'),
    );
}

/**
 * Pasos de "Cómo funciona".
 *
 * @return array
 */
function cita_default_pasos()
{
    return array(
        array(
            'titulo' => 'Identifica la fecha del curso comparendo',
            'texto'  => 'Accede a los descuentos, siempre y cuando estés dentro de las fechas establecidas por el Ministerio de Transporte.',
            'icono'  => 'fa-file-circle-exclamation',
        ),
        array(
            'titulo' => 'Busca la sede para hacer el curso',
            'texto'  => 'Selecciona entre todas nuestras sedes en la ciudad de Bogotá para agendar cita del curso comparendo.',
            'icono'  => 'fa-magnifying-glass',
        ),
        array(
            'titulo' => 'Agenda tu cita de curso comparendo',
            'texto'  => 'Reserva tu cita para foto multa o comparendo en el horario que más te convenga.',
            'icono'  => 'fa-calendar-check',
        ),
        array(
            'titulo' => 'Obtén el descuento en tu comparendo o foto multa',
            'texto'  => 'Una vez termines el curso recibirás un certificado que te servirá para obtener el descuento en la norma otorgada.',
            'icono'  => 'fa-percent',
        ),
    );
}

/**
 * Testimonios.
 *
 * @return array
 */
function cita_default_testimonios()
{
    return array(
        array(
            'rating' => '4,5',
            'texto'  => 'Soy estudiante y uso mi moto para ir a clase. El curso me pareció muy bacano y me ayudó a entender todo bien, además el descuento me sirvió un montón. ¡Recomendado para los que andamos en la calle!',
            'nombre' => 'Sandra Pulido',
            'rol'    => 'Estudiante, Motociclista',
        ),
        array(
            'rating' => '4,2',
            'texto'  => 'Trabajo como taxista en el centro. El curso fue breve, aprendí cosas que ni sabía y el descuento me cayó de perlas. ¡Gracias por la buena atención!',
            'nombre' => 'Jhonatan Prieto',
            'rol'    => 'Conductor, Taxi',
        ),
        array(
            'rating' => '4,8',
            'texto'  => 'Soy enfermera y vivo en Suba. El curso comparendo fue súper claro y rápido, me ayudó a entender mejor las normas y pude pagar mi multa con el descuento. ¡Recomendadísimo!',
            'nombre' => 'Paola Rodríguez',
            'rol'    => 'Enfermera, Motociclista',
        ),
        array(
            'rating' => '4,8',
            'texto'  => 'Soy publicista y me muevo en la zona T. Me pareció top el curso, todo súper organizado y el descuento me sirvió para pagar la multa. ¡Full recomendado para los que andan en carro!',
            'nombre' => 'Nicolás Gutiérrez',
            'rol'    => 'Publicista, Conductor',
        ),
    );
}

/**
 * Preguntas frecuentes.
 *
 * @return array
 */
function cita_default_faq()
{
    return array(
        array(
            'pregunta' => '¿Cuánto tiempo tengo para hacer el curso comparendo?',
            'respuesta' => 'Para acceder al 50% de descuento: tienes 5 días hábiles si es comparendo manual, si es foto-multa tienes 11 días hábiles y para acceder al 25% de descuento tienes hasta 20 días hábiles si es manual, si es foto-multa tienes hasta 26 días hábiles.',
        ),
        array(
            'pregunta' => '¿Dónde puedo hacer el curso de descuento de comparendos?',
            'respuesta' => 'En cualquiera de nuestras sedes aliadas de Medellín. Elige la sede más cercana en la sección de sedes y agenda tu cupo por WhatsApp.',
        ),
        array(
            'pregunta' => '¿Cuántos días tengo para pagar el comparendo y hacer el descuento del 50%?',
            'respuesta' => 'Tienes hasta 5 días hábiles si el comparendo es manual y hasta 11 días hábiles si es foto-multa, contados desde la notificación.',
        ),
        array(
            'pregunta' => '¿Cómo bajar el precio de una multa?',
            'respuesta' => 'Realizando el curso de descuento dentro de los plazos del Ministerio de Transporte: obtienes el 50% si asistes a tiempo y el 25% si lo haces en fechas posteriores.',
        ),
        array(
            'pregunta' => '¿Quién debe hacer el curso de comparendo?',
            'respuesta' => 'Toda persona que haya recibido un comparendo o foto-multa y quiera acceder al descuento del 50% o 25% del valor de la sanción.',
        ),
    );
}

/**
 * Posts de respaldo para la sección Blog (cuando aún no hay entradas).
 *
 * @return array
 */
function cita_default_posts()
{
    return array(
        array(
            'titulo'   => 'Motos cancelan caravana de Halloween',
            'extracto' => 'Bogotá queda sin la tradicional rodada de moteros en Halloween',
            'imagen'   => 'blog-1.jpg',
            'url'      => '#blog',
        ),
        array(
            'titulo'   => 'Motos cancelan caravana de Halloween',
            'extracto' => 'Bogotá queda sin la tradicional rodada de moteros en Halloween',
            'imagen'   => 'blog-2.jpg',
            'url'      => '#blog',
        ),
        array(
            'titulo'   => 'Motos cancelan caravana de Halloween',
            'extracto' => 'Bogotá queda sin la tradicional rodada de moteros en Halloween',
            'imagen'   => 'blog-3.jpg',
            'url'      => '#blog',
        ),
        array(
            'titulo'   => 'Motos cancelan caravana de Halloween',
            'extracto' => 'Bogotá queda sin la tradicional rodada de moteros en Halloween',
            'imagen'   => 'blog-4.jpg',
            'url'      => '#blog',
        ),
    );
}

/**
 * Estrellas de una calificación.
 *
 * @param string $rating Calificación, por ejemplo "4,8".
 * @return string
 */
function cita_estrellas_html($rating)
{
    $sufijo = (false !== strpos($rating, '/')) ? '' : '/5';

    return '<span class="text-navy">' . esc_html($rating . $sufijo) . '</span> '
        . '<i class="fa-solid fa-star text-yellow" aria-hidden="true"></i>';
}
