<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integra las versiones estables de GitHub Releases con el actualizador
 * nativo de WordPress. La release debe incluir PMS_RELEASE_ASSET.
 */
final class Premiero_Menus_Semanales_Updater {
    const CACHE_KEY = 'premiero_pms_github_release';

    public static function init() {
        add_filter( 'update_plugins_github.com', array( __CLASS__, 'check_update' ), 10, 4 );
        add_filter( 'plugins_api', array( __CLASS__, 'plugin_information' ), 10, 3 );
    }

    public static function get_latest_release( $force = false ) {
        if ( $force ) {
            delete_site_transient( self::CACHE_KEY );
        }

        $cached = get_site_transient( self::CACHE_KEY );
        if ( false !== $cached && is_array( $cached ) ) {
            if ( ! empty( $cached['_premiero_error'] ) ) {
                return new WP_Error(
                    isset( $cached['code'] ) ? sanitize_key( $cached['code'] ) : 'premiero_pms_cached',
                    isset( $cached['message'] ) ? sanitize_text_field( $cached['message'] ) : 'No se pudo consultar GitHub.'
                );
            }
            return $cached;
        }

        $response = wp_remote_get(
            PMS_RELEASE_API,
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept'               => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                    'User-Agent'           => 'Premiero-Menus-Semanales/' . PMS_VERSION,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            self::cache_error( $response );
            return $response;
        }

        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $error = new WP_Error( 'premiero_pms_github_http', 'GitHub no devolvió una versión estable disponible.' );
            self::cache_error( $error );
            return $error;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ), true );
        if (
            ! is_array( $release )
            || empty( $release['tag_name'] )
            || ! empty( $release['draft'] )
            || ! empty( $release['prerelease'] )
        ) {
            $error = new WP_Error( 'premiero_pms_github_release', 'La respuesta de GitHub no contiene una versión estable válida.' );
            self::cache_error( $error );
            return $error;
        }

        set_site_transient( self::CACHE_KEY, $release, 30 * MINUTE_IN_SECONDS );
        return $release;
    }

    private static function cache_error( $error ) {
        set_site_transient(
            self::CACHE_KEY,
            array(
                '_premiero_error' => true,
                'code'             => $error->get_error_code(),
                'message'          => $error->get_error_message(),
            ),
            10 * MINUTE_IN_SECONDS
        );
    }

    public static function release_version( $release ) {
        $version = isset( $release['tag_name'] )
            ? ltrim( (string) $release['tag_name'], "vV \t\n\r\0\x0B" )
            : '';
        return preg_match( '/^\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ? $version : '';
    }

    public static function release_package( $release ) {
        $assets = isset( $release['assets'] ) && is_array( $release['assets'] )
            ? $release['assets']
            : array();
        foreach ( $assets as $asset ) {
            if (
                isset( $asset['name'], $asset['browser_download_url'] )
                && PMS_RELEASE_ASSET === $asset['name']
            ) {
                return esc_url_raw( $asset['browser_download_url'] );
            }
        }
        return '';
    }

    public static function check_update( $update, $plugin_data, $plugin_file, $locales ) {
        if ( plugin_basename( PMS_PLUGIN_FILE ) !== $plugin_file ) {
            return $update;
        }

        $update_uri = isset( $plugin_data['UpdateURI'] ) ? trailingslashit( $plugin_data['UpdateURI'] ) : '';
        if ( PMS_REPOSITORY_URL !== $update_uri ) {
            return $update;
        }

        $release = self::get_latest_release();
        if ( is_wp_error( $release ) ) {
            return false;
        }

        $version = self::release_version( $release );
        $package = self::release_package( $release );
        if ( ! $version || ! $package ) {
            return false;
        }

        return array(
            'version'      => $version,
            'slug'         => PMS_PLUGIN_SLUG,
            'url'          => isset( $release['html_url'] ) ? esc_url_raw( $release['html_url'] ) : PMS_REPOSITORY_URL,
            'package'      => $package,
            'requires_php' => '7.4',
        );
    }

    public static function plugin_information( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || PMS_PLUGIN_SLUG !== $args->slug ) {
            return $result;
        }

        $release = self::get_latest_release();
        if ( is_wp_error( $release ) ) {
            return $result;
        }

        $version = self::release_version( $release );
        if ( ! $version ) {
            return $result;
        }

        $notes = ! empty( $release['body'] )
            ? wpautop( esc_html( $release['body'] ) )
            : '<p>Consulta los cambios de esta versión en GitHub.</p>';

        return (object) array(
            'name'          => 'Premiero Menús Semanales',
            'slug'          => PMS_PLUGIN_SLUG,
            'version'       => $version,
            'author'        => '<a href="https://premiero.es">Premiero</a>',
            'homepage'      => PMS_REPOSITORY_URL,
            'download_link' => self::release_package( $release ),
            'requires'      => '5.8',
            'requires_php'  => '7.4',
            'sections'      => array(
                'description' => '<p>Gestión visual de menús semanales con calendario, estilos y shortcodes configurables.</p>',
                'changelog'   => $notes,
            ),
        );
    }
}
