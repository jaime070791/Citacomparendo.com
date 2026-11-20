<?php
/**
 * Walker de navegación con clases de Bootstrap 5.
 *
 * @package Cita_Comparendo
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cita_Comparendo_Bootstrap_Walker extends Walker_Nav_Menu
{
    /**
     * Nivel inicial.
     *
     * @param string $output Salida.
     * @param int    $depth  Profundidad.
     * @param array  $args   Argumentos.
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul class=\"dropdown-menu\">\n";
    }

    /**
     * Elemento.
     *
     * @param string $output Salida.
     * @param object $item   Item del menú.
     * @param int    $depth  Profundidad.
     * @param array  $args   Argumentos.
     * @param int    $id     ID.
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $classes   = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'nav-item';
        $classes[] = 'menu-item-' . $item->ID;

        $has_children = in_array('menu-item-has-children', $classes, true);
        $link_class   = 'nav-link';

        if ($has_children && $depth === 0) {
            $classes[]  = 'dropdown';
            $link_class = 'nav-link dropdown-toggle';
        }
        if ($depth > 0) {
            $link_class = 'dropdown-item';
        }
        if (in_array('current-menu-item', $classes, true) || in_array('current_page_item', $classes, true)) {
            $link_class .= ' active';
        }

        $attributes = sprintf(' href="%s"', esc_url($item->url));
        if ($has_children && $depth === 0) {
            $attributes .= ' data-bs-toggle="dropdown" aria-expanded="false"';
        }
        if (! empty($item->target)) {
            $attributes .= sprintf(' target="%s"', esc_attr($item->target));
        }
        if (! empty($item->attr_title)) {
            $attributes .= sprintf(' title="%s"', esc_attr($item->attr_title));
        }

        $output .= sprintf(
            '<li class="%s"><a class="%s"%s>%s</a>',
            esc_attr(implode(' ', array_unique($classes))),
            esc_attr($link_class),
            $attributes,
            esc_html($item->title)
        );
    }
}
