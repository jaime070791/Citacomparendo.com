<?php
/**
 * Formulario de búsqueda.
 *
 * @package Cita_Comparendo
 */
?>
<form role="search" method="get" class="d-flex gap-2" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="visually-hidden" for="cita-search"><?php esc_html_e('Buscar', 'cita-comparendo'); ?></label>
    <input id="cita-search" type="search" class="form-control rounded-pill" name="s"
        value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Buscar…', 'cita-comparendo'); ?>">
    <button type="submit" class="btn btn-primary"><?php esc_html_e('Buscar', 'cita-comparendo'); ?></button>
</form>
