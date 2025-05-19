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

    if (isset($_POST['guardar_calendario']) && isset($_POST['calendario'])) {
        $nuevo_calendario = [];
        foreach ($_POST['calendario'] as $fecha => $semana_id) {
            $fecha = sanitize_text_field($fecha);
            $semana_id = sanitize_text_field($semana_id);
            $nuevo_calendario[$fecha] = $semana_id;
        }
        update_option('ms_menu_calendar', $nuevo_calendario);
        echo '<div class="updated"><p>Calendario actualizado correctamente.</p></div>';
        $calendario = $nuevo_calendario;
    }

    echo '<form method="post" style="margin-bottom: 20px;">';
    echo '<input type="submit" name="regenerar_calendario" class="button" value="🔁 Regenerar calendario desde esta semana">';
    echo '</form>';

    echo '<form method="post">';
    echo '<table class="widefat striped"><thead><tr><th>Semana</th><th>Menú asignado</th></tr></thead><tbody>';

    ksort($calendario);
    foreach ($calendario as $fecha => $semana_id) {
        echo '<tr>';
        echo "<td><strong>" . date('d/m/Y', strtotime($fecha)) . "</strong></td>";
        echo '<td>';
        echo "<select name='calendario[" . esc_attr($fecha) . "]'>";
        foreach ($menus as $id => $data) {
            $selected = ($id === $semana_id) ? 'selected' : '';
            echo "<option value='$id' $selected>" . ucfirst(str_replace('_', ' ', $id)) . "</option>";
        }
        echo '</select>';
        echo '</td></tr>';
    }

    echo '</tbody></table><br>';
    echo '<input type="submit" name="guardar_calendario' . "' class='button button-primary' value='Guardar cambios'>";
    echo '</form>';
    echo '</div>'; // cierre .ms-box
    echo '</div>'; // cierre .wrap
}
