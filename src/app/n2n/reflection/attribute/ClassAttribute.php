<?php

namespace n2n\reflection\attribute;

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
		if ($this->attribute === null) return -1;
		return AttributeUtils::extractClassAttributeLine($this->attribute, $this->class);
	}
}