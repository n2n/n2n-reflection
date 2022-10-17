<?php

namespace n2n\reflection\property;

use n2n\util\type\TypeConstraint;
use n2n\util\ex\UnsupportedOperationException;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use ReflectionMethod;
use Throwable;

class RestrictedAccessProxy implements PropertyAccessProxy {

	function __construct(private PropertyAccessProxy $propertyAccessProxy,
			private ?TypeConstraint $getterConstraint = null, private ?TypeConstraint $setterConstraint = null) {
	}

	public function getPropertyName(): string {
		return $this->propertyAccessProxy->getPropertyName();
	}

	function getProperty(): ?\ReflectionProperty {
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
			return $this->getterConstraint->validate($value);
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

	function createRestricted(TypeConstraint $getterConstraint = null, TypeConstraint $setterConstraint = null): PropertyAccessProxy {
		throw new UnsupportedOperationException();
	}

	function getSetterMethod(): ?ReflectionMethod {
		return $this->propertyAccessProxy->getSetterMethod();
	}

	function getGetterMethod(): ?ReflectionMethod {
		return $this->propertyAccessProxy->getGetterMethod();
	}

	function createPassedValueException(Throwable $previous): PropertyValueTypeMissmatchException {
		return $this->propertyAccessProxy->createPassedValueException($previous);
	}

	public function createReturnedValueException(Throwable $previous): PropertyValueTypeMissmatchException {
		return $this->propertyAccessProxy->createReturnedValueException($previous);
	}
}