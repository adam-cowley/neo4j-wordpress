<?php

namespace Neopress;

use Exception;

class Admin {

    /**
     * Register Admin Menus and Hooks
     *
     * @return void
     */
    public static function init() {
        static::registerSettings();
    }

    /**
     * Register Connection Settings
     *
     * @return void
     */
    private static function registerSettings() {
        register_setting('neopress_connection', 'neopress_username');
        register_setting('neopress_connection', 'neopress_password');
        register_setting('neopress_connection', 'neopress_host');
        register_setting('neopress_connection', 'neopress_port');
        register_setting('neopress_connection', 'neopress_bolt_port');


        add_settings_section(
            'neopress_connection',
            __('Connection Settings', 'neopress'),
            static::class .'::checkConnectionStatus',
            'neopress'
        );
    }

    /**
     * Check Connection and display statistics
     *
     * @return void
     */
    public static function checkConnectionStatus() {
        $class = 'error';
        $message = '<p><strong>Could not connect to Neo4j. Please check your connection settings.</strong></p>';

        try {
            $result = Neopress::client()->run('MATCH (n) RETURN count(n) as count');

            $class = 'updated';

            $message = sprintf('<p><strong>Connection Successful.</strong></p><p>There are <strong>%d</strong> nodes in your database', $result->getAsMap(0)->get('count'));
        }
        catch (Exception $e) {
            $message .= '<p>'. $e->getMessage() .'</p>';
        }

        printf('<div id="neopress-response" class="%s">%s</strong></div>', $class, $message);
    }



    /**
     * Register Configuration Menu
     *
     * @return void
     */
    public static function menu() {
        add_options_page(
            __("Neo4j Connection Settings", 'neopress'),
            __("Neopress", 'neopress'),
            'manage_options',
            'neopress',
            static::class .'::menuConnection'
        );



        $options = [
            'neopress_username' => 'Username',
            'neopress_password' => 'Password',
            'neopress_host' => 'Host',
            'neopress_port' => 'HTTP Port',
            'neopress_bolt_port' => 'Bolt Port',
        ];

        foreach ($options as $key => $label) {
            add_settings_field(
                $key,
                __($label, 'neopress'),
                static::class .'::option_'. $key,
                'neopress',
                'neopress_connection'
            );
        }

    }

    /**
     * Display HTML for Username input
     *
     * @return void
     */
    public static function option_neopress_username() {
         printf(
            '<input type="text" id="neopress_username" name="neopress_username" value="%s" />',
           get_option('neopress_username')
        );
    }

    /**
     * Display HTML for Password input
     *
     * TODO: Encrypt authentication details in database
     *
     * @return void
     */
    public static function option_neopress_password() {
        printf(
            '<input type="password" id="neopress_password" name="neopress_password" value="%s" />',
           get_option('neopress_password')
        );
    }

    /**
     * Display HTML for Host input
     *
     * @return void
     */
    public static function option_neopress_host() {
        printf(
            '<input type="text" id="neopress_host" name="neopress_host" value="%s" />',
           get_option('neopress_host')
        );
    }

    /**
     * Display HTML for Port Input
     *
     * @return void
     */
    public static function option_neopress_port() {
        printf(
            '<input type="number" id="neopress_port" name="neopress_port" value="%s" />',
           get_option('neopress_port', 7474)
        );
    }

    /**
     * Display HTML for Bolt Port Input
     *
     * @return void
     */
    public static function option_neopress_bolt_port() {
        printf(
            '<input type="number" id="neopress_bolt_port" name="neopress_bolt_port" value="%s" />',
           get_option('neopress_bolt_port', 7876)
        );
    }

    /**
     * Display HTML for Connection Options Page
     *
     * @return void
     */
    public static function menuConnection() {
         ?>
        <div class="wrap">
            <h1><?php echo __("Neo4j Connection Settings", 'neopress'); ?></h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'neopress_connection' );
                do_settings_sections( 'neopress' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }


}



