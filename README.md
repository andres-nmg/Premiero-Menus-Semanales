# Premiero Menús Semanales

Plugin de código abierto para crear, organizar y mostrar menús semanales en WordPress.

## Funciones

- Editor visual de menús con pegado directo desde Excel o Google Sheets.
- Importación y exportación de cada menú en CSV.
- Menús reutilizables con nombres personalizados.
- Calendario visual para asignar un menú a cada semana.
- Acceso directo para editar el menú de la semana actual.
- Rotación automática que completa huecos sin sobrescribir cambios manuales.
- Shortcode unificado y compatibilidad con los shortcodes anteriores.
- Colores, tipografía, tamaños, espacios y bordes configurables con vista previa.
- Diseño responsive sin depender de servicios tipográficos externos.
- Actualizaciones estables desde GitHub Releases.

## Requisitos

- WordPress 5.8 o posterior.
- PHP 7.4 o posterior.

## Instalación

1. Descarga `premiero-menus-semanales.zip` desde la última Release.
2. En WordPress, abre `Plugins > Añadir plugin > Subir plugin`.
3. Selecciona el ZIP, instálalo y actívalo.
4. Abre `Menús semanales` en el menú de administración.
5. Crea o revisa los menús, asígnalos en `Calendario` y añade `[menu_semanal]` a una página.

### Actualización desde Menús Semanales 2.8

La versión 3.0 conserva automáticamente:

- Los menús almacenados en `ms_menu_data`.
- Las asignaciones almacenadas en `ms_menu_calendar`.
- Los shortcodes `[menu_actual]`, `[menu_semana]`, `[menu_finsemana]`, `[menu_semana_mobile]` y `[menu_finsemana_mobile]`.

Instala y activa la nueva versión con la carpeta `premiero-menus-semanales`. Durante la activación se desactiva el plugin anterior, pero no se eliminan sus opciones. Comprueba los menús y el calendario antes de borrar la carpeta antigua.

## Shortcodes

- `[menu_semanal]`: menú de lunes a viernes.
- `[menu_semanal vista="hoy"]`: menú del día.
- `[menu_semanal vista="fin_semana"]`: sábado y domingo.
- `[menu_semanal vista="movil"]`: semana completa en acordeón.

Los shortcodes anteriores siguen disponibles por compatibilidad.

## Publicación de versiones

1. Actualiza `Version` y `PMS_VERSION` en `premiero-menus-semanales.php`.
2. Actualiza `Stable tag` y el registro de cambios de `readme.txt`.
3. Sube los cambios a la rama `main`.
4. Crea una etiqueta y una Release con el mismo número, por ejemplo `v3.0.0`.
5. GitHub Actions generará y adjuntará automáticamente `premiero-menus-semanales.zip`.

El actualizador requiere el ZIP generado por el workflow. Los archivos automáticos «Source code» de GitHub no garantizan la estructura de carpeta que WordPress necesita.

El título, etiqueta y texto preparados para esta versión están en [`RELEASE_NOTES.md`](RELEASE_NOTES.md).

## Repositorio y actualizaciones

El proyecto espera publicarse en:

<https://github.com/andres-nmg/premiero-menus-semanales/>

Si cambia la URL, actualiza `Plugin URI`, `Update URI`, `PMS_REPOSITORY_URL` y `PMS_RELEASE_API`.

## Licencia

Premiero Menús Semanales se distribuye bajo **GPL-3.0-or-later**. Las versiones modificadas deben indicar claramente sus cambios y conservar los avisos de licencia y autoría.

## Soporte

- Web: <https://premiero.es>
- Correo: <hola@premiero.es>
