<?php

namespace n2n\reflection\attribute;

/**
 * Describer for { @link \Attribute Attribute } on property.
 */
class PropertyAttribute extends AttributeAdapter {
	private \ReflectionProperty $property;

	public function __construct(\ReflectionAttribute|null $reflectionAttribute, \ReflectionProperty $property, mixed $instance) {
		$this->attribute = $reflectionAttribute;
		$this->property = $property;
		$this->instance = $instance;
	}

	public static function fromAttribute(\ReflectionAttribute $attribute, \ReflectionProperty $property) {
		return new PropertyAttribute($attribute, $property, null);
	}

	public static function fromInstance(mixed $instance, \ReflectionProperty $property) {
		return new PropertyAttribute(null, $property, $instance);
	}

	public function getFile(): string {
		return $this->property->getDeclaringClass()->getFileName();
	}

	public function getLine(): int {
		$attrName = null;
		if ($this->attribute !== null) {
			$attrName = $this->attribute->getName();
		} else {
			$attrName = get_class($this->instance);
		}

		return AttributeUtils::extractPropertyAttributeLine($attrName, $this->property);
	}

	public function getProperty(): \ReflectionProperty {
		return $this->property;
	}
}