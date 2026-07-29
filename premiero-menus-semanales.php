<?php
/**
 * Plugin Name: Premiero Menús Semanales
 * Plugin URI: https://github.com/andres-nmg/premiero-menus-semanales/
 * Description: Crea, organiza y muestra menús semanales con calendario, estilos y shortcodes configurables.
 * Version: 3.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Premiero
 * Author URI: https://premiero.es
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI: https://github.com/andres-nmg/premiero-menus-semanales/
 * Text Domain: premiero-menus-semanales
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PMS_VERSION', '3.0.1' );
define( 'PMS_PLUGIN_FILE', __FILE__ );
define( 'PMS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PMS_PLUGIN_SLUG', 'premiero-menus-semanales' );
define( 'PMS_SETTINGS_OPTION', 'pms_settings' );
define( 'PMS_MENUS_OPTION', 'ms_menu_data' );
define( 'PMS_CALENDAR_OPTION', 'ms_menu_calendar' );
define( 'PMS_LABELS_OPTION', 'pms_menu_labels' );
define( 'PMS_REPOSITORY_URL', 'https://github.com/andres-nmg/premiero-menus-semanales/' );
define( 'PMS_RELEASE_API', 'https://api.github.com/repos/andres-nmg/premiero-menus-semanales/releases/latest' );
define( 'PMS_RELEASE_ASSET', 'premiero-menus-semanales.zip' );

require_once PMS_PLUGIN_PATH . 'includes/funciones.php';
require_once PMS_PLUGIN_PATH . 'includes/class-premiero-menus-updater.php';
require_once PMS_PLUGIN_PATH . 'admin/panel.php';

Premiero_Menus_Semanales_Updater::init();

function pms_activate_plugin() {
    $legacy_plugin = 'menus-semanales/menus-semanales.php';
    if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $legacy_plugin ) ) {
        deactivate_plugins( $legacy_plugin, true );
    }
    pms_maybe_upgrade();
    pms_ensure_calendar_range();
}
register_activation_hook( __FILE__, 'pms_activate_plugin' );

/**
 * Ejecuta migraciones no destructivas al actualizar o cambiar el nombre
 * de la carpeta. Los datos de las versiones anteriores se conservan en
 * sus opciones originales para que el cambio sea transparente.
 */
function pms_maybe_upgrade() {
    if ( PMS_VERSION === get_option( 'pms_db_version' ) ) {
        return;
    }

    if ( false === get_option( PMS_SETTINGS_OPTION, false ) ) {
        add_option( PMS_SETTINGS_OPTION, pms_default_settings() );
    }

    $menus = pms_get_menus_data();
    $labels = pms_get_menu_labels();
    foreach ( array_keys( $menus ) as $index => $menu_id ) {
        if ( empty( $labels[ $menu_id ] ) ) {
            $labels[ $menu_id ] = sprintf( 'Menú %d', $index + 1 );
        }
    }
    update_option( PMS_LABELS_OPTION, $labels );

    pms_ensure_calendar_range();
    update_option( 'pms_db_version', PMS_VERSION );
}
add_action( 'init', 'pms_maybe_upgrade', 2 );

function pms_plugin_action_links( $links ) {
    array_unshift(
        $links,
        '<a href="' . esc_url( admin_url( 'admin.php?page=' . PMS_PLUGIN_SLUG . '&tab=settings' ) ) . '">Ajustes</a>'
    );
    $links[] = '<a href="' . esc_url( PMS_REPOSITORY_URL ) . '" target="_blank" rel="noopener noreferrer">GitHub</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pms_plugin_action_links' );

function pms_enqueue_frontend_assets() {
    wp_enqueue_style(
        'premiero-menus-semanales',
        PMS_PLUGIN_URL . 'assets/menus-semanales.css',
        array(),
        PMS_VERSION
    );
    wp_enqueue_script(
        'premiero-menus-semanales',
        PMS_PLUGIN_URL . 'assets/menus-semanales.js',
        array(),
        PMS_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'pms_enqueue_frontend_assets' );

/**
 * Corrige la ruta del plugin activo al pasar de menus-semanales a
 * premiero-menus-semanales cuando la renovación se hace directamente
 * sobre una instalación local.
 */
function pms_repair_active_plugin_path() {
    $new_basename = plugin_basename( __FILE__ );
    $old_basename = 'menus-semanales/menus-semanales.php';
    $active = get_option( 'active_plugins', array() );

    if ( in_array( $old_basename, $active, true ) && ! in_array( $new_basename, $active, true ) ) {
        $active = array_values( array_diff( $active, array( $old_basename ) ) );
        $active[] = $new_basename;
        update_option( 'active_plugins', $active );
    }
}
add_action( 'admin_init', 'pms_repair_active_plugin_path', 1 );
