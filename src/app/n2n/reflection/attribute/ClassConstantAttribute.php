<?php

namespace n2n\reflection\attribute;

/**
 * Describer for { @link \Attribute Attribute } on method.
 */
class ClassConstantAttribute extends AttributeAdapter {
	private \ReflectionClassConstant $constant;

	private function __construct(\ReflectionAttribute $reflectionAttribute, \ReflectionClassConstant $constant, mixed $instance) {
		$this->attribute = $reflectionAttribute;
		$this->constant = $constant;
		$this->instance = $instance;
	}

	public static function fromAttribute(\ReflectionAttribute $attribute, \ReflectionClassConstant $constant) {
		return new ClassConstantAttribute($attribute, $constant, null);
	}

	public static function fromInstance(mixed $instance, \ReflectionClassConstant $constant) {
		return new ClassConstantAttribute(null, $constant, $instance);
	}

	public function getFile(): string {
		return $this->constant->getDeclaringClass()->getFileName();
	}

	public function getLine(): int {
		if ($this->attribute === null) return -1;
		return AttributeUtils::extractClassConstantAttributeLine($this->attribute, $this->constant);
	}
}