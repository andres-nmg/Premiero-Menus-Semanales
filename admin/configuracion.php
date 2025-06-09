<?php

function ms_render_configuracion_tab() {
    $menus = ms_get_menus_data();
    $total_menus = count($menus);

    echo '<div class="wrap">';
    echo '<div class="ms-header">';
    echo '<h1>Menús Semanales - Casa Macario</h1>';
    echo '<img src="' . plugin_dir_url(__FILE__) . '../img/logo-premiero.png" alt="Logo Premiero">';
    echo '</div>';

    echo '<h2>Configuración de Rotación</h2>';
    echo '<div class="ms-box">';

    if ($total_menus === 0) {
        echo '<p>No hay menús creados aún. Crea al menos uno para configurar la rotación.</p></div></div>';
        return;
    }

    $calendario = get_option('ms_menu_calendar', []);
    $hoy = date('Y-m-d');
    $inicio = strtotime('monday this week');

    // Guardar cambios manuales
    if (isset($_POST['guardar_calendario']) && isset($_POST['calendario'])) {
        $nuevo_calendario = [];
        foreach ($_POST['calendario'] as $fecha => $semana_id) {
            $fecha = sanitize_text_field($fecha);
            $semana_id = sanitize_text_field($semana_id);
            if (isset($menus[$semana_id])) {
                $nuevo_calendario[$fecha] = $semana_id;
            }
        }
        update_option('ms_menu_calendar', $nuevo_calendario);
        $calendario = $nuevo_calendario;
        echo '<div class="updated"><p>Calendario actualizado correctamente.</p></div>';
    }

    // Regenerar calendario desde esta semana
    if (isset($_POST['regenerar_calendario'])) {
        $calendario = [];
        for ($i = 0; $i < 12; $i++) {
            $fecha = date('Y-m-d', strtotime("+{$i} week", $inicio));
            $semana_id = array_keys($menus)[$i % $total_menus];
            $calendario[$fecha] = $semana_id;
        }
        update_option('ms_menu_calendar', $calendario);
        echo '<div class="updated"><p>Calendario regenerado a partir de esta semana.</p></div>';
    }

    // Autocompletar futuras si hay menos de 12 semanas
    $fechas_futuras = array_filter(array_keys($calendario), function($fecha) use ($hoy) {
        return $fecha >= $hoy;
    });

    if (count($fechas_futuras) < 12) {
        $semanas_existentes = array_keys($calendario);
        $ultimo_lunes = !empty($fechas_futuras) ? max($fechas_futuras) : date('Y-m-d', strtotime('monday this week'));
        $inicio_auto = strtotime('+1 week', strtotime($ultimo_lunes));
        $offset = count($semanas_existentes);
        for ($i = 0; $i < (12 - count($fechas_futuras)); $i++) {
            $fecha = date('Y-m-d', strtotime("+{$i} week", $inicio_auto));
            $semana_id = array_keys($menus)[($offset + $i) % $total_menus];
            $calendario[$fecha] = $semana_id;
        }
        update_option('ms_menu_calendar', $calendario);
    }

    // Formulario
    echo '<form method="post">';
    echo '<table class="widefat striped" style="max-width: 600px;">';
    echo '<thead><tr><th style="width:150px;">Semana</th><th>Menú asignado</th></tr></thead><tbody>';

    ksort($calendario);
    foreach ($calendario as $fecha => $semana_id) {
        echo '<tr>';
        echo '<td>' . esc_html($fecha) . '</td>';
        echo '<td>';
        echo '<select name="calendario[' . esc_attr($fecha) . ']">';
        foreach ($menus as $clave => $_) {
            $selected = ($clave === $semana_id) ? 'selected' : '';
            echo '<option value="' . esc_attr($clave) . '" ' . $selected . '>' . ucfirst(str_replace('_', ' ', $clave)) . '</option>';
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<p style="margin-top: 20px;">';
    echo '<button type="submit" name="guardar_calendario" class="button button-primary">💾 Guardar cambios</button> ';
    echo '<button type="submit" name="regenerar_calendario" class="button">🔄 Regenerar calendario desde esta semana</button>';
    echo '</p>';
    echo '</form>';

    echo '</div>'; // .ms-box
    echo '</div>'; // .wrap
}
