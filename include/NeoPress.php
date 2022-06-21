<?php

namespace Neopress;

use Laudis\Neo4j\Basic\Driver;
use function array_key_exists;
use function get_option;
use function is_single;
use function session_id;
use function session_start;
use function time;
use function uniqid;

class NeoPress {
	/** @var NeoPress Singleton instance */
	private static self $_instance;

	private Driver $driver;
	private string $user;
	private ?Session $session = null;

	private function __construct( Driver $driver, string $user ) {
		$this->driver = $driver;
		$this->user   = $user;
	}

	private static function initializeDriver(): Driver {
		$connectionString = 'neo4j://';
		if ( get_option( 'neopress_username' ) && get_option( 'neopress_password' ) ) {
			$connectionString .= get_option( 'neopress_username' ) . ':' . get_option( 'neopress_password' ) . '@';
		}
		
		$connectionString .= get_option( 'neopress_host', 'localhost' );

		if ( get_option( 'neopress_bolt_port' ) ) {
			$connectionString .= get_option( 'neopress_bolt_port' );
		}

		return Driver::create( $connectionString );
	}

	/**
	 * Return User ID
	 */
	public function getUser(): string {
		return $this->user;
	}

	/**
	 * Singleton Class
	 */
	public static function get(): self {
		return static::$_instance ??= new self( NeoPress::initializeDriver(), NeoPress::identifyUser() );
	}

	/**
	 * Make sure a session has been started, so we have a unique Session ID
	 */
	public static function identifyUser(): string {
		if ( session_id() === '' ) {
			session_start();
		}

		return static::identify();
	}

	/**
	 * Identify the current User or create a new ID
	 */
	private static function identify(): string {
		if ( array_key_exists( 'neopress', $_COOKIE ) ) {
			$tbr = $_COOKIE['neopress'];
		} else {
			$tbr = uniqid();
		}

		$expires = time() + 60 * 60 * 24 * 30;
		$path    = '/';

		setcookie( 'neopress', static::$_user, $expires, $path );

		return $tbr;
	}

	/**
	 * Get Neo4j Client Instance
	 */
	public function getSession(): \Laudis\Neo4j\Basic\Session {
		return $this->session ??= $this->driver->createSession();
	}

	/**
	 * Get Neo4j Client Instance
	 */
	public function getDriver(): Driver {
		return $this->driver;
	}

	/**
	 * Register Shutdown Hook
	 */
	public static function shutdown(): void {
		if ( is_single() ) {
			Session::log();
		}
	}

}