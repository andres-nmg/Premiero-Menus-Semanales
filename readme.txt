=== Premiero Menús Semanales ===
Contributors: andres-nmg
Tags: menus, restaurant, calendar, shortcode, weekly
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 3.0.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Crea, organiza y muestra menús semanales con calendario, estilos y shortcodes configurables.

== Description ==

Premiero Menús Semanales permite crear varios menús reutilizables, asignarlos a semanas desde un calendario visual y mostrarlos en cualquier página mediante shortcodes.

Incluye un editor tipo hoja de cálculo, importación y exportación CSV, acceso directo a la semana actual, personalización visual con vista previa y diseño responsive.

Las actualizaciones estables se distribuyen mediante GitHub Releases desde:

https://github.com/andres-nmg/premiero-menus-semanales/

== Installation ==

1. Descarga `premiero-menus-semanales.zip` desde la última Release.
2. Sube el archivo desde `Plugins > Añadir plugin > Subir plugin`.
3. Activa Premiero Menús Semanales.
4. Abre `Menús semanales` en el administrador.
5. Asigna tus menús en `Calendario`.
6. Inserta `[menu_semanal]` en una página.

== Frequently Asked Questions ==

= ¿Se pierden los menús de la versión 2.8? =

No. Se conservan las opciones `ms_menu_data` y `ms_menu_calendar`, además de todos los shortcodes existentes.
Al activar Premiero Menús Semanales se desactiva la versión anterior para evitar que ambos plugins registren los mismos shortcodes.

= ¿Cómo edito el menú de esta semana? =

Abre la pestaña Calendario. La primera tarjeta está marcada como Actual y contiene un enlace directo al menú asignado.

= ¿Puedo cambiar los colores y tamaños? =

Sí. La pestaña Apariencia permite configurar colores, tipografía, tamaños, separación y bordes con vista previa inmediata.

= ¿Qué shortcode debo usar? =

Se recomienda `[menu_semanal]`. También admite `vista="hoy"`, `vista="fin_semana"` y `vista="movil"`.

= ¿Cómo se reciben las actualizaciones? =

WordPress consulta la última Release estable del repositorio público. Cuando existe una versión superior y la Release incluye el ZIP instalable, aparece como una actualización normal.

== Changelog ==

= 3.0.1 =

* Corregida la generación del paquete instalable mediante GitHub Actions.
* Mejorada la lectura y validación de la versión al publicar una Release.
* Añadida una comprobación que impide incluir el archivo principal de la versión 2.8.

= 3.0.0 =

* Renombrado como Premiero Menús Semanales y eliminada la identidad específica del desarrollo original.
* Unificada toda la gestión en una interfaz con pestañas.
* Añadido calendario visual con semana actual editable.
* Corregido el cálculo de fechas para respetar la zona horaria de WordPress.
* La rotación completa huecos sin sobrescribir asignaciones manuales.
* Añadido shortcode unificado y mantenidos los cinco shortcodes anteriores.
* Añadida personalización visual con vista previa.
* Corregida la carga duplicada y las rutas incorrectas de recursos móviles.
* Protegidas las operaciones administrativas con permisos y nonces.
* Añadidas licencia, documentación y actualizaciones mediante GitHub Releases.
