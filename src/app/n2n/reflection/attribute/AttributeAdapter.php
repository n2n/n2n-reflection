<?php

namespace n2n\reflection\attribute;

/**
 * Adapter for attribute classes
 */
abstract class AttributeAdapter implements Attribute {

	protected \ReflectionAttribute|null $attribute;
	protected mixed $instance;

	public function getAttribute(): \ReflectionAttribute|null {
		return $this->attribute;
	}

	/**
	 * instance or attribute must be set
	 * @return mixed
	 */
	public function getInstance(): mixed {
		if ($this->instance !== null) {
			return $this->instance;
		}

		return $this->instance = $this->attribute->newInstance();
	}
}