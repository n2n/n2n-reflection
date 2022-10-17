<?php

namespace n2n\reflection\attribute;

use n2n\util\type\ArgUtils;

/**
 * Describer for { @link \Attribute Attribute } on method.
 */
class ClassConstantAttribute extends AttributeAdapter {
	private \ReflectionClassConstant $constant;
	private int $line;

	private function __construct(?\ReflectionAttribute $reflectionAttribute, \ReflectionClassConstant $constant,
			mixed $instance) {
		ArgUtils::assertTrue($reflectionAttribute !== null || $instance !== null);

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
		$attrName = null;
		if ($this->attribute !== null) {
			$attrName = $this->attribute->getName();
		} else {
			$attrName = get_class($this->instance);
		}

		return $this->line
				?? $this->line = AttributeUtils::extractClassConstantAttributeLine($attrName, $this->constant);
	}
}