<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'pms_register_admin_menu' );
add_action( 'admin_enqueue_scripts', 'pms_enqueue_admin_assets' );
add_action( 'admin_init', 'pms_handle_admin_actions' );

function pms_register_admin_menu() {
    add_menu_page(
        'Premiero Menús Semanales',
        'Menús',
        'manage_options',
        PMS_PLUGIN_SLUG,
        'pms_render_admin_page',
        'dashicons-calendar-alt',
        30
    );
}

function pms_enqueue_admin_assets( $hook ) {
    if ( 'toplevel_page_' . PMS_PLUGIN_SLUG !== $hook ) {
        return;
    }

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style(
        'premiero-menus-admin',
        PMS_PLUGIN_URL . 'assets/admin.css',
        array( 'wp-color-picker' ),
        PMS_VERSION
    );
    wp_enqueue_script(
        'premiero-menus-admin',
        PMS_PLUGIN_URL . 'assets/admin.js',
        array( 'jquery', 'wp-color-picker' ),
        PMS_VERSION,
        true
    );
}

function pms_admin_url( $tab = 'menus', $args = array() ) {
    return add_query_arg(
        array_merge( array( 'page' => PMS_PLUGIN_SLUG, 'tab' => $tab ), $args ),
        admin_url( 'admin.php' )
    );
}

function pms_admin_redirect( $tab, $message, $args = array() ) {
    $args['pms_notice'] = $message;
    wp_safe_redirect( pms_admin_url( $tab, $args ) );
    exit;
}

function pms_handle_admin_actions() {
    if ( ! current_user_can( 'manage_options' ) || empty( $_POST['pms_action'] ) ) {
        return;
    }

    $action = sanitize_key( wp_unslash( $_POST['pms_action'] ) );
    check_admin_referer( 'pms_' . $action );
    $menus = pms_get_menus_data();
    $labels = pms_get_menu_labels();

    if ( 'create_menu' === $action ) {
        $menu_id = pms_next_menu_id( $menus );
        $source_id = isset( $_POST['source_menu'] ) ? sanitize_key( wp_unslash( $_POST['source_menu'] ) ) : '';
        $menus[ $menu_id ] = $source_id && isset( $menus[ $source_id ] ) ? $menus[ $source_id ] : array();
        $name = isset( $_POST['menu_name'] ) ? sanitize_text_field( wp_unslash( $_POST['menu_name'] ) ) : '';
        $labels[ $menu_id ] = $name ?: sprintf( 'Menú %d', count( $menus ) );
        pms_save_menus_data( $menus );
        update_option( PMS_LABELS_OPTION, $labels );
        pms_ensure_calendar_range();
        pms_admin_redirect( 'menus', 'created', array( 'edit' => $menu_id ) );
    }

    if ( 'duplicate_menu' === $action ) {
        $source_id = isset( $_POST['menu_id'] ) ? sanitize_key( wp_unslash( $_POST['menu_id'] ) ) : '';
        if ( isset( $menus[ $source_id ] ) ) {
            $menu_id = pms_next_menu_id( $menus );
            $menus[ $menu_id ] = $menus[ $source_id ];
            $labels[ $menu_id ] = pms_get_menu_label( $source_id ) . ' (copia)';
            pms_save_menus_data( $menus );
            update_option( PMS_LABELS_OPTION, $labels );
            pms_admin_redirect( 'menus', 'duplicated', array( 'edit' => $menu_id ) );
        }
    }

    if ( 'delete_menu' === $action ) {
        $menu_id = isset( $_POST['menu_id'] ) ? sanitize_key( wp_unslash( $_POST['menu_id'] ) ) : '';
        if ( isset( $menus[ $menu_id ] ) ) {
            unset( $menus[ $menu_id ], $labels[ $menu_id ] );
            pms_save_menus_data( $menus );
            update_option( PMS_LABELS_OPTION, $labels );

            $calendar = get_option( PMS_CALENDAR_OPTION, array() );
            foreach ( (array) $calendar as $date => $assigned_id ) {
                if ( $menu_id === $assigned_id ) {
                    unset( $calendar[ $date ] );
                }
            }
            update_option( PMS_CALENDAR_OPTION, $calendar );
            pms_ensure_calendar_range();
            pms_admin_redirect( 'menus', 'deleted' );
        }
    }

    if ( 'save_menu' === $action ) {
        $menu_id = isset( $_POST['menu_id'] ) ? sanitize_key( wp_unslash( $_POST['menu_id'] ) ) : '';
        if ( isset( $menus[ $menu_id ] ) ) {
            $json = isset( $_POST['menu_json'] ) ? wp_unslash( $_POST['menu_json'] ) : '';
            $data = json_decode( $json, true );
            if ( is_array( $data ) ) {
                $menus[ $menu_id ] = pms_sanitize_menu_data( $data );
                pms_save_menus_data( $menus );
                $name = isset( $_POST['menu_name'] ) ? sanitize_text_field( wp_unslash( $_POST['menu_name'] ) ) : '';
                $labels[ $menu_id ] = $name ?: pms_get_menu_label( $menu_id );
                update_option( PMS_LABELS_OPTION, $labels );
                pms_admin_redirect( 'menus', 'saved', array( 'edit' => $menu_id ) );
            }
        }
        pms_admin_redirect( 'menus', 'invalid' );
    }

    if ( 'save_calendar' === $action ) {
        $posted = isset( $_POST['calendar'] ) && is_array( $_POST['calendar'] )
            ? wp_unslash( $_POST['calendar'] )
            : array();
        $calendar = get_option( PMS_CALENDAR_OPTION, array() );
        $calendar = is_array( $calendar ) ? $calendar : array();

        foreach ( $posted as $date => $menu_id ) {
            $date = sanitize_text_field( $date );
            $menu_id = sanitize_key( $menu_id );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && isset( $menus[ $menu_id ] ) ) {
                $calendar[ $date ] = $menu_id;
            }
        }
        ksort( $calendar );
        update_option( PMS_CALENDAR_OPTION, $calendar );
        pms_admin_redirect( 'calendar', 'calendar_saved' );
    }

    if ( 'rotate_calendar' === $action ) {
        pms_ensure_calendar_range( true );
        pms_admin_redirect( 'calendar', 'calendar_rotated' );
    }
}

function pms_render_notice() {
    $notice = isset( $_GET['pms_notice'] ) ? sanitize_key( wp_unslash( $_GET['pms_notice'] ) ) : '';
    $messages = array(
        'created'          => 'Menú creado. Ya puedes completar su contenido.',
        'duplicated'       => 'Menú duplicado correctamente.',
        'deleted'          => 'Menú eliminado. Las semanas afectadas se han reasignado sin modificar el resto.',
        'saved'            => 'Menú guardado correctamente.',
        'invalid'          => 'No se pudieron guardar los datos del menú.',
        'calendar_saved'   => 'Calendario actualizado correctamente.',
        'calendar_rotated' => 'Rotación regenerada desde la semana actual.',
    );
    if ( isset( $messages[ $notice ] ) ) {
        $class = 'invalid' === $notice ? 'notice notice-error' : 'notice notice-success is-dismissible';
        echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $messages[ $notice ] ) . '</p></div>';
    }
}

function pms_render_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $allowed_tabs = array( 'menus', 'calendar', 'shortcodes', 'appearance', 'settings', 'about' );
    $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'menus';
    $tab = in_array( $tab, $allowed_tabs, true ) ? $tab : 'menus';
    ?>
    <div class="wrap pms-admin">
        <header class="pms-admin__header">
            <div>
                <h1>Premiero Menús Semanales</h1>
                <p>Organiza, publica y adapta tus menús desde un único lugar.</p>
            </div>
        </header>

        <nav class="nav-tab-wrapper" aria-label="Secciones de Menús Semanales">
            <?php
            $tabs = array(
                'menus'      => 'Menús',
                'calendar'   => 'Calendario',
                'shortcodes' => 'Shortcodes',
                'appearance' => 'Apariencia',
                'settings'   => 'Ajustes',
                'about'      => 'Acerca de',
            );
            foreach ( $tabs as $slug => $label ) {
                printf(
                    '<a href="%1$s" class="nav-tab %2$s">%3$s</a>',
                    esc_url( pms_admin_url( $slug ) ),
                    $slug === $tab ? 'nav-tab-active' : '',
                    esc_html( $label )
                );
            }
            ?>
        </nav>

        <?php pms_render_notice(); ?>
        <main class="pms-admin__content">
            <?php
            switch ( $tab ) {
                case 'calendar':
                    pms_render_calendar_tab();
                    break;
                case 'shortcodes':
                    pms_render_shortcodes_tab();
                    break;
                case 'appearance':
                    pms_render_appearance_tab();
                    break;
                case 'settings':
                    pms_render_settings_tab();
                    break;
                case 'about':
                    pms_render_about_tab();
                    break;
                default:
                    pms_render_menus_tab();
                    break;
            }
            ?>
        </main>
    </div>
    <?php
}

function pms_render_menus_tab() {
    $menus = pms_get_menus_data();
    $editing = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : '';

    if ( $editing && isset( $menus[ $editing ] ) ) {
        pms_render_menu_editor( $editing, $menus[ $editing ] );
        return;
    }

    $current_id = pms_get_current_menu_id();
    ?>
    <div class="pms-section-heading">
        <div>
            <h2>Menús disponibles</h2>
            <p>Crea menús reutilizables y asígnalos después a las semanas del calendario.</p>
        </div>
    </div>

    <section class="pms-panel">
        <form method="post" class="pms-create-menu">
            <?php wp_nonce_field( 'pms_create_menu' ); ?>
            <input type="hidden" name="pms_action" value="create_menu">
            <label>
                <span>Nombre del nuevo menú</span>
                <input type="text" name="menu_name" placeholder="Ej.: Menú de verano" required>
            </label>
            <label>
                <span>Contenido inicial</span>
                <select name="source_menu">
                    <option value="">Menú vacío</option>
                    <?php foreach ( $menus as $menu_id => $data ) : ?>
                        <option value="<?php echo esc_attr( $menu_id ); ?>">Copiar <?php echo esc_html( pms_get_menu_label( $menu_id ) ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="button button-primary">Crear menú</button>
        </form>
    </section>

    <?php if ( empty( $menus ) ) : ?>
        <div class="pms-empty-state">
            <span class="dashicons dashicons-food"></span>
            <h3>Aún no hay menús</h3>
            <p>Crea el primero con el formulario superior.</p>
        </div>
    <?php else : ?>
        <div class="pms-menu-cards">
            <?php foreach ( $menus as $menu_id => $data ) : ?>
                <article class="pms-menu-card">
                    <div>
                        <?php if ( $current_id === $menu_id ) : ?>
                            <span class="pms-badge">Semana actual</span>
                        <?php endif; ?>
                        <h3><?php echo esc_html( pms_get_menu_label( $menu_id ) ); ?></h3>
                        <p><?php echo esc_html( pms_count_filled_dishes( $data ) ); ?> platos completados</p>
                    </div>
                    <div class="pms-card-actions">
                        <a class="button button-primary" href="<?php echo esc_url( pms_admin_url( 'menus', array( 'edit' => $menu_id ) ) ); ?>">Editar</a>
                        <form method="post">
                            <?php wp_nonce_field( 'pms_duplicate_menu' ); ?>
                            <input type="hidden" name="pms_action" value="duplicate_menu">
                            <input type="hidden" name="menu_id" value="<?php echo esc_attr( $menu_id ); ?>">
                            <button class="button" type="submit">Duplicar</button>
                        </form>
                        <form method="post" onsubmit="return confirm('¿Seguro que quieres eliminar este menú?');">
                            <?php wp_nonce_field( 'pms_delete_menu' ); ?>
                            <input type="hidden" name="pms_action" value="delete_menu">
                            <input type="hidden" name="menu_id" value="<?php echo esc_attr( $menu_id ); ?>">
                            <button class="button button-link-delete" type="submit">Eliminar</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
}

function pms_count_filled_dishes( $data ) {
    $count = 0;
    foreach ( (array) $data as $types ) {
        foreach ( (array) $types as $items ) {
            $count += count( array_filter( (array) $items, 'strlen' ) );
        }
    }
    return $count;
}

function pms_render_menu_editor( $menu_id, $data ) {
    ?>
    <div class="pms-editor-heading">
        <div>
            <a href="<?php echo esc_url( pms_admin_url( 'menus' ) ); ?>" class="pms-back-link">← Volver a todos los menús</a>
            <h2>Editar <?php echo esc_html( pms_get_menu_label( $menu_id ) ); ?></h2>
            <p>Edita las celdas directamente o pega un bloque desde Excel o Google Sheets.</p>
        </div>
    </div>

    <form method="post" id="pms-menu-form">
        <?php wp_nonce_field( 'pms_save_menu' ); ?>
        <input type="hidden" name="pms_action" value="save_menu">
        <input type="hidden" name="menu_id" value="<?php echo esc_attr( $menu_id ); ?>">
        <input type="hidden" name="menu_json" id="pms-menu-json">
        <input type="file" id="pms-csv-input" accept=".csv,text/csv" hidden>

        <div class="pms-editor-toolbar">
            <label class="pms-editor-name">
                <span>Nombre</span>
                <input type="text" name="menu_name" value="<?php echo esc_attr( pms_get_menu_label( $menu_id ) ); ?>" required>
            </label>
            <div class="pms-editor-actions">
                <button type="button" class="button" id="pms-clear-table">Vaciar tabla</button>
                <button type="button" class="button" id="pms-export-csv">Exportar CSV</button>
                <button type="button" class="button" id="pms-import-csv">Importar CSV</button>
                <button type="submit" class="button button-primary">Guardar cambios</button>
            </div>
        </div>

        <div class="pms-table-scroll">
            <table id="pms-menu-table" class="widefat striped">
                <thead>
                    <tr>
                        <th>Tipo de plato</th>
                        <?php foreach ( pms_days() as $day ) : ?>
                            <th><?php echo esc_html( ucfirst( $day ) ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( pms_menu_rows() as $row_key => $label ) : ?>
                        <?php list( $type, $index ) = pms_row_type_and_index( $row_key ); ?>
                        <tr>
                            <th scope="row"><?php echo esc_html( $label ); ?></th>
                            <?php foreach ( pms_days() as $day ) : ?>
                                <td
                                    contenteditable="true"
                                    data-day="<?php echo esc_attr( $day ); ?>"
                                    data-type="<?php echo esc_attr( $type ); ?>"
                                    data-index="<?php echo esc_attr( $index ); ?>"
                                ><?php echo esc_html( $data[ $day ][ $type ][ $index ] ?? '' ); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php
}

function pms_render_calendar_tab() {
    $menus = pms_get_menus_data();
    $calendar = pms_ensure_calendar_range();
    $settings = pms_get_settings();
    $monday = pms_week_monday();

    ?>
    <div class="pms-section-heading">
        <div>
            <h2>Calendario de menús</h2>
            <p>Asigna visualmente un menú a cada semana. La semana actual siempre aparece en primer lugar.</p>
        </div>
    </div>

    <?php if ( empty( $menus ) ) : ?>
        <div class="pms-empty-state">
            <h3>Primero necesitas crear un menú</h3>
            <p>Después podrás asignarlo a las semanas del calendario.</p>
            <a class="button button-primary" href="<?php echo esc_url( pms_admin_url( 'menus' ) ); ?>">Crear un menú</a>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'pms_save_calendar' ); ?>
        <input type="hidden" name="pms_action" value="save_calendar">
        <div class="pms-calendar-grid">
            <?php for ( $index = 0; $index < absint( $settings['week_count'] ); $index++ ) : ?>
                <?php
                $date = pms_calendar_date( $monday, $index );
                $end_date = pms_calendar_date( $date, 0 );
                $end = DateTimeImmutable::createFromFormat( '!Y-m-d', $end_date, wp_timezone() )->modify( '+6 days' );
                $assigned = $calendar[ $date ] ?? '';
                $is_current = 0 === $index;
                ?>
                <article class="pms-week-card <?php echo $is_current ? 'is-current' : ''; ?>">
                    <div class="pms-week-card__top">
                        <span class="pms-week-number">Semana <?php echo esc_html( wp_date( 'W', strtotime( $date ) ) ); ?></span>
                        <?php if ( $is_current ) : ?><span class="pms-badge">Actual</span><?php endif; ?>
                    </div>
                    <h3><?php echo esc_html( pms_format_week_range( $date, $end->format( 'Y-m-d' ) ) ); ?></h3>
                    <label>
                        <span class="screen-reader-text">Menú para <?php echo esc_html( $date ); ?></span>
                        <select name="calendar[<?php echo esc_attr( $date ); ?>]">
                            <?php foreach ( $menus as $menu_id => $data ) : ?>
                                <option value="<?php echo esc_attr( $menu_id ); ?>" <?php selected( $assigned, $menu_id ); ?>>
                                    <?php echo esc_html( pms_get_menu_label( $menu_id ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php if ( $assigned && isset( $menus[ $assigned ] ) ) : ?>
                        <a href="<?php echo esc_url( pms_admin_url( 'menus', array( 'edit' => $assigned ) ) ); ?>">Editar este menú →</a>
                    <?php endif; ?>
                </article>
            <?php endfor; ?>
        </div>
        <p class="submit">
            <button type="submit" class="button button-primary">Guardar calendario</button>
        </p>
    </form>

    <section class="pms-panel pms-panel--warning">
        <div>
            <h3>Regenerar la rotación</h3>
            <p>Asigna los menús por orden desde la semana actual. Esta acción reemplaza las asignaciones visibles.</p>
        </div>
        <form method="post" onsubmit="return confirm('¿Quieres reemplazar las asignaciones de las próximas semanas?');">
            <?php wp_nonce_field( 'pms_rotate_calendar' ); ?>
            <input type="hidden" name="pms_action" value="rotate_calendar">
            <button type="submit" class="button">Regenerar rotación</button>
        </form>
    </section>
    <?php
}

function pms_format_week_range( $start, $end ) {
    $start_time = strtotime( $start . ' 12:00:00' );
    $end_time = strtotime( $end . ' 12:00:00' );
    return sprintf(
        '%s – %s',
        wp_date( 'j M', $start_time ),
        wp_date( 'j M Y', $end_time )
    );
}

function pms_render_shortcodes_tab() {
    $shortcodes = array(
        '[menu_semanal]'                         => 'Menú de lunes a viernes. Es la opción recomendada.',
        '[menu_semanal vista="hoy"]'             => 'Menú correspondiente al día actual.',
        '[menu_semanal vista="fin_semana"]'      => 'Menú de sábado y domingo.',
        '[menu_semanal vista="movil"]'           => 'Semana completa en formato acordeón.',
        '[menu_actual]'                          => 'Compatibilidad: menú del día actual.',
        '[menu_semana]'                          => 'Compatibilidad: menú de lunes a viernes.',
        '[menu_finsemana]'                       => 'Compatibilidad: menú de fin de semana.',
        '[menu_semana_mobile]'                   => 'Compatibilidad: semana laboral en acordeón.',
        '[menu_finsemana_mobile]'                => 'Compatibilidad: fin de semana en acordeón.',
    );
    ?>
    <div class="pms-section-heading">
        <div>
            <h2>Añadir el menú a una página</h2>
            <p>Copia el shortcode que necesites y pégalo en un bloque «Shortcode» del editor de WordPress.</p>
        </div>
    </div>
    <div class="pms-shortcode-list">
        <?php foreach ( $shortcodes as $shortcode => $description ) : ?>
            <article class="pms-shortcode-card">
                <div>
                    <code><?php echo esc_html( $shortcode ); ?></code>
                    <p><?php echo esc_html( $description ); ?></p>
                </div>
                <button type="button" class="button pms-copy-shortcode" data-shortcode="<?php echo esc_attr( $shortcode ); ?>">Copiar</button>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="pms-panel">
        <h3>Anchura del contenido</h3>
        <p>El plugin ocupa el 100 % del contenedor donde se inserta. Si se ve estrecho, configura la página o el grupo que contiene el shortcode como «Ancho amplio» o «Ancho completo» en el tema/editor.</p>
    </div>
    <?php
}

function pms_render_appearance_tab() {
    $settings = pms_get_settings();
    $style = $settings['style'];
    $colors = array(
        'accent'           => 'Color principal',
        'accent_text'      => 'Texto sobre color principal',
        'heading'          => 'Títulos',
        'text'             => 'Texto de los platos',
        'background'       => 'Fondo general',
        'panel_background' => 'Fondo de cada día',
        'border'           => 'Bordes y separadores',
    );
    ?>
    <div class="pms-section-heading">
        <div>
            <h2>Apariencia y vista previa</h2>
            <p>Los cambios se aplican a todos los shortcodes del plugin.</p>
        </div>
    </div>
    <div class="pms-appearance-layout">
        <form method="post" action="options.php" class="pms-panel">
            <?php settings_fields( 'pms_settings_group' ); ?>
            <input type="hidden" name="<?php echo esc_attr( PMS_SETTINGS_OPTION ); ?>[settings_tab]" value="appearance">
            <table class="form-table">
                <?php foreach ( $colors as $key => $label ) : ?>
                    <tr>
                        <th><label for="pms-style-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                        <td>
                            <input
                                type="text"
                                id="pms-style-<?php echo esc_attr( $key ); ?>"
                                class="pms-color-field"
                                name="<?php echo esc_attr( PMS_SETTINGS_OPTION ); ?>[style][<?php echo esc_attr( $key ); ?>]"
                                value="<?php echo esc_attr( $style[ $key ] ); ?>"
                                data-style-key="<?php echo esc_attr( $key ); ?>"
                            >
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th><label for="pms-font-family">Tipografía</label></th>
                    <td>
                        <select id="pms-font-family" name="<?php echo esc_attr( PMS_SETTINGS_OPTION ); ?>[style][font_family]" data-style-key="font_family">
                            <option value="system" <?php selected( $style['font_family'], 'system' ); ?>>Sistema</option>
                            <option value="serif" <?php selected( $style['font_family'], 'serif' ); ?>>Serif clásica</option>
                            <option value="modern" <?php selected( $style['font_family'], 'modern' ); ?>>Moderna</option>
                        </select>
                    </td>
                </tr>
                <?php
                $numbers = array(
                    'body_size'    => array( 'Tamaño del texto', 12, 24, 'px' ),
                    'heading_size' => array( 'Tamaño de los títulos', 16, 40, 'px' ),
                    'radius'       => array( 'Esquinas redondeadas', 0, 30, 'px' ),
                    'gap'          => array( 'Separación', 8, 60, 'px' ),
                );
                foreach ( $numbers as $key => $config ) :
                    ?>
                    <tr>
                        <th><label for="pms-style-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $config[0] ); ?></label></th>
                        <td>
                            <input
                                type="number"
                                id="pms-style-<?php echo esc_attr( $key ); ?>"
                                name="<?php echo esc_attr( PMS_SETTINGS_OPTION ); ?>[style][<?php echo esc_attr( $key ); ?>]"
                                value="<?php echo esc_attr( $style[ $key ] ); ?>"
                                min="<?php echo esc_attr( $config[1] ); ?>"
                                max="<?php echo esc_attr( $config[2] ); ?>"
                                data-style-key="<?php echo esc_attr( $key ); ?>"
                            > <?php echo esc_html( $config[3] ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p class="submit"><button type="submit" class="button button-primary">Guardar apariencia</button></p>
        </form>

        <div class="pms-preview-column">
            <h3>Vista previa</h3>
            <div class="pms-preview" style="<?php echo esc_attr( pms_style_variables( $style ) ); ?>">
                <section class="pms-preview-day">
                    <h3>LUNES</h3>
                    <div>
                        <h4>Primeros</h4>
                        <p>Ensalada de temporada</p>
                        <p>Crema de verduras</p>
                    </div>
                    <div>
                        <h4>Segundos</h4>
                        <p>Plato principal de ejemplo</p>
                    </div>
                    <div>
                        <h4>Postres</h4>
                        <p>Fruta fresca</p>
                    </div>
                </section>
                <section class="pms-preview-day">
                    <h3>MARTES</h3>
                    <div>
                        <h4>Primeros</h4>
                        <p>Arroz con verduras</p>
                    </div>
                    <div>
                        <h4>Segundos</h4>
                        <p>Receta de la casa</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <?php
}

function pms_render_settings_tab() {
    $settings = pms_get_settings();
    ?>
    <div class="pms-section-heading">
        <div>
            <h2>Ajustes generales</h2>
            <p>Configura el comportamiento del calendario sin alterar los menús ya creados.</p>
        </div>
    </div>
    <form method="post" action="options.php" class="pms-panel pms-settings-form">
        <?php settings_fields( 'pms_settings_group' ); ?>
        <input type="hidden" name="<?php echo esc_attr( PMS_SETTINGS_OPTION ); ?>[settings_tab]" value="settings">
        <table class="form-table">
            <tr>
                <th><label for="pms-week-count">Semanas visibles</label></th>
                <td>
                    <input
                        type="number"
                        id="pms-week-count"
                        name="<?php echo esc_attr( PMS_SETTINGS_OPTION ); ?>[week_count]"
                        value="<?php echo esc_attr( $settings['week_count'] ); ?>"
                        min="4"
                        max="52"
                    >
                    <p class="description">Entre 4 y 52 semanas. Los cambios manuales existentes se conservan.</p>
                </td>
            </tr>
        </table>
        <p class="submit"><button type="submit" class="button button-primary">Guardar ajustes</button></p>
    </form>
    <section class="pms-panel">
        <h3>Funcionamiento del calendario</h3>
        <ul class="pms-check-list">
            <li>La semana actual se calcula con la zona horaria configurada en WordPress.</li>
            <li>Los huecos futuros se completan automáticamente sin sobrescribir asignaciones manuales.</li>
            <li>La rotación solo se reemplaza al usar expresamente «Regenerar rotación».</li>
        </ul>
    </section>
    <?php
}

function pms_render_about_tab() {
    ?>
    <div class="pms-about">
        <div class="pms-about__primary">
            <section class="pms-about__brand">
                <a class="pms-about__wordmark" href="https://premiero.es" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo esc_url( PMS_PLUGIN_URL . 'img/logo-premiero.png' ); ?>" alt="Premiero">
                </a>
                <p class="pms-about__eyebrow">Gestión de menús para WordPress</p>
                <h2>Premiero Menús Semanales</h2>
                <p class="pms-about__lead">Plugin desarrollado por <strong>Premiero</strong> para crear, organizar y publicar menús semanales desde WordPress.</p>
                <div class="pms-about__actions">
                    <a class="button button-primary" href="https://premiero.es" target="_blank" rel="noopener noreferrer">Visitar premiero.es</a>
                </div>
            </section>
            <section class="pms-about__panel">
                <h2>Acceso rápido</h2>
                <div class="pms-about__tools">
                    <a href="<?php echo esc_url( pms_admin_url( 'menus' ) ); ?>">
                        <strong>Menús</strong><span>Crea y edita los menús disponibles.</span>
                    </a>
                    <a href="<?php echo esc_url( pms_admin_url( 'calendar' ) ); ?>">
                        <strong>Calendario</strong><span>Organiza el menú de cada semana.</span>
                    </a>
                    <a href="<?php echo esc_url( pms_admin_url( 'shortcodes' ) ); ?>">
                        <strong>Shortcodes</strong><span>Añade los menús a cualquier página.</span>
                    </a>
                    <a href="<?php echo esc_url( pms_admin_url( 'appearance' ) ); ?>">
                        <strong>Apariencia</strong><span>Personaliza el diseño y los colores.</span>
                    </a>
                </div>
            </section>
        </div>
        <div class="pms-about__details">
            <section class="pms-about__panel">
                <h2>Proyecto abierto</h2>
                <p>El código se distribuye bajo licencia GPL v3 o posterior. Puedes estudiarlo, modificarlo y redistribuirlo respetando la licencia y los avisos de autoría.</p>
                <a href="<?php echo esc_url( PMS_REPOSITORY_URL ); ?>" target="_blank" rel="noopener noreferrer">Ver repositorio en GitHub</a>
            </section>
            <section class="pms-about__panel">
                <h2>Actualizaciones</h2>
                <p>Las versiones estables se reciben desde GitHub Releases mediante el actualizador normal de WordPress.</p>
                <p><strong>Versión instalada:</strong> <?php echo esc_html( PMS_VERSION ); ?></p>
            </section>
            <section class="pms-about__panel">
                <h2>Soporte</h2>
                <p>¿Necesitas adaptar el plugin, integrarlo con otro sistema o desarrollar una solución a medida?</p>
                <div class="pms-about__actions">
                    <a class="button button-primary" href="mailto:hola@premiero.es">Enviar un correo</a>
                    <a class="button" href="https://wa.me/34684774365" target="_blank" rel="noopener noreferrer">Contactar por WhatsApp</a>
                </div>
            </section>
        </div>
    </div>
    <?php
}
