<?php

namespace Neopress;

use DI\ContainerBuilder;

/**
 * @return callable
 */
function make_callable( array $pseudoCallable ) {
	static $container = null;
	if ( $container === null ) {
		$container = ( new ContainerBuilder() )
			->addDefinitions( __DIR__ . '/../register.php' )
			->useAutowiring( true )
			->build();
	}

	return static function () use ( &$container, $pseudoCallable ) {
		[ $class, $method ] = $pseudoCallable;

		return $container->get( $class )->$method();
	};
}