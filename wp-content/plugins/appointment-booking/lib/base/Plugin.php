<?php
namespace Bookly\Lib\Base;

use Bookly\Lib;

/**
 * Class Plugin
 * @package Bookly\Lib\Base
 */
abstract class Plugin
{
    /**
     * Prefix for options and metas.
     *
     * @staticvar string
     */
    protected static $prefix;

    /**
     * Plugin title (used in method "title").
     *
     * @staticvar string
     */
    protected static $title;

    /**
     * Plugin version (used in method "version").
     *
     * @staticvar string
     */
    protected static $version;

    /**
     * Plugin slug (used in method "slug").
     *
     * @staticvar string
     */
    protected static $slug;

    /**
     * Path to plugin directory (used in method "directory").
     *
     * @staticvar string
     */
    protected static $directory;

    /**
     * Path to plugin main file (used in method "mainFile").
     *
     * @staticvar string
     */
    protected static $main_file;

    /**
     * Plugin basename (used in method "basename").
     *
     * @staticvar string
     */
    protected static $basename;

    /**
     * Plugin text domain (used in method "textDomain").
     *
     * @staticvar string
     */
    protected static $text_domain;

    /**
     * Root namespace of plugin classes.
     *
     * @staticvar string
     */
    protected static $root_namespace;

    /**
     * Start Bookly plugin.
     */
    public static function run()
    {
        static::registerHooks();
        static::initUpdateChecker();
        // Run updates.
        $updater_class = static::getRootNamespace() . '\Lib\Updater';
        $updater = new $updater_class();
        $updater->run();
    }

    /**
     * Activate plugin.
     *
     * @param bool $network_wide
     */
    public static function activate( $network_wide )
    {
        if ( $network_wide && has_action( 'bookly_plugin_activate' ) ) {
            do_action( 'bookly_plugin_activate', static::getSlug() );
        } else {
            $installer_class = static::getRootNamespace() . '\Lib\Installer';
            $installer = new $installer_class();
            $installer->install();
        }
    }

    /**
     * Deactivate plugin.
     *
     * @param bool $network_wide
     */
    public static function deactivate( $network_wide )
    {
        if ( $network_wide && has_action( 'bookly_plugin_deactivate' ) ) {
            do_action( 'bookly_plugin_deactivate', static::getSlug() );
        } else {
            unload_textdomain( 'bookly' );
        }
    }

    /**
     * Uninstall plugin.
     *
     * @param string|bool $network_wide
     */
    public static function uninstall( $network_wide )
    {
        if ( $network_wide !== false && has_action( 'bookly_plugin_uninstall' ) ) {
            do_action( 'bookly_plugin_uninstall', static::getSlug() );
        } else {
            $installer_class = static::getRootNamespace() . '\Lib\Installer';
            $installer = new $installer_class();
            $installer->uninstall();
        }
    }

    /**
     * Get prefix.
     *
     * @return mixed
     */
    public static function getPrefix()
    {
        if ( static::$prefix === null ) {
            static::$prefix = str_replace( array( '-addon', '-' ), array( '', '_' ), static::getSlug() ) . '_';
        }

        return static::$prefix;
    }

    /**
     * Get plugin purchase code option.
     *
     * @return string
     */
    public static function getPurchaseCode()
    {
        return static::getPrefix() . 'envato_purchase_code';
    }

    /**
     * Get plugin title.
     *
     * @return string
     */
    public static function getTitle()
    {
        if ( static::$title === null ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( static::getMainFile() );
            static::$version     = $plugin_data['Version'];
            static::$title       = $plugin_data['Name'];
            static::$text_domain = $plugin_data['TextDomain'];
        }

        return static::$title;
    }

    /**
     * Get plugin version.
     *
     * @return string
     */
    public static function getVersion()
    {
        if ( static::$version === null ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( static::getMainFile() );
            static::$version     = $plugin_data['Version'];
            static::$title       = $plugin_data['Name'];
            static::$text_domain = $plugin_data['TextDomain'];
        }

        return static::$version;
    }

    /**
     * Get plugin slug.
     *
     * @return string
     */
    public static function getSlug()
    {
        if ( static::$slug === null ) {
            static::$slug = basename( static::getDirectory() );
        }

        return static::$slug;
    }

    /**
     * Get path to plugin directory.
     *
     * @return string
     */
    public static function getDirectory()
    {
        if ( static::$directory === null ) {
            $reflector = new \ReflectionClass( get_called_class() );
            static::$directory = dirname( dirname( $reflector->getFileName() ) );
        }

        return static::$directory;
    }

    /**
     * Get path to plugin main file.
     *
     * @return string
     */
    public static function getMainFile()
    {
        if ( static::$main_file === null ) {
            static::$main_file = static::getDirectory() . '/main.php';
        }

        return static::$main_file;
    }

    /**
     * Get plugin basename.
     *
     * @return string
     */
    public static function getBasename()
    {
        if ( static::$basename === null ) {
            static::$basename = plugin_basename( static::getMainFile() );
        }

        return static::$basename;
    }

    /**
     * Get plugin text domain.
     *
     * @return string
     */
    public static function getTextDomain()
    {
        if ( static::$text_domain === null ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( static::getMainFile() );
            static::$version     = $plugin_data['Version'];
            static::$title       = $plugin_data['Name'];
            static::$text_domain = $plugin_data['TextDomain'];
        }

        return static::$text_domain;
    }

    /**
     * Get root namespace of called class.
     *
     * @return string
     */
    public static function getRootNamespace()
    {
        if ( static::$root_namespace === null ) {
            $called_class = get_called_class();
            static::$root_namespace = substr( $called_class, 0, strpos( $called_class, '\\' ) );
        }

        return static::$root_namespace;
    }

    /**
     * Check whether the plugin is network active.
     *
     * @return bool
     */
    public static function isNetworkActive()
    {
        return is_plugin_active_for_network( static::getBasename() );
    }

    /**
     * Register hooks.
     */
    public static function registerHooks()
    {
        /** @var Plugin $plugin_class */
        $plugin_class = get_called_class();

        register_activation_hook( static::getMainFile(),   array( $plugin_class, 'activate' ) );
        register_deactivation_hook( static::getMainFile(), array( $plugin_class, 'deactivate' ) );
        register_uninstall_hook( static::getMainFile(),    array( $plugin_class, 'uninstall' ) );

        add_action( 'plugins_loaded', function () use ( $plugin_class ) {
            // l10n.
            load_plugin_textdomain( $plugin_class::getTextDomain(), false, $plugin_class::getSlug() . '/languages' );
        } );

        if ( is_admin() ) {
            // Admin notice.
            add_action( 'admin_notices',  function () use ( $plugin_class ) {
                if ( current_user_can( 'manage_options' ) &&
                    get_option( $plugin_class::getPurchaseCode() ) == '' &&
                    ! get_user_meta( get_current_user_id(), $plugin_class::getPrefix() . 'dismiss_admin_notice', true ) &&
                    time() > get_option( $plugin_class::getPrefix() . 'installation_time' ) + WEEK_IN_SECONDS
                ) {
                    printf(
                        '<div id=%1$s_notice class=update-nag>
                            <h3>%2$s</h3>
                            <p>%3$s</p>
                            <p>%4$s</p>
                            <a id="%1$s_notice_dismiss" href="#">%5$s</a>
                        </div>
                        <script type="text/javascript">
                            jQuery("a#%1$s_notice_dismiss").click(function (e) {
                                e.preventDefault();
                                jQuery("#%1$s_notice").hide(300);
                                jQuery.ajax({
                                    url: "%6$s",
                                    data: {action: "ab_dismiss_admin_notice", prefix: "%1$s"}
                                });
                            });
                        </script>',
                        $plugin_class::getPrefix(),
                        $plugin_class::getTitle(),
                        sprintf(
                            __( 'Please do not forget to specify your purchase code in Bookly <a href="%s">settings</a>. Upon providing the code you will have access to free updates of %s. The updates may contain functionality improvements and important security fixes.', 'bookly' ),
                            Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Settings\Controller::page_slug, array( 'tab' => 'purchase_code' ) ),
                            $plugin_class::getTitle()
                        ),
                        sprintf(
                            __( '<b>Important!</b> Please be aware that if your copy of %1$s was not downloaded from Codecanyon (the only channel of %1$s distribution), you may put your website under significant risk - it is very likely that it contains a malicious code, a trojan or a backdoor. Please consider buying a licensed copy of %1$s <a href="http://booking-wp-plugin.com" target="_blank">here</a>.', 'bookly' ),
                            $plugin_class::getTitle()
                        ),
                        __( 'Dismiss', 'bookly' ),
                        admin_url( 'admin-ajax.php' )
                    );
                }
            }, 10, 0 );
            // Add handlers to Bookly actions.
            add_action( 'bookly_render_purchase_code', function ( $blog_id = null ) use ( $plugin_class ) {
                $purchase_code = $blog_id ? get_blog_option( $blog_id, $plugin_class::getPurchaseCode() ) : get_option( $plugin_class::getPurchaseCode() );
                printf(
                    '<div class="form-group"><h4>%1$s</h4><label for="%2$s">%3$s</label><input id="%2$s" class="purchase-code form-control" type="text" name="purchase_code[%2$s]" value="%4$s" /></div>',
                    $plugin_class::getTitle(),
                    $plugin_class::getPurchaseCode(),
                    __( 'Purchase Code', 'bookly' ),
                    $purchase_code
                );
            }, 1, 1 );

            // Add handlers to Bookly filters.
            add_filter( 'bookly_plugins', function ( array $plugins ) use ( $plugin_class ) {
                $plugins[ $plugin_class::getSlug() ] = $plugin_class;

                return $plugins;
            } );
            add_filter( 'bookly_save_purchase_codes', function ( $errors, $purchase_codes, $blog_id ) use ( $plugin_class ) {
                if ( array_key_exists( $plugin_class::getPurchaseCode(), (array) $purchase_codes ) ) {
                    $purchase_code = $purchase_codes[ $plugin_class::getPurchaseCode() ];
                    $valid = ( $purchase_code == '' ) ? true : Lib\Utils\Common::verifyPurchaseCode( $purchase_code, $plugin_class::getSlug() );
                    if ( $valid ) {
                        if ( $blog_id ) {
                            update_blog_option( $blog_id, $plugin_class::getPurchaseCode(), $purchase_code );
                        } else {
                            update_option( $plugin_class::getPurchaseCode(), $purchase_code );
                        }
                    } elseif ( $valid === null ) {
                        $errors[] = __( 'Unfortunately the service for checking the purchase code is not available at the moment, please try again later.', 'bookly' );
                    } else {
                        $errors[] = sprintf( __( '<strong>%s</strong> is not a valid purchase code for %s.', 'bookly' ), $purchase_code, $plugin_class::getTitle() );
                    }
                }

                return $errors;
            } , 10, 3 );
        }
    }

    /**
     * Init update checker.
     */
    public static function initUpdateChecker()
    {
        if ( get_option( static::getPurchaseCode() ) ) {

            include_once Lib\Plugin::getDirectory() . '/lib/utils/plugin-update-checker.php';

            add_filter( 'puc_manual_check_link-' . static::getSlug(), function () {
                return __( 'Check for updates', 'bookly' );
            } );

            add_filter( 'puc_manual_check_message-' . static::getSlug(), function ( $message, $status ) {
                switch ( $status ) {
                    case 'no_update':        return __( 'This plugin is up to date.', 'bookly' );
                    case 'update_available': return __( 'A new version of this plugin is available.', 'bookly' );
                    default:                 return sprintf( __( 'Unknown update checker status "%s"', 'bookly' ), htmlentities( $status ) );
                }
            }, 10, 2 );

            $plugin_version = static::getVersion();
            $plugin_slug    = static::getSlug();
            $purchase_code  = get_option( static::getPurchaseCode() );
            add_filter( 'puc_request_info_query_args-' . static::getSlug(), function( $queryArgs ) use ( $plugin_version, $plugin_slug, $purchase_code ) {
                global $wp_version;

                $queryArgs['api']           = '1.0';
                $queryArgs['action']        = 'update';
                $queryArgs['plugin']        = $plugin_slug;
                $queryArgs['site']          = parse_url( site_url(), PHP_URL_HOST );
                $queryArgs['versions']      = array( $plugin_version, 'wp' => $wp_version );
                $queryArgs['purchase_code'] = $purchase_code;
                unset ( $queryArgs['checking_for_updates'] );

                return $queryArgs;
            } );

            \PucFactory::buildUpdateChecker(
                'http://booking-wp-plugin.com/index.php',
                static::getMainFile(),
                static::getSlug(),
                24
            );
        } else {
            $plugin_basename = static::getBasename();
            add_filter( 'plugin_row_meta', function ( $links, $plugin ) use ( $plugin_basename )  {
                if ( $plugin == $plugin_basename ) {
                    return array_merge(
                        $links,
                        array(
                            0 => '<span class="dashicons dashicons-info"></span> ' .
                                sprintf(
                                    __( 'To update - enter the <a href="%s">Purchase Code</a>', 'bookly' ),
                                    Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Settings\Controller::page_slug, array( 'tab' => 'purchase_code' ) )
                                ),
                        )
                    );
                }
                return $links;
            }, 10, 2 );
        }
    }

}