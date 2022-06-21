<?php
/**
Plugin Name: Neopress
Description: Neo4j Recommendation Engine for Wordpress
Version: 1.0
Author: Adam Cowley
Author URI: http://wecommit.co
License: GPLv2 or later
Text Domain: neopress
*/

namespace Neopress;

// No Hackers
use Laudis\Neo4j\Basic\Driver;

defined( 'ABSPATH' ) or die( 'No dice.');

// Include Vendor Files
require_once 'vendor/autoload.php';

class Neopress {
    private static \Laudis\Neo4j\Basic\Session $_client;

    /** @var Neopress Singleton instance */
    private static $_instance;

    /** @var string User ID */
    private static $_user;

    /**
     * Make sure a session has been started so we have a unique Session ID
     * @return void
     */
    public static function session() {
        // Start Session
        session_start();

        // Identify User
        static::identify();
    }

    /**
     * Identify the current User or create a new ID
     *
     * @return void
     */
    private static function identify() {
        if ( array_key_exists('neopress', $_COOKIE) ) {
            static::$_user = $_COOKIE['neopress'];
        }
        else {
            static::$_user = uniqid();
        }

        $expires = time()+60*60*24*30;
        $path = '/';

        setcookie('neopress', static::$_user, $expires, $path);
    }

    /**
     * Return User ID
     *
     * @return string
     */
    public static function user() {
        return static::$_user;
    }

    /**
     * Singleton Class
     *
     * @return Neopress
     */
    public static function init() {
        if ( !static::$_instance ) {
            static::$_instance = new static;

            static::session();
        }

        return static::$_instance;
    }

    /**
     * Get Neo4j Client Instance
     */
    public static function client(): \Laudis\Neo4j\Basic\Session {
        if ( !static::$_client ) {
            // Create Neo Client
            $connection_string = sprintf('://%s:%s@%s:',
                get_option('neopress_username', 'neo4j'),
                get_option('neopress_password', 'neo'),
                get_option('neopress_host', 'localhost')
            );

            static::$_client = Driver::create('bolt'. $connection_string .get_option('neopress_bolt_port', 7687))
                ->createSession();
        }

        return static::$_client;
    }

    /**
     * Register Shutdown Hook
     *
     * @return void
     */
    public static function shutdown() {
        if (is_single()) {
            Session::log();
        }
    }

}

if ( is_admin() ) {
    add_action('admin_init', Neopress::class .'::init');


    add_action('admin_init', Admin::class    .'::init');
    add_action('admin_menu', Admin::class    .'::menu');

    add_action('save_post',  Post::class     .'::merge');
}
else {
    add_action('init',       Neopress::class .'::session');
    add_action('shutdown',   Neopress::class .'::shutdown');
}



