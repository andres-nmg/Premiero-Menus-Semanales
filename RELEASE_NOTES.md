# Publicación de Premiero Menús Semanales

- **Versión:** `3.0.1`
- **Etiqueta:** `v3.0.1`
- **Título de la Release:** `Premiero Menús Semanales 3.0.1`

## Texto para la Release

## Premiero Menús Semanales 3.0.1

Versión correctiva que completa la distribución pública del plugin y soluciona la generación automática del ZIP instalable mediante GitHub Actions.

### Cambios principales

- Corregida la lectura de la versión del plugin dentro del workflow.
- Añadida una validación clara entre la etiqueta de GitHub y la versión instalada.
- Añadida una comprobación que evita empaquetar el archivo principal heredado de Menús Semanales 2.8.
- Verificada la creación de `premiero-menus-semanales.zip` con la carpeta y el archivo principal correctos.
- Conservados todos los menús, calendarios, ajustes y shortcodes existentes.

### Requisitos

- WordPress 5.8 o posterior.
- PHP 7.4 o posterior.

### Instalación

Descarga `premiero-menus-semanales.zip` de los archivos adjuntos de esta Release e instálalo desde `Plugins > Añadir plugin > Subir plugin`.

Las instalaciones existentes recibirán la versión `3.0.1` mediante el actualizador normal de WordPress.

> El ZIP instalable se adjunta automáticamente cuando finaliza el workflow **Build WordPress plugin release**.
