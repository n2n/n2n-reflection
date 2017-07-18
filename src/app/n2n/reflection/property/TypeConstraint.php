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
use n2n\util\col\ArrayUtils;
use n2n\util\ex\IllegalStateException;

class TypeConstraint {	
	private $typeName;
	private $allowsNull;
	private $arrayFieldTypeConstraint;
	private $whitelistTypes;
	/**
	 * @param unknown $typeName
	 * @param string $allowsNull
	 * @param TypeConstraint $arrayFieldTypeConstraints
	 * @param array $whitelistTypes
	 * @throws \InvalidArgumentException
	 */
	protected function __construct(string $typeName = null, bool $allowsNull = true, 
			TypeConstraint $arrayFieldTypeConstraints = null,array $whitelistTypes = array()) {
		$this->typeName = $typeName;
		$this->allowsNull = (boolean) $allowsNull;
		$this->arrayFieldTypeConstraint = $arrayFieldTypeConstraints;
		$this->whitelistTypes = $whitelistTypes;
	}
	/**
	 * @return string
	 */
	public function getTypeName() {
		return $this->typeName;
	}
// 	/**
// 	 * @return boolean
// 	 */
// 	public function isArray() {
// 		return $this->type == 'array';
// 	}
	
	public function isArrayLike() {
		return $this->arrayFieldTypeConstraint !== null 
				|| ArrayUtils::isTypeNameArrayLike($this->typeName);
	}
	/**
	 * @return boolean
	 */
	public function allowsNull(): bool {
		return $this->allowsNull;
	}
	/**
	 * @return TypeConstraint
	 */
	public function getArrayFieldTypeConstraint() {
		return $this->arrayFieldTypeConstraint;
	}
	
// 	public function getWhitelistTypes() {
// 		return $this->whitelistTypes;
// 	}
	
	public function isValueValid($value) {
		foreach ($this->whitelistTypes as $whitelistType) {
			if (self::isValueA($value, $whitelistType, false)) return true;
		}
		
		if ($value === null) {
			return $this->allowsNull();
		}
		
		if (!self::isValueA($value, $this->typeName, false)) {
			return false;
		}
		
		if ($this->arrayFieldTypeConstraint === null) return true;
		
		if (!ArrayUtils::isArrayLike($value)) {
			if ($this->typeName === null) {
				return false;
			}
				
			throw new IllegalStateException('Illegal constraint ' . $this->__toString() . ' defined:'
					. $this->typeName . ' is not array like.');
		}
		
		foreach ($value as $key => $fieldValue) {
			if (!$this->arrayFieldTypeConstraint->isValueValid($fieldValue)) return false;
		}
		
		return true;
	}
	/**
	 * @param unknown $value
	 * @throws ValueIncompatibleWithConstraintsException
	 */
	public function validate($value) {
		foreach ($this->whitelistTypes as $whitelistType) {
			if (TypeConstraint::isValueA($value, $whitelistType, false)) return;
		}
		
		if ($value === null) {
			if ($this->allowsNull()) return;
			
			throw new ValueIncompatibleWithConstraintsException(
					'Null not allowed with constraints.');
		}
		
		if (!TypeConstraint::isValueA($value, $this->typeName, false)) {
			throw $this->createIncompatbleValueException($value);
		}
		
		if ($this->arrayFieldTypeConstraint === null) return;
		
		if (!ArrayUtils::isArrayLike($value)) {
			if ($this->typeName === null) {
				throw $this->createIncompatbleValueException($value);
			}
			
			throw new IllegalStateException('Illegal constraint ' . $this->__toString() . ' defined:'
				. $this->typeName . ' is no ArrayType.');
		}
		
		foreach ($value as $key => $fieldValue) {
			try {
				$this->arrayFieldTypeConstraint->validate($fieldValue);
			} catch (ValueIncompatibleWithConstraintsException $e) {
				throw new ValueIncompatibleWithConstraintsException(
						'Value type no allowed with constraints ' 
						. $this->__toString() . '. Array field (key: \'' . $key . '\') contains invalid value.', null, $e);
			}
		}
	}
	
	private function createIncompatbleValueException($value) {
		throw new ValueIncompatibleWithConstraintsException(
				'Value type not allowed with constraints. Required type: '
				. $this->__toString() . '; Given type: '
				. ReflectionUtils::getTypeInfo($value));
	}
	
	public function isEmpty() {
		return $this->typeName === null && $this->arrayFieldTypeConstraint === null;
	}
	/**
	 * Returns true if all values which are compatible with the constraints of this instance are also 
	 * compatible with the passed constraints (but not necessary the other way around)
	 * @param TypeConstraint $constraints
	 */
	public function isPassableTo(TypeConstraint $constraints, $ignoreNullAllowed = false) {
		if ($constraints->isEmpty()) return true;
		 
		if (!(self::isTypeA($this->getTypeName(), $constraints->getTypeName()) 
				&& ($ignoreNullAllowed || $constraints->allowsNull() || !$this->allowsNull()))) return false;
				
		$arrayFieldConstraints = $constraints->getArrayFieldTypeConstraint();
		if ($arrayFieldConstraints === null) return true;
		if ($this->arrayFieldTypeConstraint === null) return true;
		
		return $this->arrayFieldTypeConstraint->isPassableTo($arrayFieldConstraints, $ignoreNullAllowed);
	}
	
	public function isPassableBy(TypeConstraint $constraints, $ignoreNullAllowed = false) {
		if ($this->isEmpty()) return true;

		if (!(self::isTypeA($constraints->getTypeName(), $this->getTypeName())
				&& ($ignoreNullAllowed || $this->allowsNull() || !$constraints->allowsNull()))) return false;
		
		if ($this->arrayFieldTypeConstraint === null) return true;
		$arrayFieldConstraints = $constraints->getArrayFieldTypeConstraint();
		if ($arrayFieldConstraints === null) return true;

		return $this->arrayFieldTypeConstraint->isPassableBy($arrayFieldConstraints, $ignoreNullAllowed);
	}
	
	public function getLenientCopy() {
		if ($this->allowsNull || $this->isArrayLike()) return $this;
				
		return new TypeConstraint($this->typeName, true, $this->arrayFieldTypeConstraint, 
				$this->whitelistTypes);
	}
	
	public function __toString(): string {
		if ($this->arrayFieldTypeConstraint === null) {
			return $this->typeName === null ? 'mixed' : $this->typeName;
		}
		
		$str = $this->typeName;
		if ($this->typeName === null) {
			$str = 'ArrayType';
		}
		
		return $str . '<' . $this->arrayFieldTypeConstraint . '>';
	}
	
	

	/**
	 * @param \ReflectionParameter $parameter
	 * @return TypeConstraint
	 */
	public static function createFromParameter(\ReflectionParameter $parameter) {
		return self::createSimple($parameter->isArray() ? 'array' :
				ReflectionUtils::extractParameterClass($parameter), $parameter->allowsNull());
	}
	
	private static function buildTypeName($type) {
		if ($type instanceof \ReflectionClass) {
			return $type->getName();
		}
		
		if ($type === null || is_scalar($type)) {
			return $type;
		}
		
		throw new \InvalidArgumentException(
				'Invalid type parameter passed for TypeConstraint (Allowed: string, ReflectionClass): ' 
						. ReflectionUtils::getTypeInfo($type));
	}
	
	public static function createSimple($type, bool $allowsNull = true, array $whitelistTypes = array()) {
		return new TypeConstraint(self::buildTypeName($type), $allowsNull, null, $whitelistTypes);
	}
	
	public static function createArrayLike($type, bool $allowsNull = true, TypeConstraint $arrayFieldTypeConstraints = null, 
			array $whitelistTypes = array()) {
				return new TypeConstraint(self::buildTypeName($type), $allowsNull, $arrayFieldTypeConstraints, $whitelistTypes);
	}
	
	public static function isValueA($value, $expectedType, bool $nullAllowed): bool {
		if ($expectedType === null || ($nullAllowed && $value === null)) return true;
	
		if (is_array($expectedType)) {
			foreach ($expectedType as $type) {
				if (self::isValueA($value, $type, false)) return true;
			}
			return false;
		}
		
		if ($expectedType instanceof \ReflectionClass) {
			return is_a($value, $expectedType->getName());
		} 
		
		if ($expectedType instanceof TypeConstraint) {
			return $expectedType->isValueValid($value);
		}
		
		switch ($expectedType) {
			case 'scalar':
				return is_scalar($value);
			case 'array':
				return is_array($value);
			case 'string':
				return is_string($value);
			case 'numeric':
				return is_numeric($value);
			case 'int':
				return is_int($value);
			case 'float':
				return is_float($value);
			case 'boolean':
			case 'bool':
				return is_bool($value);
			case 'object':
				return is_object($value);
			case 'resource':
				return is_resource($value);
			case 'null':
			case 'NULL':
				return $value === null;
			default:
				return is_a($value, $expectedType);
		}
		
		return false;
	}
	
	public static function isTypeA($type, $expectedType): bool {
		if ($expectedType === null) return true;
		if ($type === null) return false;
	
		switch ($type) {
			case 'scalar':
				return $expectedType == 'scalar';
			case 'array':
				return $expectedType == 'array';
			case 'string':
				return $expectedType == 'string' || $expectedType == 'scalar';
			case 'numeric':
				return $expectedType == 'numeric' || $expectedType == 'scalar';
		}
	
		if ($type instanceof \ReflectionClass) {
			$type = $type->getName();
		}
	
		if ($expectedType instanceof \ReflectionClass) {
			$expectedType = $expectedType->getName();
		}
	
		return $type == $expectedType || is_subclass_of($type, $expectedType);
	}
}
