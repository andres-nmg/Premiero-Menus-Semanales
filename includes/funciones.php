<?php

function ms_get_menus_data() {
    return get_option('ms_menu_data', []);
}

function ms_save_menus_data($data) {
    update_option('ms_menu_data', $data);
}

function ms_titulo_refinado($texto) {
    $minis = ['de', 'del', 'la', 'el', 'con', 'a', 'y', 'en', 'por', 'para', 'al', 'las', 'los'];
    $palabras = explode(' ', mb_strtolower($texto, 'UTF-8'));
    foreach ($palabras as $i => $palabra) {
        if ($i === 0 || !in_array($palabra, $minis)) {
            $palabras[$i] = mb_convert_case(mb_substr($palabra, 0, 1, 'UTF-8'), MB_CASE_UPPER, 'UTF-8') . mb_substr($palabra, 1, null, 'UTF-8');
        }
    }
    return implode(' ', $palabras);
}

// --------------------------------------------------
// SHORTCODE: Menú del día (actual)
// --------------------------------------------------

function ms_get_menu_for_today() {
    $calendario = get_option('ms_menu_calendar', []);
    $menus = ms_get_menus_data();
    $lunes_actual = date('Y-m-d', strtotime('monday this week'));
    $semana_id = $calendario[$lunes_actual] ?? null;
    if (!$semana_id || !isset($menus[$semana_id])) return '';

    $menu = $menus[$semana_id];
    $dia_actual = strtolower(date('l'));
    $trad = ['monday'=>'lunes','tuesday'=>'martes','wednesday'=>'miércoles','thursday'=>'jueves','friday'=>'viernes','saturday'=>'sábado','sunday'=>'domingo'];
    $dia = $trad[$dia_actual] ?? 'lunes';

    $data = $menu[$dia] ?? [];

    ob_start(); ?>
    <div class="ms-menu-actual">
        <div class="ms-menu-column">
            <h3>PRIMEROS</h3><div class="ms-divider"></div>
            <?php foreach ($data['primeros'] ?? [] as $item): ?>
                <div class="ms-item"><?= esc_html(ms_titulo_refinado($item)) ?></div>
            <?php endforeach; ?>
        </div>
        <div class="ms-menu-column">
            <h3>SEGUNDOS</h3><div class="ms-divider"></div>
            <?php foreach ($data['segundos'] ?? [] as $item): ?>
                <div class="ms-item"><?= esc_html(ms_titulo_refinado($item)) ?></div>
            <?php endforeach; ?>
        </div>
        <div class="ms-menu-column">
            <h3>POSTRES</h3><div class="ms-divider"></div>
            <?php foreach ($data['postres'] ?? [] as $item): ?>
                <div class="ms-item"><?= esc_html(ms_titulo_refinado($item)) ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400&family=Source+Sans+Pro:wght@400&display=swap');

    .ms-menu-actual {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 40px;
        margin: 30px 0;
        text-align: center;
        font-family: 'Source Sans Pro', sans-serif;
    }
    .ms-menu-column {
        flex: 1 1 300px;
        max-width: 300px;
    }
    .ms-menu-column h3 {
        font-family: 'Playfair Display', serif;
        font-size: 22px;
        font-weight: 400;
        color: #a88f26;
        margin-bottom: 10px;
    }
    .ms-divider {
        height: 2px;
        background-color: #a88f26;
        width: 100px;
        margin: 0 auto 20px auto;
    }
    .ms-item {
        font-size: 16px;
        color: #002a49;
        margin-bottom: 14px;
        word-break: break-word;
    }
    </style>
    <?php return ob_get_clean();
}
add_shortcode('menu_actual', 'ms_get_menu_for_today');


// --------------------------------------------------
// SHORTCODES ESCRITORIO: Semana y Fin de Semana
// --------------------------------------------------

add_shortcode('menu_semana', 'ms_shortcode_menu_semana');
function ms_shortcode_menu_semana() {
    $dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'];
    return ms_get_menu_formateado_semana($dias);
}

add_shortcode('menu_finsemana', 'ms_shortcode_menu_finsemana');
function ms_shortcode_menu_finsemana() {
    $dias = ['sábado', 'domingo'];
    return ms_get_menu_formateado_finsemana($dias);
}

function ms_get_menu_formateado_semana($dias) {
    $calendario = get_option('ms_menu_calendar', []);
    $menus = ms_get_menus_data();
    $lunes_actual = date('Y-m-d', strtotime('monday this week'));
    $semana_id = $calendario[$lunes_actual] ?? null;
    if (!$semana_id || !isset($menus[$semana_id])) return '';

    $menu = $menus[$semana_id];
    ob_start();

    echo '<div class="ms-menu-semana">';
    foreach ($dias as $dia) {
        $data = $menu[$dia] ?? ['primeros' => [], 'segundos' => [], 'postres' => []];
        echo '<div class="ms-dia">';
        echo '<div class="ms-dia-titulo">' . mb_strtoupper($dia, 'UTF-8') . '<div class="ms-dia-linea"></div></div>';
        foreach (['primeros' => 'Primeros', 'segundos' => 'Segundos', 'postres' => 'Postres'] as $tipo => $label) {
            echo "<div class='ms-bloque'>";
            echo "<div class='ms-tipo'>" . esc_html($label) . "</div>";
            foreach ($data[$tipo] ?? [] as $item) {
                echo "<div class='ms-plato'>" . esc_html(ms_titulo_refinado($item)) . "</div>";
            }
            echo "</div>";
        }
        echo '</div>';
    }
    echo '</div>';

    ?>
    <style>
    .ms-menu-semana {
        display: flex;
        justify-content: center;
        gap: 50px;
        flex-wrap: nowrap;
        overflow-x: auto;
        margin-top: 30px;
        font-family: 'Source Sans Pro', sans-serif;
    }
    .ms-dia {
        width: 20%;
        text-align: center;
    }
    .ms-dia-titulo {
        font-family: 'Playfair Display', serif;
        font-size: 22px;
        font-weight: 700;
        color: #a88f26;
        position: relative;
        margin-bottom: 12px;
    }
    .ms-dia-linea {
        width: 60px;
        height: 2px;
        background-color: #a88f26;
        margin: 5px auto 0;
    }
    .ms-tipo {
        font-size: 18px;
        color: #002a49;
        font-weight: 600;
        margin-top: 30px;
    }
    .ms-plato {
        font-size: 16px;
        color: #002a49;
        margin-bottom: 4px;
        word-break: break-word;
    }
    </style>
    <?php return ob_get_clean();
}

function ms_get_menu_formateado_finsemana($dias) {
    $calendario = get_option('ms_menu_calendar', []);
    $menus = ms_get_menus_data();
    $lunes_actual = date('Y-m-d', strtotime('monday this week'));
    $semana_id = $calendario[$lunes_actual] ?? null;
    if (!$semana_id || !isset($menus[$semana_id])) return '';

    $menu = $menus[$semana_id];
    ob_start();

    echo '<div class="ms-finsemana">';
    foreach ($dias as $dia) {
        $data = $menu[$dia] ?? ['primeros' => [], 'segundos' => [], 'postres' => []];
        echo '<div class="ms-fin-dia">';
        echo '<div class="ms-fin-dia-label-wrap">';
        echo '<div class="ms-fin-dia-label">' . mb_strtoupper($dia, 'UTF-8') . '</div>';
        echo '<div class="ms-fin-linea"></div>';
        echo '</div>';
        echo '<div class="ms-fin-columnas">';
        foreach (['primeros' => 'Primeros', 'segundos' => 'Segundos', 'postres' => 'Postres'] as $tipo => $label) {
            echo "<div class='ms-fin-col'>";
            echo "<div class='ms-fin-tipo'>" . esc_html($label) . "</div>";
            foreach ($data[$tipo] ?? [] as $item) {
                echo "<div class='ms-fin-plato'>" . esc_html(ms_titulo_refinado($item)) . "</div>";
            }
            echo "</div>";
        }
        echo '</div></div>';
    }
    echo '</div>';

    ?>
    <style>
    .ms-finsemana {
        display: flex;
        justify-content: center;
        gap: 100px;
        flex-wrap: wrap;
        margin-top: 40px;
        font-family: 'Source Sans Pro', sans-serif;
    }
    .ms-fin-dia {
        text-align: center;
    }
    .ms-fin-dia-label-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }
    .ms-fin-dia-label {
        background-color: #a88f26;
        color: #fff;
        padding: 6px 16px;
        border-radius: 5px;
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        font-weight: 700;
        z-index: 2;
    }
    .ms-fin-linea {
        flex-grow: 1;
        height: 2px;
        background: #a88f26;
        z-index: 1;
    }
    .ms-fin-columnas {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 10px;
    }
    .ms-fin-col {
        min-width: 100px;
    }
    .ms-fin-tipo {
        font-family: 'Playfair Display', serif;
        font-size: 22px;
        text-transform: uppercase;
        font-weight: 700;
        color: #a88f26;
        margin-bottom: 8px;
    }
    .ms-fin-plato {
        font-family: 'Source Sans Pro', sans-serif;
        font-size: 16px;
        color: #002a49;
        margin-bottom: 5px;
        word-break: break-word;
    }
    </style>
    <?php return ob_get_clean();
}

// --------------------------------------------------
// SHORTCODES MÓVILES (mantenidos aparte con estilos separados)
// --------------------------------------------------

wp_enqueue_style('ms-mobile-css', plugin_dir_url(__FILE__) . '../assets/menus-mobile.css');
wp_enqueue_script('ms-mobile-js', plugin_dir_url(__FILE__) . '../assets/menus-mobile.js', [], null, true);


add_action('wp_enqueue_scripts', 'ms_enqueue_mobile_assets');
function ms_enqueue_mobile_assets() {
    // Solo para móviles, puedes añadir condicional si deseas
    wp_enqueue_style('ms-mobile-css', plugin_dir_url(__FILE__) . '../assets/css/menus-mobile.css');
    wp_enqueue_script('ms-mobile-js', plugin_dir_url(__FILE__) . '../assets/js/menus-mobile.js', [], null, true);
}


add_shortcode('menu_semana_mobile', 'ms_shortcode_menu_semana_mobile');
function ms_shortcode_menu_semana_mobile() {
    $dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'];
    return ms_render_menu_acordeon($dias);
}

add_shortcode('menu_finsemana_mobile', 'ms_shortcode_menu_finsemana_mobile');
function ms_shortcode_menu_finsemana_mobile() {
    $dias = ['sábado', 'domingo'];
    return ms_render_menu_acordeon($dias);
}

function ms_render_menu_acordeon($dias) {
    $calendario = get_option('ms_menu_calendar', []);
    $menus = ms_get_menus_data();
    $lunes_actual = date('Y-m-d', strtotime('monday this week'));
    $semana_id = $calendario[$lunes_actual] ?? null;

    if (!$semana_id || !isset($menus[$semana_id])) return '';

    $menu = $menus[$semana_id];

    ob_start();
    echo '<div class="ms-accordion-container">';
    foreach ($dias as $dia) {
        $data = $menu[$dia] ?? ['primeros' => [], 'segundos' => [], 'postres' => []];
        $dia_mayus = mb_strtoupper($dia, 'UTF-8');

        echo '<div class="ms-accordion-item">';
        echo '<button class="ms-accordion-toggle" aria-expanded="false">' . $dia_mayus . '</button>';
        echo '<div class="ms-accordion-content" hidden>';

        foreach (['primeros' => 'Primeros', 'segundos' => 'Segundos', 'postres' => 'Postres'] as $tipo => $etiqueta) {
            echo '<div class="ms-accordion-block">';
            echo '<div class="ms-accordion-subtitle">' . $etiqueta . '</div>';
            foreach ($data[$tipo] ?? [] as $item) {
                echo '<div class="ms-accordion-plate">' . esc_html(ms_titulo_refinado($item)) . '</div>';
            }
            echo '</div>';
        }

        echo '</div></div>';
    }
    echo '</div>';
    return ob_get_clean();
}