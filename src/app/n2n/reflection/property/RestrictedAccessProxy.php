<?php

namespace n2n\reflection\property;

use n2n\util\type\TypeConstraint;
use n2n\util\ex\UnsupportedOperationException;
use n2n\util\type\ValueIncompatibleWithConstraintsException;

class RestrictedAccessProxy implements AccessProxy {

	function __construct(private PropertyAccessProxy $propertyAccessProxy,
			private ?TypeConstraint $getterConstraint = null, private ?TypeConstraint $setterConstraint = null) {
	}

	public function getPropertyName(): string {
		return $this->propertyAccessProxy->getPropertyName();
	}

	function getProperty() {
		return $this->propertyAccessProxy->getProperty();
	}

	public function getConstraint(): TypeConstraint {
		throw new UnsupportedOperationException();
	}

	public function setConstraint(TypeConstraint $constraint) {
		throw new UnsupportedOperationException();
	}

	function isReadable(): bool {
		return $this->propertyAccessProxy->isReadable();
	}

	function getGetterConstraint(): TypeConstraint {
		return $this->getterConstraint ?? $this->propertyAccessProxy->getGetterConstraint();
	}

	function isWritable(): bool {
		return $this->propertyAccessProxy->isWritable();
	}

	function getSetterConstraint(): TypeConstraint {
		return $this->setterConstraint ?? $this->propertyAccessProxy->getSetterConstraint();
	}

	public function setValue(object $object, mixed $value, bool $validate = true): void {
		if ($validate && $this->setterConstraint !== null) {
			try {
				$value = $this->setterConstraint->validate($value);
			} catch (ValueIncompatibleWithConstraintsException $e) {
				throw $this->propertyAccessProxy->createPassedValueException($e);
			}
		}

		$this->propertyAccessProxy->setValue($object, $value, $validate);
	}

	public function getValue(object $object): mixed {
		$value = $this->propertyAccessProxy->getValue($object);

		if ($this->getterConstraint === null) {
			return $value;
		}

		try {
			return $this->setterConstraint->validate($value);
		} catch (ValueIncompatibleWithConstraintsException $e) {
			throw $this->propertyAccessProxy->createRestricted($e);
		}
	}

	public function setNullReturnAllowed($nullReturnAllowed) {
		throw new UnsupportedOperationException();
	}

	public function isNullReturnAllowed() {
		throw new UnsupportedOperationException();
	}

	public function __toString(): string {
		return $this->propertyAccessProxy->__toString();
	}

	function createRestricted(TypeConstraint $getterConstraint = null, TypeConstraint $setterConstraint = null): AccessProxy {
		throw new UnsupportedOperationException();
	}
}