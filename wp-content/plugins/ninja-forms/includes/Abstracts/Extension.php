<?php if ( ! defined( 'ABSPATH' ) ) exit;

/*************************************************************/
// This class is not intended to be extended directly,
// but rather should be used as a boilerplate for an
// extension plugin base file.
// This file, if included or required, will immediately exit.
// TODO: Remove this header before use.
exit;
/*************************************************************/

/**
 * Class NF_Abstracts_Extension
 */
final class NF_Abstracts_Extension
{
    /**
     * @since 3.0
     */
    const VERSION = '';

    /**
     * @var NF_Abstracts_Extension
     * @since 3.0
     */
    private static $instance;

    /**
     * Plugin Directory
     *
     * @since 3.0
     * @var string $dir
     */
    public static $dir = '';

    /**
     * Plugin URL
     *
     * @since 3.0
     * @var string $url
     */
    public static $url = '';

    /**
     * Form Fields
     *
     * @since 3.0
     * @var array
     */
    public $fields = array();

    /**
     * Form Actions
     *
     * @since 3.0
     * @var array
     */
    public $actions = array();

    protected $autoloader_prefix = '';

    /**
     * Main Plugin Instance
     *
     * Insures that only one instance of a plugin class exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @since 3.0
     * @static
     * @staticvar array $instance
     * @return Plugin Highlander Instance
     */
    public static function instance()
    {
        if (!isset(self::$instance) && !(self::$instance instanceof NF_Abstracts_Extension)) {
            self::$instance = new NF_Abstracts_Extension();

            self::$dir = plugin_dir_path(__FILE__);

            self::$url = plugin_dir_url(__FILE__);

            /*
             * Register our autoloader
             */
            spl_autoload_register(array(self::$instance, 'autoloader'));
        }
    }

    public function autoloader( $class_name )
    {
        if( class_exists( $class_name ) ) return;

        if( ! $this->autoloader_prefix ) {
            $class = explode( '_', __CLASS__ );
            $this->autoloader_prefix = $class[ 0 ];
        }

        if ( false !== strpos( $class_name, $this->autoloader_prefix ) ) {
            $class_name = str_replace($this->autoloader_prefix, '', $class_name);
            $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
            if (file_exists($classes_dir . $class_file)) {
                require_once $classes_dir . $class_file;
            }
        }
    }
}

/**
 * The main function responsible for returning The Highlander Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $nf = NF_Abstracts_Extension(); ?>
 *
 * @since 3.0
 * @return Plugin Highlander Instance
 */
function NF_Abstracts_Extension()
{
    return NF_Abstracts_Extension::instance();
}

NF_Abstracts_Extension();
