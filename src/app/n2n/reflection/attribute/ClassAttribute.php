<?php

namespace n2n\reflection\attribute;

/**
 * Describer for { @link \Attribute Attribute } on method.
 */
class ClassAttribute implements Attribute {
	/**
	 * @var \ReflectionAttribute
	 */
	private $attribute;

	/**
	 * @var \ReflectionClass
	 */
	private $class;

	/**
	 * @var mixed|null
	 */
	private $instance;

	public function __construct(\ReflectionAttribute $reflectionAttribute = null, \ReflectionClass $class) {
		$this->attribute = $reflectionAttribute;
		$this->class = $class;

		if ($reflectionAttribute !== null) {
			$this->instance = $reflectionAttribute->newInstance();
		}
	}

	public function getFile(): string {
		return $this->class->getFileName();
	}

	public function getLine(): int {
		return AttributeUtils::extractClassAttributeLine($this->attribute, $this->class);
	}

	public function getAttribute(): \ReflectionAttribute|null {
		return $this->attribute;
	}

	public function getInstance(): mixed {
		return $this->instance;
	}
}