<?php

function ms_render_menus_tab() {
    $dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
    $filas = [
        'primeros_1'       => 'Primeros 1',
        'primeros_2'       => 'Primeros 2',
        'primeros_3'       => 'Primeros 3',
		'primeros_4'       => 'Primeros 4',
		'primeros_5'       => 'Primeros 5',
        'segundos_carne'   => 'Segundos Carne',
        'segundos_pescado' => 'Segundos Pescado',
        'segundos_otros'   => 'Segundos Otros',
		'segundos_otros_1'   => 'Segundos Otros 1',
		'segundos_otros_2'   => 'Segundos Otros 2',
        'postres_1'        => 'Postres 1',
        'postres_2'        => 'Postres 2',
        'postres_3'        => 'Postres 3',
		'postres_4'        => 'Postres 4',
    ];

    $menus = ms_get_menus_data();

    if (isset($_GET['duplicar'])) {
        $duplicar = sanitize_text_field($_GET['duplicar']);
        if (isset($menus[$duplicar])) {
            $nueva_clave = 'semana_' . (count($menus) + 1);
            $menus[$nueva_clave] = $menus[$duplicar];
            ms_save_menus_data($menus);
            wp_redirect(admin_url('admin.php?page=menus-semanales'));
            exit;
        }
    }

    if (isset($_GET['eliminar'])) {
        $eliminar = sanitize_text_field($_GET['eliminar']);
        if (isset($menus[$eliminar])) {
            unset($menus[$eliminar]);
            ms_save_menus_data($menus);
            wp_redirect(admin_url('admin.php?page=menus-semanales'));
            exit;
        }
    }

    if (isset($_POST['crear_nueva_semana'])) {
        $nueva_clave = 'semana_' . (count($menus) + 1);
        $menus[$nueva_clave] = [];
        ms_save_menus_data($menus);
        wp_redirect(admin_url('admin.php?page=menus-semanales'));
        exit;
    }

    echo '<div class="wrap">';
    echo '<div class="ms-header">';
    echo '<h1>Menús Semanales - Casa Macario</h1>';
    echo '<img src="' . plugin_dir_url(__FILE__) . '../img/logo-premiero.png" alt="Logo Premiero">';
    echo '</div>';

    echo '<h2>Editar Menús</h2>';
    echo '<div class="ms-box">';
    echo '<p>Edita los menús de cada semana visualmente en una tabla editable. Puedes duplicar, eliminar o crear nuevas semanas. También puedes copiar y pegar directamente desde Excel.</p>';

    if (isset($_GET['editar'])) {
        $semana = sanitize_text_field($_GET['editar']);
        $datos = $menus[$semana] ?? [];

        if (isset($_POST['menu_json'])) {
            $json = stripslashes($_POST['menu_json']);
            $array = json_decode($json, true);
            if (is_array($array)) {
                $menus[$semana] = $array;
                ms_save_menus_data($menus);
                echo '<div class="updated"><p>Menú guardado correctamente.</p></div>';
            } else {
                echo '<div class="error"><p>Error al decodificar los datos.</p></div>';
            }
        }

        echo '<form method="post" id="menu-form">';
        echo '<input type="hidden" name="menu_json" id="menu_json">';
        echo '<input type="file" id="input-csv" accept=".csv" style="display:none;">';

        echo '<p>';
        echo '<button type="button" class="button" id="vaciar-tabla">🧹 Vaciar tabla</button> ';
        echo '<button type="button" class="button" id="exportar-csv">📤 Exportar CSV</button> ';
        echo '<button type="button" class="button" id="importar-csv">📥 Importar CSV</button> ';
        echo '<button type="submit" class="button button-primary">💾 Guardar cambios</button>';
        echo '</p>';

        echo '<table id="menu-table" class="widefat striped" style="table-layout: fixed;">';
        echo '<thead><tr><th style="width:140px;">Tipo de plato</th>';
        foreach ($dias as $dia) {
            echo '<th>' . ucfirst($dia) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($filas as $clave => $etiqueta) {
            echo "<tr><td><strong>$etiqueta</strong></td>";
            foreach ($dias as $dia) {
                $tipo = explode('_', $clave)[0];
                $index = match ($clave) {
                    'primeros_1' => 0, 'primeros_2' => 1, 'primeros_3' => 2, 'primeros_4' => 3, 'primeros_5' => 4,
                    'segundos_carne' => 0, 'segundos_pescado' => 1, 'segundos_otros' => 2, 'segundos_otros_1' => 3, 'segundos_otros_2' => 4,
                    'postres_1' => 0, 'postres_2' => 1, 'postres_3' => 2, 'postres_4' => 3,
                };
                $valor = $datos[$dia][$tipo][$index] ?? '';
                echo "<td contenteditable='true' data-dia='$dia' data-tipo='$tipo' data-index='$index'>" . esc_html($valor) . "</td>";
            }
            echo "</tr>";
        }

        echo '</tbody></table>';
        echo '</form>';

        // SCRIPTS
        echo '<script>
        // GUARDAR
        document.getElementById("menu-form")?.addEventListener("submit", function(e) {
            const tabla = document.getElementById("menu-table");
            const data = {};
            tabla.querySelectorAll("td[contenteditable]").forEach(td => {
                const dia = td.dataset.dia;
                const tipo = td.dataset.tipo;
                const index = td.dataset.index;
                if (!data[dia]) data[dia] = {};
                if (!data[dia][tipo]) data[dia][tipo] = [];
                data[dia][tipo][index] = td.innerText.trim();
            });
            document.getElementById("menu_json").value = JSON.stringify(data);
        });

        // EXPORTAR
        document.getElementById("exportar-csv")?.addEventListener("click", function () {
            const tabla = document.getElementById("menu-table");
            if (!tabla) return;
            let csv = "";
            const filas = tabla.querySelectorAll("tr");
            filas.forEach(fila => {
                const celdas = fila.querySelectorAll("th, td");
                const valores = [];
                celdas.forEach(celda => {
                    const valor = celda.textContent.replace(/"/g, \'""\');
                    valores.push(`"${valor}"`);
                });
                csv += valores.join(",") + "\\n";
            });
            const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
            const url = URL.createObjectURL(blob);
            const enlace = document.createElement("a");
            enlace.href = url;
            enlace.download = "menu_semanal.csv";
            enlace.style.display = "none";
            document.body.appendChild(enlace);
            enlace.click();
            document.body.removeChild(enlace);
            URL.revokeObjectURL(url);
        });

        // VACIAR
        document.getElementById("vaciar-tabla")?.addEventListener("click", () => {
            document.querySelectorAll("#menu-table td[contenteditable]").forEach(cell => {
                cell.innerText = "";
            });
        });

        // IMPORTAR
        document.getElementById("importar-csv")?.addEventListener("click", () => {
            document.getElementById("input-csv").click();
        });

        document.getElementById("input-csv")?.addEventListener("change", function () {
            const archivo = this.files[0];
            if (!archivo) return;
            const lector = new FileReader();
            lector.onload = function (e) {
                const contenido = e.target.result;
                const lineas = contenido.trim().split("\\n").map(l => l.split(",").map(c => c.replace(/^"|"$/g, "").replace(/""/g, \'"\')));
                const filas = document.querySelectorAll("#menu-table tbody tr");
                for (let i = 0; i < filas.length; i++) {
                    const celdas = filas[i].querySelectorAll("td");
                    const valores = lineas[i + 1];
                    if (!valores) continue;
                    for (let j = 1; j < celdas.length && j < valores.length; j++) {
                        celdas[j].innerText = valores[j] || "";
                    }
                }
            };
            lector.readAsText(archivo);
        });

        // PEGADO DIRECTO DESDE EXCEL
        document.getElementById("menu-table")?.addEventListener("paste", function (e) {
            const active = document.activeElement;
            if (!active || active.tagName !== "TD" || !active.hasAttribute("contenteditable")) return;

            e.preventDefault();
            const texto = (e.clipboardData || window.clipboardData).getData("text");
            const filas = texto.trim().split(/\\r?\\n/).map(row => row.split("\\t"));

            let fila = active.parentElement;
            let celdaIndex = Array.from(fila.children).indexOf(active);

            for (let i = 0; i < filas.length; i++) {
                const celdas = fila.children;
                for (let j = 0; j < filas[i].length && (celdaIndex + j) < celdas.length; j++) {
                    const target = celdas[celdaIndex + j];
                    if (target && target.hasAttribute("contenteditable")) {
                        target.innerText = filas[i][j];
                    }
                }
                fila = fila.nextElementSibling;
                if (!fila) break;
            }
        });
        </script>';

    } else {
        echo '<form method="post" style="margin-bottom: 20px;">';
        echo '<input type="submit" name="crear_nueva_semana" class="button" value="+ Crear nueva semana vacía">';
        echo '</form>';

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Semana</th><th>Acciones</th></tr></thead><tbody>';
        foreach ($menus as $clave => $data) {
            $nombre = ucfirst(str_replace('_', ' ', $clave));
            $url_base = admin_url('admin.php?page=menus-semanales');
            echo "<tr><td><strong>$nombre</strong></td><td>";
            echo "<a href='$url_base&editar=$clave' class='button'>Editar</a> ";
            echo "<a href='$url_base&duplicar=$clave' class='button'>Duplicar</a> ";
            echo "<a href='$url_base&eliminar=$clave' class='button' onclick=\"return confirm('¿Eliminar esta semana?')\">Eliminar</a>";
            echo "</td></tr>";
        }
        echo '</tbody></table>';
    }

    echo '</div>'; // .ms-box
    echo '</div>'; // .wrap
}
