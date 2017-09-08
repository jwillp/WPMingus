<?php
/**
 * Plugin Name:     Wpmingus
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     wpmingus
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wpmingus
 */


/**
 * Wordpress.org API Version Check data fetcher
 * Gives information about the latest version of wordpress
 */
class WPVersionCheckFetcher {
    
    // URI for Version Check
    const API_URL = 'https://api.wordpress.org/core/version-check/1.7/';

    private static $dataCache = null;


    /**
     * Retreive data from the Wordpress.org API
     */
    private static function getData() {
        if(is_null(self::$dataCache)) {
            self::$dataCache = json_decode(file_get_contents(self::API_URL));
        }
        return self::$dataCache;
    }

    /**
     * Invalidates the cache
     */
    private static function invalidateCache() {
        self::$dataCache = null;
    }

    /**
    * Returns the latest version of wordpress
    */
    public static function getLatestVersion() {
       return self::getData()->offers[0]->current;
    }

    /**
     * Returns the minimum required php version for the latest wp version
     */
    public static function getMinPhpVersion() {
        return self::getData()->offers[0]->php_version;
    }

    /**
     * Returns the minimum required mysql version for the latest wp version
     */
    public static function getMinMySQLVersion() {
        return self::getData()->offers[0]->mysql_version;
    }
}


/**
 * Wordpress Update center is used to indicates if Wordpress and
 * server installations are up to date at least for the latest Wordpress version
 */
class WPUpdateCenter {
    
    /**
     * Indicates if the current wordpress isntalaltion is updated or not
     */
    public static function isWordpressOutdated() {
        return version_compare(self::getWordpressVersion(), 
                               self::getLatestWordpressVersion(), '<' );
    }
    
    /**
     * Returns the current installation of wordpress's version
     */
    public static function getWordpressVersion() {
        global $wp_version;
        return $wp_version;
    }

    /**
     * Returns the latest wordpress version
     */
    public static function getLatestWordpressVersion() {
        return WPVersionCheckFetcher::getLatestVersion();
    }


    /** 
     * Returns the current PHP version of the server
     */
    public static function getPHPVersion() {
        return PHP_VERSION;
    }

    /**
     * Returns the minimum PHP version for the latest install of wordpress
     */
    public static function getWPPHPVersion() {
        return WPVersionCheckFetcher::getMinPhpVersion();
    }

    /** 
     * Returns the current MySQL version of the server
     */
    public static function getMySQLVersion() {
        $link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
        return mysqli_get_server_info($link);
    }

    /**
     * Returns the minimum MySQL version for the latest install of wordpress
     */
    public static function getWPMySQLVersion() {
        return WPVersionCheckFetcher::getMinMySQLVersion();
    }
}



function versionCheck() {
    $latestWPVersion = WPUpdateCenter::getLatestWordpressVersion();
    $wordpressVersion = WPUpdateCenter::getWordpressVersion();

    if (WPUpdateCenter::isWordpressOutdated()) {
        echo "<p id='dolly'>Your wordpress installation is outdated:</p>";
        echo "<p> Latest version: $latestWPVersion </p>";
        echo "<p> Your version: $wordpressVersion </p>";
    } else {
        echo "<p> You have the latest version of wordpress </p>";
        echo "<p> Latest version: $latestWPVersion </p>";
        echo "<p> Your version: $wordpressVersion </p>";
    }

    $phpVersion = WPUpdateCenter::getPHPVersion();
    $mySQLVersion = WPUpdateCenter::getMySQLVersion();
    echo "<p>PHP Version: $phpVersion</p>";
    echo "<p>MySQL Version: $mySQLVersion</p>";
}

// Now we set that function up to execute when the admin_notices action is called
add_action( 'admin_notices', 'versionCheck' );







// list plugins
    
add_action( 'admin_notices', 'getPluginsInfo' );


/**
 * Returns information about plugins
 * including updates and version numbers
 */
function getPluginsInfo() {

    $plugins = array();
    foreach (get_plugins() as $plugin ) {

        // Since there is no official way to get a plugin's pluginSlug for online searching
        // we will try these things
        $compatInfo = null;

        // slugify the name of the plugin
        $slug = sanitize_title($plugin['Name']);
        $compatInfo = getPluginCompatibility($slug);

        // use text domain
        if($compatInfo == null) {
            $slug = sanitize_title($plugin['TextDomain']);
            $compatInfo = getPluginCompatibility($slug);
        }

        // use folder name
        /*if($compatInfo == null) {
            $slug = sanitize_title($plugin['TextDomain']);
            $compatInfo = getPluginCompatibility();
        }*/


        // Info we still don't have compatibility info we will provide N/A
        if($compatInfo == null){
            $compatInfo = array('tested' =>  null, 'requires' => null);
        }


        $wordpressPluginUrl = ($compatInfo->tested == null) ? null :
                                                     'https://wordpress.org/plugins/' . $slug;



        // Is it outdated ?
        $outdated = version_compare($plugin['version'], $compatInfo['latest_version'], '<' );                            



         var_dump($plugin);
        // Add plugin info to list of plugins
        $plugins[] = array_merge($plugin, 
            array("wordpress_plugin_url" => $wordpressPluginUrl,
                  "outdated" => $outdated
            ), 
            $compatInfo);
    }

    $plugins = snakizeCamelArrayKeys($plugins);
    var_dump($plugins);
    return $plugins;
}

/**
 * Fetches Wordpress's Plugin listing to get data about compatibility for a plugin
 */
function getPluginCompatibility($pluginSlug){
    $args = (object) array( 'slug' => $pluginSlug);
    $request = array( 'action' => 'plugin_information', 'timeout' => 15, 'request' => serialize( $args) );
    $url = 'http://api.wordpress.org/plugins/info/1.0/';
    $response = wp_remote_post( $url, array( 'body' => $request ) );
    $pluginInfo = unserialize( $response['body'] );

    if($pluginInfo === null) {
        return null;
    }

    return array(
        'tested' => $pluginInfo->tested, 
        'requires' => $pluginInfo->requires,
        'latest_version' => $pluginInfo->version
    );
}







/**
* A Router class that allows one to register REST API Routes
*/
class RestRouter
{
    protected $routes;

    function __construct() {
        $this->routes = array();
    }

    /**
     * Adds a rioute to the router
     */
    public function addRoute($route, $method, $callback) {
        $this->routes[] = array(
            'prefix' => 'mingus/v1',
            'route' => $route,
            'method' => $method,
            'callback' => $callback
        );
        return $this;
    }

    /**
     * Initialises the Router
     */
    public function init() {
        $self = $this;
        add_action('rest_api_init', function() use ($self){
            $self->run();
        });
    }

    /**
     * Registers all the routes with the wordpress hook
     */
    public function run() {

        foreach ($this->routes as $route) {
            register_rest_route($route['prefix'], $route['route'], array(
                                'methods' => $route['method'],
                                'callback' => $route['callback']
                )
            );
        }
    }
}

$router = new RestRouter();


$router->addRoute('/version', 'GET', function(){
    return array(
        'wp_version' => WPUpdateCenter::getWordpressVersion(),
        'wp_latest_version' => WPUpdateCenter::getLatestWordpressVersion(),
        'php_version' => WPUpdateCenter::getPHPVersion(),
        'mysql_version' => WPUpdateCenter::getMySQLVersion(),


        'server_software' => $_SERVER['SERVER_SOFTWARE'],
        'server_name' => $_SERVER['SERVER_NAME'],
        'server_addr' => $_SERVER['SERVER_ADDR'],
        'server_port' => $_SERVER['SERVER_PORT'],
        'server_document_root' => $_SERVER['DOCUMENT_ROOT']
    );
});

$router->init();









/**
 * Changes camelCase to snake_keys of an multidimensional array 
 */
function snakizeCamelArrayKeys($origArr)
{
    $arr = [];
    foreach ($origArr as $key => $value) {
        $key = snakizeCamel($key);

        if (is_array($value)){
            $value = snakizeCamelArrayKeys($value);
        }
      $arr[$key] = $value;
  }
  return $arr;
}

function snakizeCamel($input) {
    if(!$input) return $input;
    preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
    $ret = $matches[0];
    foreach ($ret as &$match) {
        $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
    }
    return implode('_', $ret);
}






add_action( 'admin_menu', function(){    
        add_menu_page( 'WPMingus Dashboard', 'WPMingus Dashboard', 'manage_options', 'wpmingus/dashboard.php', 'myplguin_admin_page', 'dashicons-tickets', 0);
});





















