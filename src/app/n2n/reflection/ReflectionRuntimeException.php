<?php
namespace n2n\reflection;

class ReflectionRuntimeException extends \RuntimeException {
	static function try(\Closure $closure): mixed {
		try {
			return $closure();
		} catch (\Throwable $t) {
			throw new ReflectionRuntimeException($t->getMessage(), previous: $t);
		}
	}
}