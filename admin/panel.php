<?php

add_action('admin_menu', 'ms_crear_menu_admin');

function ms_crear_menu_admin() {
    add_menu_page(
        'Menús Semanales',
        'Menús Semanales',
        'manage_options',
        'menus-semanales',
        'ms_render_menus_tab',
        'dashicons-calendar-alt',
        30
    );

    add_submenu_page(
        'menus-semanales',
        'Editar Menús',
        'Editar Menús',
        'manage_options',
        'menus-semanales',
        'ms_render_menus_tab'
    );

    add_submenu_page(
        'menus-semanales',
        'Configuración de Rotación',
        'Configuración',
        'manage_options',
        'menus-configuracion',
        'ms_render_configuracion_tab'
    );

    add_submenu_page(
        'menus-semanales',
        'Soporte del Plugin',
        'Soporte',
        'manage_options',
        'menus-soporte',
        'ms_render_soporte_tab'
    );
}

// Incluye las pestañas/páginas
require_once MS_PLUGIN_PATH . 'admin/menus.php';
require_once MS_PLUGIN_PATH . 'admin/configuracion.php';
require_once MS_PLUGIN_PATH . 'admin/soporte.php'; 
