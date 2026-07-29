<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pms_default_settings() {
    return array(
        'week_count' => 12,
        'style'      => array(
            'accent'          => '#6B1C00',
            'accent_text'     => '#FFFFFF',
            'heading'         => '#6B1C00',
            'text'            => '#292524',
            'background'      => '#FFFFFF',
            'panel_background'=> '#FAFAF9',
            'border'          => '#E7E5E4',
            'font_family'     => 'system',
            'body_size'       => 16,
            'heading_size'    => 22,
            'radius'          => 8,
            'gap'             => 24,
        ),
    );
}

function pms_get_settings() {
    $saved = get_option( PMS_SETTINGS_OPTION, array() );
    $saved = is_array( $saved ) ? $saved : array();
    $defaults = pms_default_settings();
    $settings = wp_parse_args( $saved, $defaults );
    $settings['style'] = wp_parse_args(
        isset( $saved['style'] ) && is_array( $saved['style'] ) ? $saved['style'] : array(),
        $defaults['style']
    );
    return $settings;
}

function pms_sanitize_settings( $input ) {
    $current = pms_get_settings();
    $defaults = pms_default_settings();
    $input = is_array( $input ) ? $input : array();
    $settings_tab = isset( $input['settings_tab'] ) ? sanitize_key( $input['settings_tab'] ) : '';

    if ( 'settings' === $settings_tab ) {
        $week_count = isset( $input['week_count'] ) ? absint( $input['week_count'] ) : $current['week_count'];
        $current['week_count'] = min( 52, max( 4, $week_count ) );
    }

    if ( 'appearance' === $settings_tab ) {
        $style = isset( $input['style'] ) && is_array( $input['style'] ) ? $input['style'] : array();
        foreach ( array( 'accent', 'accent_text', 'heading', 'text', 'background', 'panel_background', 'border' ) as $key ) {
            $color = isset( $style[ $key ] ) ? sanitize_hex_color( $style[ $key ] ) : '';
            $current['style'][ $key ] = $color ? $color : $defaults['style'][ $key ];
        }

        $allowed_fonts = array( 'system', 'serif', 'modern' );
        $font = isset( $style['font_family'] ) ? sanitize_key( $style['font_family'] ) : 'system';
        $current['style']['font_family'] = in_array( $font, $allowed_fonts, true ) ? $font : 'system';
        $current['style']['body_size'] = min( 24, max( 12, absint( $style['body_size'] ?? 16 ) ) );
        $current['style']['heading_size'] = min( 40, max( 16, absint( $style['heading_size'] ?? 22 ) ) );
        $current['style']['radius'] = min( 30, max( 0, absint( $style['radius'] ?? 8 ) ) );
        $current['style']['gap'] = min( 60, max( 8, absint( $style['gap'] ?? 24 ) ) );
    }

    return $current;
}

function pms_register_settings() {
    register_setting(
        'pms_settings_group',
        PMS_SETTINGS_OPTION,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'pms_sanitize_settings',
            'default'           => pms_default_settings(),
        )
    );
}
add_action( 'admin_init', 'pms_register_settings' );

function pms_get_menus_data() {
    $menus = get_option( PMS_MENUS_OPTION, array() );
    return is_array( $menus ) ? $menus : array();
}

function pms_save_menus_data( $data ) {
    update_option( PMS_MENUS_OPTION, is_array( $data ) ? $data : array() );
}

function pms_get_menu_labels() {
    $labels = get_option( PMS_LABELS_OPTION, array() );
    return is_array( $labels ) ? $labels : array();
}

function pms_get_menu_label( $menu_id ) {
    $labels = pms_get_menu_labels();
    if ( ! empty( $labels[ $menu_id ] ) ) {
        return $labels[ $menu_id ];
    }
    return ucwords( str_replace( array( '_', '-' ), ' ', $menu_id ) );
}

function pms_next_menu_id( $menus ) {
    $index = count( $menus ) + 1;
    do {
        $menu_id = 'menu_' . $index;
        $index++;
    } while ( isset( $menus[ $menu_id ] ) );
    return $menu_id;
}

function pms_week_monday( $timestamp = null ) {
    $timezone = wp_timezone();
    $date = null === $timestamp
        ? new DateTimeImmutable( 'now', $timezone )
        : ( new DateTimeImmutable( '@' . absint( $timestamp ) ) )->setTimezone( $timezone );
    return $date->modify( 'monday this week' )->format( 'Y-m-d' );
}

function pms_calendar_date( $monday, $weeks = 0 ) {
    $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $monday, wp_timezone() );
    if ( ! $date ) {
        $date = new DateTimeImmutable( 'monday this week', wp_timezone() );
    }
    return $date->modify( sprintf( '%+d weeks', (int) $weeks ) )->format( 'Y-m-d' );
}

function pms_ensure_calendar_range( $force_rotation = false ) {
    $menus = pms_get_menus_data();
    if ( empty( $menus ) ) {
        return array();
    }

    $settings = pms_get_settings();
    $week_count = min( 52, max( 4, absint( $settings['week_count'] ) ) );
    $calendar = get_option( PMS_CALENDAR_OPTION, array() );
    $calendar = is_array( $calendar ) ? $calendar : array();
    $menu_ids = array_keys( $menus );
    $monday = pms_week_monday();
    $changed = false;

    if ( $force_rotation ) {
        for ( $i = 0; $i < $week_count; $i++ ) {
            $calendar[ pms_calendar_date( $monday, $i ) ] = $menu_ids[ $i % count( $menu_ids ) ];
        }
        $changed = true;
    } else {
        for ( $i = 0; $i < $week_count; $i++ ) {
            $date = pms_calendar_date( $monday, $i );
            if ( empty( $calendar[ $date ] ) || ! isset( $menus[ $calendar[ $date ] ] ) ) {
                $calendar[ $date ] = $menu_ids[ $i % count( $menu_ids ) ];
                $changed = true;
            }
        }
    }

    ksort( $calendar );
    if ( $changed ) {
        update_option( PMS_CALENDAR_OPTION, $calendar );
    }
    return $calendar;
}

function pms_get_current_menu_id() {
    $calendar = get_option( PMS_CALENDAR_OPTION, array() );
    $calendar = is_array( $calendar ) ? $calendar : array();
    $menu_id = $calendar[ pms_week_monday() ] ?? '';
    return isset( pms_get_menus_data()[ $menu_id ] ) ? $menu_id : '';
}

function pms_titulo_refinado( $text ) {
    $text = trim( (string) $text );
    if ( '' === $text || ! function_exists( 'mb_strtolower' ) ) {
        return $text;
    }
    $lowercase = array( 'de', 'del', 'la', 'el', 'con', 'a', 'y', 'en', 'por', 'para', 'al', 'las', 'los' );
    $words = explode( ' ', mb_strtolower( $text, 'UTF-8' ) );
    foreach ( $words as $index => $word ) {
        if ( 0 === $index || ! in_array( $word, $lowercase, true ) ) {
            $words[ $index ] = mb_convert_case( mb_substr( $word, 0, 1, 'UTF-8' ), MB_CASE_UPPER, 'UTF-8' ) . mb_substr( $word, 1, null, 'UTF-8' );
        }
    }
    return implode( ' ', $words );
}

function pms_days() {
    return array( 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo' );
}

function pms_menu_rows() {
    return array(
        'primeros_1'       => 'Primeros 1',
        'primeros_2'       => 'Primeros 2',
        'primeros_3'       => 'Primeros 3',
        'primeros_4'       => 'Primeros 4',
        'primeros_5'       => 'Primeros 5',
        'segundos_carne'   => 'Segundos Carne',
        'segundos_pescado' => 'Segundos Pescado',
        'segundos_otros'   => 'Segundos Otros',
        'segundos_otros_1' => 'Segundos Otros 1',
        'segundos_otros_2' => 'Segundos Otros 2',
        'postres_1'        => 'Postres 1',
        'postres_2'        => 'Postres 2',
        'postres_3'        => 'Postres 3',
        'postres_4'        => 'Postres 4',
    );
}

function pms_row_type_and_index( $row_key ) {
    $type = strtok( $row_key, '_' );
    $indexes = array(
        'primeros_1' => 0, 'primeros_2' => 1, 'primeros_3' => 2, 'primeros_4' => 3, 'primeros_5' => 4,
        'segundos_carne' => 0, 'segundos_pescado' => 1, 'segundos_otros' => 2, 'segundos_otros_1' => 3, 'segundos_otros_2' => 4,
        'postres_1' => 0, 'postres_2' => 1, 'postres_3' => 2, 'postres_4' => 3,
    );
    return array( $type, $indexes[ $row_key ] ?? 0 );
}

function pms_sanitize_menu_data( $data ) {
    $clean = array();
    if ( ! is_array( $data ) ) {
        return $clean;
    }
    foreach ( pms_days() as $day ) {
        foreach ( array( 'primeros', 'segundos', 'postres' ) as $type ) {
            $items = isset( $data[ $day ][ $type ] ) && is_array( $data[ $day ][ $type ] )
                ? $data[ $day ][ $type ]
                : array();
            $clean[ $day ][ $type ] = array_map( 'sanitize_text_field', array_values( $items ) );
        }
    }
    return $clean;
}

function pms_font_stack( $font ) {
    $fonts = array(
        'system' => '-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif',
        'serif'  => 'Georgia,"Times New Roman",serif',
        'modern' => '"Avenir Next",Avenir,Montserrat,Arial,sans-serif',
    );
    return $fonts[ $font ] ?? $fonts['system'];
}

function pms_style_variables( $style = null ) {
    $settings = pms_get_settings();
    $style = wp_parse_args( is_array( $style ) ? $style : $settings['style'], pms_default_settings()['style'] );
    return implode(
        ';',
        array(
            '--pms-accent:' . sanitize_hex_color( $style['accent'] ),
            '--pms-accent-text:' . sanitize_hex_color( $style['accent_text'] ),
            '--pms-heading:' . sanitize_hex_color( $style['heading'] ),
            '--pms-text:' . sanitize_hex_color( $style['text'] ),
            '--pms-background:' . sanitize_hex_color( $style['background'] ),
            '--pms-panel-background:' . sanitize_hex_color( $style['panel_background'] ),
            '--pms-border:' . sanitize_hex_color( $style['border'] ),
            '--pms-font:' . pms_font_stack( $style['font_family'] ),
            '--pms-body-size:' . absint( $style['body_size'] ) . 'px',
            '--pms-heading-size:' . absint( $style['heading_size'] ) . 'px',
            '--pms-radius:' . absint( $style['radius'] ) . 'px',
            '--pms-gap:' . absint( $style['gap'] ) . 'px',
        )
    );
}

function pms_get_menu_for_render() {
    $menu_id = pms_get_current_menu_id();
    $menus = pms_get_menus_data();
    return $menu_id && isset( $menus[ $menu_id ] ) ? $menus[ $menu_id ] : null;
}

function pms_render_empty_message() {
    return current_user_can( 'manage_options' )
        ? '<p class="pms-empty">No hay un menú asignado a la semana actual. Revísalo en Menús semanales → Calendario.</p>'
        : '';
}

function pms_render_day_columns( $days, $modifier = '' ) {
    $menu = pms_get_menu_for_render();
    if ( ! is_array( $menu ) ) {
        return pms_render_empty_message();
    }

    ob_start();
    ?>
    <div class="pms-menu pms-menu--<?php echo esc_attr( $modifier ?: 'week' ); ?>" style="<?php echo esc_attr( pms_style_variables() ); ?>">
        <?php foreach ( $days as $day ) : ?>
            <?php $data = $menu[ $day ] ?? array(); ?>
            <section class="pms-day">
                <h3 class="pms-day__title"><?php echo esc_html( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $day, 'UTF-8' ) : strtoupper( $day ) ); ?></h3>
                <?php foreach ( array( 'primeros' => 'Primeros', 'segundos' => 'Segundos', 'postres' => 'Postres' ) as $type => $label ) : ?>
                    <?php $items = array_filter( $data[ $type ] ?? array(), 'strlen' ); ?>
                    <?php if ( $items ) : ?>
                        <div class="pms-course">
                            <h4><?php echo esc_html( $label ); ?></h4>
                            <?php foreach ( $items as $item ) : ?>
                                <div class="pms-dish"><?php echo esc_html( pms_titulo_refinado( $item ) ); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function pms_render_accordion( $days ) {
    $menu = pms_get_menu_for_render();
    if ( ! is_array( $menu ) ) {
        return pms_render_empty_message();
    }

    $instance_id = wp_unique_id( 'pms-accordion-' );
    ob_start();
    ?>
    <div class="pms-accordion" id="<?php echo esc_attr( $instance_id ); ?>" style="<?php echo esc_attr( pms_style_variables() ); ?>">
        <?php foreach ( $days as $index => $day ) : ?>
            <?php $data = $menu[ $day ] ?? array(); ?>
            <section class="pms-accordion__item">
                <button class="pms-accordion__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $instance_id . '-' . $index ); ?>">
                    <?php echo esc_html( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $day, 'UTF-8' ) : strtoupper( $day ) ); ?>
                </button>
                <div class="pms-accordion__content" id="<?php echo esc_attr( $instance_id . '-' . $index ); ?>" hidden>
                    <?php foreach ( array( 'primeros' => 'Primeros', 'segundos' => 'Segundos', 'postres' => 'Postres' ) as $type => $label ) : ?>
                        <?php $items = array_filter( $data[ $type ] ?? array(), 'strlen' ); ?>
                        <?php if ( $items ) : ?>
                            <div class="pms-course">
                                <h4><?php echo esc_html( $label ); ?></h4>
                                <?php foreach ( $items as $item ) : ?>
                                    <div class="pms-dish"><?php echo esc_html( pms_titulo_refinado( $item ) ); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function pms_shortcode_menu_semanal( $atts ) {
    $atts = shortcode_atts( array( 'vista' => 'semana' ), $atts, 'menu_semanal' );
    $view = sanitize_key( $atts['vista'] );
    if ( 'hoy' === $view ) {
        return pms_shortcode_menu_actual();
    }
    if ( 'fin_semana' === $view ) {
        return pms_render_day_columns( array( 'sábado', 'domingo' ), 'weekend' );
    }
    if ( 'movil' === $view ) {
        return pms_render_accordion( pms_days() );
    }
    return pms_render_day_columns( array_slice( pms_days(), 0, 5 ), 'week' );
}
add_shortcode( 'menu_semanal', 'pms_shortcode_menu_semanal' );

function pms_shortcode_menu_actual() {
    $days = pms_days();
    $day_number = (int) wp_date( 'N' );
    return pms_render_day_columns( array( $days[ $day_number - 1 ] ), 'today' );
}
add_shortcode( 'menu_actual', 'pms_shortcode_menu_actual' );

function pms_shortcode_menu_semana() {
    return pms_render_day_columns( array_slice( pms_days(), 0, 5 ), 'week' );
}
add_shortcode( 'menu_semana', 'pms_shortcode_menu_semana' );

function pms_shortcode_menu_finsemana() {
    return pms_render_day_columns( array( 'sábado', 'domingo' ), 'weekend' );
}
add_shortcode( 'menu_finsemana', 'pms_shortcode_menu_finsemana' );

function pms_shortcode_menu_semana_mobile() {
    return pms_render_accordion( array_slice( pms_days(), 0, 5 ) );
}
add_shortcode( 'menu_semana_mobile', 'pms_shortcode_menu_semana_mobile' );

function pms_shortcode_menu_finsemana_mobile() {
    return pms_render_accordion( array( 'sábado', 'domingo' ) );
}
add_shortcode( 'menu_finsemana_mobile', 'pms_shortcode_menu_finsemana_mobile' );

/*
 * Alias de funciones públicas de la versión anterior. Se mantienen para
 * integraciones que pudieran estar llamándolas directamente.
 */
if ( ! function_exists( 'ms_get_menus_data' ) ) {
    function ms_get_menus_data() {
        return pms_get_menus_data();
    }
}
if ( ! function_exists( 'ms_save_menus_data' ) ) {
    function ms_save_menus_data( $data ) {
        pms_save_menus_data( $data );
    }
}
if ( ! function_exists( 'ms_titulo_refinado' ) ) {
    function ms_titulo_refinado( $text ) {
        return pms_titulo_refinado( $text );
    }
}
