<?php

namespace n2n\reflection\attribute;

/**
 * Describer for { @link \Attribute Attribute } on property.
 */
class PropertyAttribute implements Attribute {
	/**
	 * @var \ReflectionAttribute|null
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

	public function __construct(\ReflectionAttribute|null $reflectionAttribute, \ReflectionProperty $property) {
		$this->attribute = $reflectionAttribute;
		$this->property = $property;
		if ($reflectionAttribute !== null) {
			$this->instance = $reflectionAttribute->newInstance();
		}
	}

	public function getFile(): string {
		return $this->property->getDeclaringClass()->getFileName();
	}

	public function getLine(): int {
		if ($this->attribute !== null) {
			return AttributeUtils::extractPropertyAttributeLine($this->attribute, $this->property);
		}
		return -1;
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