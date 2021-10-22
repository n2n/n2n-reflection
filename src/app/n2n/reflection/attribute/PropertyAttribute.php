<?php

namespace n2n\reflection\attribute;

/**
 * Describer for { @link \Attribute Attribute } on property.
 */
class PropertyAttribute implements Attribute {
	/**
	 * @var \ReflectionAttribute
	 */
	private $attribute;

	/**
	 * @var \ReflectionProperty
	 */
	private $property;

	/**
	 * @var mixed|null
	 */
	private $instance;

	public function __construct(\ReflectionAttribute $reflectionAttribute, \ReflectionProperty $property) {
		$this->attribute = $reflectionAttribute;
		$this->property = $property;
		$this->instance = $reflectionAttribute->newInstance();
	}

	public function getFile(): string {
		return $this->property->getDeclaringClass()->getFileName();
	}

	public function getLine(): int {
		return AttributeUtils::extractAttributeLine($this->attribute, $this->property);
	}

	public function getAttribute(): \ReflectionAttribute|null {
		return $this->attribute;
	}

	public function getInstance(): mixed {
		return $this->instance;
	}

	public function getProperty(): \ReflectionProperty {
		return $this->property;
	}
}