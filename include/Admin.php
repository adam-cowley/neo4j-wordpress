<?php

namespace Neopress;

use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Basic\Session;
use function add_settings_section;
use function register_setting;

class Admin {
	private Driver $driver;
	private Session $session;

	public function __construct( Driver $driver, Session $session ) {
		$this->driver  = $driver;
		$this->session = $session;
	}

	/**
	 * Display HTML for Password input
	 *
	 * TODO: Encrypt authentication details in database
	 */
	public static function option_neopress_password(): void {
		printf(
			'<input type="password" id="neopress_password" name="neopress_password" value="%s" />',
			get_option( 'neopress_password' )
		);
	}

	/**
	 * Register Admin Menus and Hooks
	 */
	public function init(): void {
		register_setting( 'neopress_connection', 'neopress_username' );
		register_setting( 'neopress_connection', 'neopress_password' );
		register_setting( 'neopress_connection', 'neopress_host' );
		register_setting( 'neopress_connection', 'neopress_port' );
		register_setting( 'neopress_connection', 'neopress_bolt_port' );


		add_settings_section(
			'neopress_connection',
			__( 'Connection Settings', 'neopress' ),
			[ Admin::class, 'checkConnectionStatus' ],
			'neopress'
		);
	}

	/**
	 * Check Connection and display statistics
	 */
	public function checkConnectionStatus(): void {

		if ( $this->driver->verifyConnectivity() ) {
			$class  = 'updated';
			$result = $this->session->run( 'MATCH (x) RETURN count(x) AS count' );

			$message = sprintf( '<p><strong>Connection Successful.</strong></p><p>There are <strong>%d</strong> nodes in your database', $result->getAsMap( 0 )->get( 'count' ) );
		} else {
			$class   = 'error';
			$message = '<p><strong>Could not connect to Neo4j. Please check your connection settings.</strong></p>';
		}

		printf( '<div id="neopress-response" class="%s">%s</strong></div>', $class, $message );
	}

	/**
	 * Register Configuration Menu
	 *
	 * @return void
	 */
	public function menu(): void {
		add_options_page(
			__( "Neo4j Connection Settings", 'neopress' ),
			__( "Neopress", 'neopress' ),
			'manage_options',
			'neopress',
			static::class . '::menuConnection'
		);


		$options = [
			'neopress_username'  => 'Username',
			'neopress_password'  => 'Password',
			'neopress_host'      => 'Host',
			'neopress_port'      => 'HTTP Port',
			'neopress_bolt_port' => 'Bolt Port',
		];

		foreach ( $options as $key => $label ) {
			add_settings_field(
				$key,
				__( $label, 'neopress' ),
				static::class . '::option_' . $key,
				'neopress',
				'neopress_connection'
			);
		}

	}

	/**
	 * Display HTML for Username input
	 */
	public function option_neopress_username(): void {
		printf(
			'<input type="text" id="neopress_username" name="neopress_username" value="%s" />',
			get_option( 'neopress_username' )
		);
	}

	/**
	 * Display HTML for Host input
	 */
	public function option_neopress_host(): void {
		printf(
			'<input type="text" id="neopress_host" name="neopress_host" value="%s" />',
			get_option( 'neopress_host' )
		);
	}

	/**
	 * Display HTML for Port Input
	 */
	public function option_neopress_port(): void {
		printf(
			'<input type="number" id="neopress_port" name="neopress_port" value="%s" />',
			get_option( 'neopress_port', 7474 )
		);
	}

	/**
	 * Display HTML for Bolt Port Input
	 */
	public function option_neopress_bolt_port(): void {
		printf(
			'<input type="number" id="neopress_bolt_port" name="neopress_bolt_port" value="%s" />',
			get_option( 'neopress_bolt_port', 7876 )
		);
	}

	/**
	 * Display HTML for Connection Options Page
	 */
	public function menuConnection(): void {
		?>
        <div class="wrap">
            <h1><?php echo __( "Neo4j Connection Settings", 'neopress' ); ?></h1>
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



