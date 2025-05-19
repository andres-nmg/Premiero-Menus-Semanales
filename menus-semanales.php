<?php
/**
 * Plugin Name: Menús Semanales
 * Description: Herramienta para la subida y gestión de los menús semanales de Casa Macario.
 * Version: 2.7
 * Author: <a href="https://premiero.es">Premiero</a>
 */

define('MS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once MS_PLUGIN_PATH . 'includes/funciones.php';
require_once MS_PLUGIN_PATH . 'admin/panel.php';

// Cargar assets para admin
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'menus-semanales') !== false) {
        wp_enqueue_style('ms-estilos', MS_PLUGIN_URL . 'assets/estilo.css');
        wp_enqueue_script('ms-script', MS_PLUGIN_URL . 'assets/script.js', ['jquery'], null, true);
    }
});
