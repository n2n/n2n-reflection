<?php

namespace n2n\reflection\attribute;

/**
 * Describer for { @link \Attribute Attribute } on method.
 */
class ClassConstantAttribute implements Attribute {
	/**
	 * @var \ReflectionAttribute
	 */
	private $attribute;

	/**
	 * @var \ReflectionClassConstant
	 */
	private $constant;

	/**
	 * @var mixed|null
	 */
	private $instance;

	public function __construct(\ReflectionAttribute $reflectionAttribute, \ReflectionClassConstant $constant) {
		$this->attribute = $reflectionAttribute;
		$this->constant = $constant;
		$this->instance = $reflectionAttribute->newInstance();
	}

	public function getFile(): string {
		return $this->constant->getDeclaringClass()->getFileName();
	}

	public function getLine(): int {
		// @todo
		return -1;
	}

	public function getAttribute(): \ReflectionAttribute|null {
		return $this->attribute;
	}

	public function getInstance(): mixed {
		return $this->instance;
	}
}