<?php

namespace Neopress;

use Laudis\Neo4j\Basic\Driver;
use function array_key_exists;
use function is_single;
use function session_id;
use function session_start;
use function sprintf;
use function time;
use function uniqid;

class Neopress {
	private static \Laudis\Neo4j\Basic\Session $_session;
	private static Driver $_driver;

	/** @var Neopress Singleton instance */
	private static self $_instance;

	/** @var string User ID */
	private static string $_user;

	/**
	 * Return User ID
	 */
	public static function user(): string {
		return static::$_user;
	}

	/**
	 * Singleton Class
	 */
	public static function init(): self {
		if ( ! isset( static::$_instance ) ) {
			static::$_instance = new static;

			static::session();
		}

		return static::$_instance;
	}

	/**
	 * Make sure a session has been started, so we have a unique Session ID
	 */
	public static function session(): void {
		// Start Session
		if (session_id() === '') {
			session_start();
		}

		// Identify User
		static::identify();
	}

	/**
	 * Identify the current User or create a new ID
	 */
	private static function identify(): void {
		if ( array_key_exists( 'neopress', $_COOKIE ) ) {
			static::$_user = $_COOKIE['neopress'];
		} else {
			static::$_user = uniqid();
		}

		$expires = time() + 60 * 60 * 24 * 30;
		$path    = '/';

		setcookie( 'neopress', static::$_user, $expires, $path );
	}

	/**
	 * Get Neo4j Client Instance
	 */
	public static function client(): \Laudis\Neo4j\Basic\Session {
		if ( ! isset( static::$_session ) ) {
			static::$_session = self::driver()->createSession();
		}

		return static::$_session;
	}

	/**
	 * Get Neo4j Client Instance
	 */
	public static function driver(): Driver {
		if ( ! isset( static::$_driver ) ) {
			// Create Neo Client
			$connection_string = sprintf( '://%s:%s@%s:',
				get_option( 'neopress_username', 'neo4j' ),
				get_option( 'neopress_password', 'neo' ),
				get_option( 'neopress_host', 'localhost' )
			);

			static::$_driver = Driver::create( 'bolt' . $connection_string . get_option( 'neopress_bolt_port', 7687 ) );
		}

		return static::$_driver;
	}

	/**
	 * Register Shutdown Hook
	 *
	 * @return void
	 */
	public static function shutdown(): void {
		if ( is_single() ) {
			Session::log();
		}
	}

}