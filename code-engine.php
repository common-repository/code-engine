<?php
/**
* Plugin Name: Code Engine
* Plugin URI: https://meowapps.com/ai-engine/
* Description: Code Engine helps you manage code snippets from Code Snippets and WP Code. It checks errors, gathers snippets, and creates JSON for AI models.
* Version: 0.0.2
* Author: Meow Apps
* Author URI: https://meowapps.com
* Text Domain: code-engine
* License: GPL v2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CDEGN_VERSION', '0.0.2' );
define( 'CDEGN_PREFIX', 'cdegn' );
define( 'CDEGN_DOMAIN', 'code-engine' );
define( 'CDEGN_ENTRY', __FILE__ );
define( 'CDEGN_PATH', dirname( __FILE__ ) );
define( 'CDEGN_URL', plugin_dir_url( __FILE__ ) );

require_once( 'classes/init.php' );

?>
