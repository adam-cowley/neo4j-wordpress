<?php
/**
 * Plugin Name: Neopress
 * Description: Neo4j Recommendation Engine for WordPress
 * Version: 1.0
 * Author: Adam Cowley
 * Author URI: http://wecommit.co
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * Text Domain: neopress
 */

/*
Neopress is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Neopress is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Neopress. If not, see LICENSE.md
*/

use Neopress\Admin;
use Neopress\NeoPress;
use Neopress\WordpressStore;
use function Neopress\make_callable;

defined( 'ABSPATH' ) or die( 'No dice.' );

// Include Vendor Files
require_once 'vendor/autoload.php';


if ( is_admin() ) {
	add_action( 'admin_init', make_callable( [ Admin::class, 'init' ] ) );
	add_action( 'admin_menu', make_callable( [ Admin::class, 'menu' ] ), 9);

	add_action( 'save_post', make_callable( [ WordpressStore::class, 'merge' ] ));
} else {
	add_action( 'shutdown', make_callable( [ NeoPress::class, 'shutdown' ] ) );
}



