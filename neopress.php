<?php
/**
 * Plugin Name: Neopress
 * Description: Neo4j Recommendation Engine for WordPress
 * Version: 1.0
 * Author: Adam Cowley
 * Author URI: http://wecommit.co
 * License: GPLv2 or later
 * Text Domain: neopress
 */

namespace Neopress;

// No Hackers
use Laudis\Neo4j\Basic\Driver;

defined( 'ABSPATH' ) or die( 'No dice.' );

// Include Vendor Files
require_once 'vendor/autoload.php';

if ( is_admin() ) {
	add_action( 'admin_init', Neopress::class . '::init' );


	add_action( 'admin_init', Admin::class . '::init');
	add_action( 'admin_menu', Admin::class . '::menu' );

	add_action( 'save_post', Post::class . '::merge' );
} else {
	add_action( 'init', Neopress::class . '::session' );
	add_action( 'shutdown', Neopress::class . '::shutdown' );
}



