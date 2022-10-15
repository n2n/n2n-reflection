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
use n2n\util\type\ArgUtils;
use n2n\util\type\TypeUtils;
use n2n\util\type\TypeConstraint;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\util\type\TypeConstraints;
use n2n\util\ex\UnsupportedOperationException;
use n2n\util\ex\IllegalStateException;

class ReflectionAccessProxy implements PropertyAccessProxy {
	private $propertyName;
	private $property;
	private $setterMethod;
	private $getterMethod;
	private $forcePropertyAccess;
	private ?TypeConstraint $constraint = null;
	private $nullReturnAllowed = false;
	private TypeConstraint $getterConstraint;
	private TypeConstraint $setterConstraint;

	public function __construct($propertyName, \ReflectionProperty $property = null, 
			\ReflectionMethod $getterMethod = null, \ReflectionMethod $setterMethod = null) {
		$this->propertyName = $propertyName;
		$this->property = $property;
		$this->getterMethod = $getterMethod;
		$this->setterMethod = $setterMethod;
	}
	
	public function getBaseConstraint() {
		return $this->isWritable() ? $this->getSetterConstraint() : $this->getGetterConstraint();
	}
	
	public function isNullPossible() {
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
	
	public function isNullReturnAllowed() {
		return $this->nullReturnAllowed;
	}
	
	public function setNullReturnAllowed($nullReturnAllowed) {
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
	 * @return \n2n\util\type\TypeConstraint
	 */
	public function getConstraint(): TypeConstraint {
		return $this->constraint ?? $this->getSetterConstraint();
	}

	public function setConstraint(TypeConstraint $constraint) {
		if ($constraint->isPassableTo($this->getBaseConstraint())) {
			$this->constraint = $constraint;
			return;
		}

		if (null === $this->setterMethod) {
			throw new ConstraintsConflictException('Constraints conflict for property ' 
					. $this->property->getDeclaringClass()->getName() . '::$' 
					. $this->property->getName() . '. Constraints ' . $constraint->__toString()
					. ' are not compatible with ' . $this->baseConstraint->__toString());
		} else {
			throw new ConstraintsConflictException('Constraints conflict for setter-method ' 
							. $this->setterMethod->getDeclaringClass()->getName() . '::' 
							. $this->setterMethod->getName() . '(). Constraints ' . $constraint->__toString()
							. ' are not compatible with ' . $this->baseConstraint->__toString(),
					0, null, $this->setterMethod);
		}
	}

	function getSetterConstraint(): TypeConstraint {
		if (isset($this->setterConstraint)) {
			return $this->setterConstraint;
		}

		if ($this->setterMethod !== null) {
			$parameter = current($this->setterMethod->getParameters());
			return $this->setterConstraint = TypeConstraints::type($parameter);
		}

		if (!$this->isWritable()) {
			throw new IllegalStateException($this . ' not writable.');
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

		return $this->getterConstraint = TypeConstraints::type($this->property?->getType());
	}

	public function setForcePropertyAccess($forcePropertyAccess) {
		$this->property->setAccessible((boolean) $forcePropertyAccess);
		$this->forcePropertyAccess = (boolean) $forcePropertyAccess;
	}

	public function isPropertyAccessSetterMode() {
		return $this->forcePropertyAccess || null === $this->setterMethod;
	}
	
	public function isPropertyAccessGetterMode() {
		return $this->forcePropertyAccess || null === $this->getterMethod;
	}

	/**
	 * @param ValueIncompatibleWithConstraintsException $e
	 * @return PropertyValueTypeMissmatchException
	 */
	public function createPassedValueException(\Throwable $e): PropertyValueTypeMissmatchException {
		if ($this->isPropertyAccessSetterMode()) {
			return new PropertyValueTypeMissmatchException('Passed value for ' 
					. $this->property->getDeclaringClass()->getName() . '::$' . $this->property->getName() 
					. ' is incompatible with constraints.', 0, $e);
		} else {
			return new PropertyValueTypeMissmatchException('Passed value for ' 
					. $this->setterMethod->getDeclaringClass()->getName() . '::' . $this->setterMethod->getName() 
					. '() is disallowed for property setter method.', 0, $e);
		}
	}


	public function createReturnedValueException(\Throwable $previous): PropertyValueTypeMissmatchException {
		if ($this->isPropertyAccessGetterMode()) {
			return new PropertyValueTypeMissmatchException('Property ' 
					. $this->property->getDeclaringClass()->getName() . '::$' 
					. $this->property->getName() . ' contains unexpected type.', 0, $previous);
		} else {
			return new PropertyValueTypeMissmatchException('Getter method ' 
					. $this->getterMethod->getDeclaringClass()->getName() . '::' 
					. $this->getterMethod->getName() . '()  returns unexpected type', 0, $previous);
		}
	}

	public function setValue(object $object, mixed $value, bool $validate = true): void {
		if (isset($this->constraint) && $validate) {
			try {
				$value = $this->constraint->validate($value);
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

		$setterMethod = $this->findMethod($object, $this->setterMethod);
		try {
			$setterMethod->invoke($object, $value);
		} catch (\ReflectionException $e) {
			throw $this->createMethodInvokeException($setterMethod, $e);
		}				
	}

	/**
	 * @throws PropertyValueTypeMissmatchException
	 * @throws PropertyAccessException
	 */
	public function getValue(object $object): mixed {
		$value = null;

		if ($this->isPropertyAccessGetterMode()) {			
			try {
				$value = $this->property->getValue($object);
			} catch (\ReflectionException $e) {
				throw new PropertyAccessException('Could not get value of property '
								.  $this->property->getDeclaringClass()->getName() . '::$' 
						. $this->property->getName() . ' (Read from object type ' . get_class($object) . ')', 0, $e);
			}
		} else {
			$getterMethod = $this->findMethod($object, $this->getterMethod);
			try {
				$value = $getterMethod->invoke($object);
			} catch (\ReflectionException $e) {
				throw $this->createMethodInvokeException($getterMethod, $e, $object);
			}
		}
		
		if ($this->constraint === null || ($value === null && $this->nullReturnAllowed)) {
			return $value;
		}
		
		try {
			$value = $this->constraint->validate($value);
		} catch (ValueIncompatibleWithConstraintsException $e) {
			throw $this->createReturnedValueException($e);
		}
		
		return $value;
	}
	
	private function findMethod($object, \ReflectionMethod $method) {
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

	function createRestricted(TypeConstraint $getterConstraint = null, TypeConstraint $setterConstraint = null): PropertyAccessProxy {
		return new RestrictedAccessProxy($this, $getterConstraint, $setterConstraint);
	}
}
