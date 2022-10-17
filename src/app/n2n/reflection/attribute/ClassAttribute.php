<?php

namespace n2n\reflection\attribute;

use n2n\util\ex\IllegalStateException;

/**
 * Describer for { @link \Attribute Attribute } on method.
 */
class ClassAttribute extends AttributeAdapter {
	private \ReflectionClass $class;

	private function __construct(\ReflectionAttribute|null $reflectionAttribute, \ReflectionClass $class, $instance) {
		$this->attribute = $reflectionAttribute;
		$this->class = $class;
		$this->instance = $instance;
	}

	public static function fromAttribute(\ReflectionAttribute $attribute, \ReflectionClass $class) {
		return new ClassAttribute($attribute, $class, null);
	}

	public static function fromInstance(mixed $instance, \ReflectionClass $class) {
		return new ClassAttribute(null, $class, $instance);
	}

	public function getFile(): string {
		return $this->class->getFileName();
	}

	public function getLine(): int {
		$attrName = null;
		if ($this->attribute !== null) {
			$attrName = $this->attribute->getName();
		} else {
			$attrName = get_class($this->instance);
		}

		return AttributeUtils::extractClassAttributeLine($attrName, $this->class);
	}
}