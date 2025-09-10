<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\reflection\property;

use n2n\reflection\ReflectionUtils;
use n2n\util\type\TypeUtils;
use n2n\util\type\TypeConstraint;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\util\type\TypeConstraints;
use n2n\util\ex\IllegalStateException;
use n2n\util\type\TypeName;
use n2n\util\type\custom\Undefined;

class ReflectionAccessProxy implements PropertyAccessProxy {
	private $propertyName;
	private $property;
	private $setterMethod;
	private $getterMethod;
	private $forcePropertyAccess;
	private ?TypeConstraint $constraint = null;
	private bool $nullReturnAllowed = false;
	private TypeConstraint $getterConstraint;
	private TypeConstraint $setterConstraint;
	private ?UninitializedBehaviour $uninitializedBehaviour;

	public function __construct($propertyName, ?\ReflectionProperty $property = null,
			?\ReflectionMethod $getterMethod = null, ?\ReflectionMethod $setterMethod = null,
			?UninitializedBehaviour $uninitializedBehaviour = null) {
		$this->propertyName = $propertyName;
		$this->property = $property;
		$this->getterMethod = $getterMethod;
		$this->setterMethod = $setterMethod;
		$this->uninitializedBehaviour = $uninitializedBehaviour;
	}

	public function getBaseConstraint(): ?TypeConstraint {
		return $this->isWritable() ? $this->getSetterConstraint() : $this->getGetterConstraint();
	}

	public function isNullPossible(): bool {
		return $this->getBaseConstraint()->allowsNull();
	}

	public function getPropertyName(): string {
		return $this->propertyName;
	}

	public function getProperty(): ?\ReflectionProperty {
		return $this->property;
	}

	public function isReadable(): bool {
		return (isset($this->property) && ($this->forcePropertyAccess || $this->property->isPublic()))
				|| isset($this->getterMethod);
	}

	public function isWritable(): bool {
		return (isset($this->property) && ($this->forcePropertyAccess || $this->property->isPublic()))
				|| isset($this->setterMethod);
	}

	public function isConstant(): bool {
		return isset($this->property) && $this->property->isReadOnly();
	}

	public function isNullReturnAllowed(): bool {
		return $this->nullReturnAllowed;
	}

	public function setNullReturnAllowed(bool $nullReturnAllowed): void {
		$this->nullReturnAllowed = $nullReturnAllowed;
	}

	public function getSetterMethod(): ?\ReflectionMethod {
		return $this->setterMethod;
	}

	public function getGetterMethod(): ?\ReflectionMethod {
		return $this->getterMethod;
	}
	/**
	 *
	 * @return TypeConstraint
	 */
	public function getConstraint(): TypeConstraint {
		return $this->constraint ?? $this->getBaseConstraint();
	}

	public function setConstraint(TypeConstraint $constraint): void {
		if ($constraint->isPassableTo($this->getBaseConstraint())) {
			$this->constraint = $constraint;
			return;
		}

		if (null === $this->setterMethod) {
			throw new ConstraintsConflictException('Constraints conflict for property '
					. $this->property->getDeclaringClass()->getName() . '::$'
					. $this->property->getName() . '. Constraints ' . $constraint->__toString()
					. ' are not compatible with ' . $this->getBaseConstraint()->__toString());
		} else {
			throw new ConstraintsConflictException('Constraints conflict for setter-method '
					. $this->setterMethod->getDeclaringClass()->getName() . '::'
					. $this->setterMethod->getName() . '(). Constraints ' . $constraint->__toString()
					. ' are not compatible with ' . $this->getBaseConstraint()->__toString(),
					0, null, $this->setterMethod);
		}
	}

	function getSetterConstraint(): ?TypeConstraint {
		if (isset($this->setterConstraint)) {
			return $this->setterConstraint;
		}

		if ($this->setterMethod !== null) {
			$parameter = current($this->setterMethod->getParameters());
			return $this->setterConstraint = TypeConstraints::type($parameter);
		}

		if (!$this->isWritable()) {
			return null;
		}

		return $this->setterConstraint = TypeConstraints::type($this->property->getType());
	}

	function getGetterConstraint(): TypeConstraint {
		if (isset($this->getterConstraint)) {
			return $this->getterConstraint;
		}

		if ($this->getterMethod !== null) {
			return $this->getterConstraint = TypeConstraints::type(
					$this->getterMethod->getReturnType());
		}

		if (!$this->isReadable()) {
			throw new IllegalStateException($this . ' not readable.');
		}


		$type = $this->property->getType();

		if ($type === null || $type->allowsNull()) {
			return $this->getterConstraint = TypeConstraints::type($type);
		}

		if (TypeName::isNamedType($type)) {
			return $this->getterConstraint = TypeConstraints::namedType($type, true);
		}

		$typeNames = TypeName::extractUnionTypeNames($type);
		$typeNames[] = 'null';
		return $this->getterConstraint = TypeConstraints::type($typeNames);
	}

	public function setForcePropertyAccess(bool $forcePropertyAccess): void {
		$this->property->setAccessible((boolean) $forcePropertyAccess);
		$this->forcePropertyAccess = (boolean) $forcePropertyAccess;
	}

	public function isPropertyAccessSetterMode(): bool {
		return $this->forcePropertyAccess || null === $this->setterMethod;
	}

	public function isPropertyAccessGetterMode(): bool {
		return $this->forcePropertyAccess || null === $this->getterMethod;
	}

	/**
	 * @param ValueIncompatibleWithConstraintsException $previous
	 * @return PropertyValueTypeMismatchException
	 */
	public function createPassedValueException(\Throwable $previous): PropertyValueTypeMismatchException {
		if ($this->isPropertyAccessSetterMode()) {
			return new PropertyValueTypeMismatchException('Passed value for '
					. $this->property->getDeclaringClass()->getName() . '::$' . $this->property->getName()
					. ' is incompatible with constraints.', 0, $previous);
		} else {
			return new PropertyValueTypeMismatchException('Passed value for '
					. $this->setterMethod->getDeclaringClass()->getName() . '::' . $this->setterMethod->getName()
					. '() is disallowed for property setter method.', 0, $previous);
		}
	}


	public function createReturnedValueException(\Throwable $previous): PropertyValueTypeMismatchException {
		if ($this->isPropertyAccessGetterMode()) {
			return new PropertyValueTypeMismatchException('Property '
					. $this->property->getDeclaringClass()->getName() . '::$'
					. $this->property->getName() . ' contains unexpected type.', 0, $previous);
		} else {
			return new PropertyValueTypeMismatchException('Getter method '
					. $this->getterMethod->getDeclaringClass()->getName() . '::'
					. $this->getterMethod->getName() . '()  returns unexpected type', 0, $previous);
		}
	}

	/**
	 * @throws PropertyValueTypeMismatchException
	 * @throws PropertyAccessException
	 */
	public function setValue(object $object, mixed $value, bool $validate = true): void {
		if ($validate) {
			try {
				$value = ($this->constraint ?? $this->getSetterConstraint())->validate($value);
			} catch (ValueIncompatibleWithConstraintsException $e) {
				throw $this->createPassedValueException($e);
			}
		}

		if ($this->isPropertyAccessSetterMode()) {
			try {
				$this->property->setValue($object, $value);
			} catch (\ReflectionException|\TypeError $e) {
				throw new PropertyAccessException('Could not set value for property. Reason: ' . $e->getMessage()
						. $this->property->getDeclaringClass()->getName() . '::$'
						. $this->property->getName(), 0, $e);
			}

			return;
		}

		try {
			$setterMethod = $this->findMethod($object, $this->setterMethod);
		} catch (\ReflectionException|\TypeError $e) {
			throw $this->createPropertyAccessWriteException($object, $e);
		}
		try {
			$setterMethod->invoke($object, $value);
		} catch (\ReflectionException $e) {
			throw $this->createMethodInvokeException($setterMethod, $e);
		}
	}

	/**
	 * @throws PropertyValueTypeMismatchException
	 * @throws PropertyAccessException
	 */
	public function getValue(object $object): mixed {
		$value = $this->isPropertyAccessGetterMode()
				? $this->propertyValue($object)
				: $this->getterValue($object);

		if ($this->constraint === null || ($value === null && $this->nullReturnAllowed)) {
			return $value;
		}

		return $this->validateValue($value);
	}

	/**
	 * @throws PropertyAccessException
	 */
	private function propertyValue(object $object): mixed {
		if ($this->property->isInitialized($object)) {
			return $this->property->getValue($object);
		}
		return $this->handleUninitializedProperty($object);
	}

	/**
	 * @throws PropertyAccessException
	 */
	private function handleUninitializedProperty(object $object): ?Undefined {
		switch ($this->uninitializedBehaviour) {
			case UninitializedBehaviour::RETURN_UNDEFINED:
				return Undefined::i();
			case UninitializedBehaviour::RETURN_NULL:
				return null;
			case UninitializedBehaviour::THROW_EXCEPTION:
				throw $this->createPropertyAccessException($object);
			case UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE:
				if (TypeConstraints::type($this->property->getType())
						->isPassableBy(TypeConstraints::type(Undefined::class))) {
				return Undefined::i();
			}
		}

		return null;
	}

	/**
	 * @throws PropertyAccessException
	 */
	private function getterValue(object $object): mixed {
		try {
			$getterMethod = $this->findMethod($object, $this->getterMethod);
		} catch (\ReflectionException $e) {
			throw $this->createPropertyAccessException($object, $e);
		}

		try {
			return $getterMethod->invoke($object);
		} catch (\ReflectionException $e) {
			throw $this->createMethodInvokeException($getterMethod, $e, $object);
		}
	}

	private function createPropertyAccessException(object $object, ?\Throwable $e = null): PropertyAccessException {
		return new PropertyAccessException(
				sprintf(
						'Could not get value of property %s::$%s (Read from object type %s)',
						$this->property->getDeclaringClass()->getName(),
						$this->property->getName(),
						get_class($object)
				), 0, $e);
	}

	private function createPropertyAccessWriteException($object, ?\Throwable $e = null): PropertyAccessException {
		return new PropertyAccessException(
				sprintf(
						'Could not write value of property %s::$%s (Read from object type %s)',
						$this->property->getDeclaringClass()->getName(),
						$this->property->getName(),
						get_class($object)
				), 0, $e);
	}

	private function validateValue(mixed $value): mixed {
		try {
			return $this->constraint->validate($value);
		} catch (ValueIncompatibleWithConstraintsException $e) {
			throw $this->createReturnedValueException($e);
		}
	}

	/**
	 * @throws \ReflectionException
	 */
	private function findMethod($object, \ReflectionMethod $method): \ReflectionMethod {
		$declaringClass = $method->getDeclaringClass();
		if (get_class($object) == $declaringClass->getName()) {
			return $method;
		}

		$objectClass = new \ReflectionClass($object);
		if (!ReflectionUtils::isClassA($objectClass, $declaringClass)) {
			return $method;
		}

		return $objectClass->getMethod($method->getName());
	}

	/**
	 * @throws PropertyAccessException
	 */
	public function createMethodInvokeException(\ReflectionMethod $method, \Exception $previous, $object = null) {
		$message = 'Reflection execution of ' . TypeUtils::prettyReflMethName($method). ' failed.';

		if ($object !== null && !ReflectionUtils::isObjectA($object, $method->getDeclaringClass())) {
			$message .= ' Reason: Type of ' . get_class($object) . ' passed as object, type of '
					. $method->getDeclaringClass()->getName() . ' expected.';
		}

		throw new PropertyAccessException($message, 0, $previous);
	}

	public function __toString(): string {
		if ($this->isPropertyAccessGetterMode() && $this->isPropertyAccessSetterMode()) {
			return 'AccessProxy [' . ($this->property !== null ? TypeUtils::prettyReflPropName($this->property)
							: TypeUtils::prettyPropName('<unknown class>', $this->propertyName)) . ']';
		}

		$strs = array();
		if ($this->getterMethod !== null) {
			$strs[] = TypeUtils::prettyReflMethName($this->getterMethod);
		}
		if ($this->setterMethod !== null) {
			$strs[] = TypeUtils::prettyReflMethName($this->setterMethod);
		}

		return 'AccessProxy [' . implode(', ', $strs) . ']';
	}

	function createRestricted(?TypeConstraint $getterConstraint = null, ?TypeConstraint $setterConstraint = null): PropertyAccessProxy {
		return new RestrictedAccessProxy($this, $getterConstraint, $setterConstraint);
	}
}
