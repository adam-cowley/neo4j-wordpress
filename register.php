<?php

use Laudis\Neo4j\Basic\Driver;
use Laudis\Neo4j\Basic\Session;
use Laudis\Neo4j\Contracts\DriverInterface;
use Laudis\Neo4j\Contracts\SessionInterface;
use Neopress\NeoPress;
use Psr\Container\ContainerInterface;
use function DI\create;

return [
	Session::class => static function ( ContainerInterface $c ) {
		return $c->get( Driver::class )->createSession();
	},

	Driver::class => static function () {
		$connectionString = 'neo4j://';
		if ( get_option( 'neopress_username' ) && get_option( 'neopress_password' ) ) {
			$connectionString .= get_option( 'neopress_username' ) . ':' . get_option( 'neopress_password' ) . '@';
		}

		$connectionString .= get_option( 'neopress_host', 'localhost' );

		if ( get_option( 'neopress_port' ) ) {
			$connectionString .= get_option( 'neopress_port' );
		}

		return Driver::create( $connectionString );
	},

	'userId' => static function () {
		if ( session_id() === '' ) {
			session_start();
		}

		if ( array_key_exists( 'neopress', $_COOKIE ) ) {
			$tbr = $_COOKIE['neopress'];
		} else {
			$tbr = uniqid();
		}

		$expires = time() + 60 * 60 * 24 * 30;
		$path    = '/';

		setcookie( 'neopress', $tbr, $expires, $path );

		return $tbr;
	},

	SessionInterface::class => create( Session::class ),
	DriverInterface::class  => create( Driver::class ),

	NeoPress::class => static function ( ContainerInterface $c ) {
		return new Neopress( $c->get( Session::class ), $c->get( 'userId' ) );
	}
];