<?php

namespace n2n\reflection\attribute;

/**
 * Describer for { @link \Attribute Attribute } on method.
 */
class MethodAttribute implements Attribute {
	/**
	 * @var \ReflectionAttribute
	 */
	private $attribute;

	/**
	 * @var \ReflectionMethod
	 */
	private $method;

	/**
	 * @var mixed|null
	 */
	private $instance;

	public function __construct(\ReflectionAttribute|null $reflectionAttribute, \ReflectionMethod $method) {
		$this->attribute = $reflectionAttribute;
		$this->method = $method;
		$this->instance = $reflectionAttribute->newInstance();
	}

	public function getFile(): string {
		return $this->method->getDeclaringClass()->getFileName();
	}

	public function getLine(): int {
		return AttributeUtils::extractMethodAttributeLine($this->attribute, $this->method);
	}

	public function getAttribute(): \ReflectionAttribute|null {
		return $this->attribute;
	}

	public function getInstance(): mixed {
		return $this->instance;
	}
}