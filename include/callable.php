<?php

namespace Neopress;

use Closure;
use DI\ContainerBuilder;

function make_callable(array $pseudoCallable): Closure
{
	static $container = null;
	if ($container ===  null) {
		$container =  (new ContainerBuilder())
			->addDefinitions(__DIR__ . '/../register.php')
			->useAutowiring(true)
			->build();
	}

	return Closure::fromCallable(static function () use ($container, $pseudoCallable) {
		[$class, $method] = $pseudoCallable;

		return $container->get($class)->$method();
	});
}