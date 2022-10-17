<?php

namespace n2n\reflection\attribute;

/**
 * Describer for { @link \Attribute Attribute } on method.
 */
class MethodAttribute extends AttributeAdapter {
	private \ReflectionMethod $method;

	private function __construct(\ReflectionAttribute|null $reflectionAttribute, \ReflectionMethod $method, mixed $instance) {
		$this->attribute = $reflectionAttribute;
		$this->method = $method;
		$this->instance = $instance;
	}

	public static function fromAttribute(\ReflectionAttribute $attribute, \ReflectionMethod $method) {
		return new MethodAttribute($attribute, $method, null);
	}

	public static function fromInstance(mixed $instance, \ReflectionMethod $method) {
		return new MethodAttribute(null, $method, $instance);
	}

	public function getMethod(): \ReflectionMethod {
		return $this->method;
	}

	public function getFile(): string {
		return $this->method->getDeclaringClass()->getFileName();
	}

	public function getLine(): int {
		$attrName = null;
		if ($this->attribute !== null) {
			$attrName = $this->attribute->getName();
		} else {
			$attrName = get_class($this->instance);
		}

		return AttributeUtils::extractMethodAttributeLine($attrName, $this->method);
	}
}