<?php

function ms_render_soporte_tab() {
    echo '<div class="wrap">';

    // Encabezado con logo
    echo '<div class="ms-header">';
    echo '<h1>Menús Semanales - Casa Macario</h1>';
    echo '<img src="' . plugin_dir_url(__FILE__) . '../img/logo-premiero.png" alt="Logo Premiero">';
    echo '</div>';

    // Sección Ayuda
    echo '<h2>Ayuda</h2>';
    echo '<div class="ms-box">';
    echo '<p><strong>¿Cómo usar el plugin?</strong></p>';
    echo '<ul>';
    echo '<li>🧩 Inserta los <strong>shortcodes</strong> en cualquier página, entrada o widget para mostrar el menú de la semana: <code>[menu_actual]</code> y para la fecha <code>[dia_actual]</code></li>';
    echo '<li>🛠️ En la pestaña <strong>Editar Menús</strong> puedes editar el contenido de cada semana visualmente con una tabla editable tipo Excel.</li>';
    echo '<li>📅 En la pestaña <strong>Configuración</strong> puedes gestionar la rotación automática de los menús, con vista por semanas.</li>';
    echo '</ul>';
    echo '</div>';

    // Sección Soporte
    echo '<h2>Soporte</h2>';
    echo '<div class="ms-box">';
    echo '<p>Plugin desarrollado por <strong>Premiero</strong> para la gestión de los menús de Casa Macario.</p>';
    echo '<p>Visítanos: <a href="https://premiero.es" target="_blank">https://premiero.es</a></p>';

    echo '<p><strong>¿Necesitas soporte?</strong> Puedes contactarnos a través de:</p>';
    echo '<ul>';
    echo '<li>📞 <a href="tel:+34684774365">+34 684 774 365</a></li>';
    echo '<li>📧 <a href="mailto:hola@premiero.es">hola@premiero.es</a></li>';
    echo '<li>💬 <a href="https://wa.me/34684774365" target="_blank" class="button button-primary">Enviar mensaje por WhatsApp</a></li>';
    echo '</ul>';
    echo '</div>';

    echo '</div>';
}

