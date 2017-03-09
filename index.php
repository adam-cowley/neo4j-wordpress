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

use GraphAware\Neo4j\Client\ClientBuilder;

// No Hackers
defined( 'ABSPATH' ) or die( 'No dice.');

// Include Vendor Files
require_once 'vendor/autoload.php';

class Neopress {

    /** @var GraphAware\Neo4j\Client\Client */
    private static $_client;

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
     *
     * @return GraphAware\Neo4j\Client\Client
     */
    public static function client() {
        if ( !static::$_client ) {
            // Create Neo Client
            $connection_string = sprintf('://%s:%s@%s:',
                get_option('neopress_username', 'neo4j'),
                get_option('neopress_password', 'neo'),
                get_option('neopress_host', 'localhost')
            );

            static::$_client = ClientBuilder::create()
                // ->addConnection('default', 'http'. $connection_string .get_option('neopress_port', 7474))
                ->addConnection('bolt',    'bolt'. $connection_string .get_option('neopress_bolt_port', 7687))
                ->build();
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



