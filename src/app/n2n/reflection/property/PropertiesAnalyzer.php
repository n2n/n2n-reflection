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
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\reflection\property;

use n2n\reflection\ReflectionUtils;
use n2n\core\SysTextUtils;

class PropertiesAnalyzer {
	private $class;
	private $ignoreAccessMethods;
	private $superIgnored;
	/**
	 * 
	 * @param \ReflectionClass $class
	 * @param unknown_type $lenient
	 * @param unknown_type $readable
	 * @param unknown_type $writable
	 */
	public function __construct(\ReflectionClass $class, $ignoreAccessMethods = false, $superIgnored = true) {
		$this->class = $class;
		$this->ignoreAccessMethods = $ignoreAccessMethods;
		$this->superIgnored = $superIgnored;
	}
	
	public function setSuperIgnored($superIgnored) {
		$this->superIgnored = $superIgnored;
	}
	
	public function isSuperIgnored() {
		return $this->superIgnored;
	}
	
	private function isFromThisClass($property) {
		return $property->getDeclaringClass()->getName() == $this->class->getName(); 
	}
	/**
	 * @param bool $includePrivate
	 * @param bool $checkIfAcessable
	 * @return \n2n\reflection\property\PropertyAccessProxy[] 
	 */
	public function analyzeProperties($includePrivate = false, $checkIfAcessable = true) {
		$accessProxies = array();
		
		$visiblity = \ReflectionProperty::IS_PROTECTED|\ReflectionProperty::IS_PUBLIC;
		if ($includePrivate) $visiblity = $visiblity|\ReflectionProperty::IS_PRIVATE;
		
		foreach ($this->class->getProperties($visiblity) as $property) {
			if ($property->isStatic() || ($this->superIgnored && !$this->isFromThisClass($property))) {
				continue;
			}
			
			if ($this->ignoreAccessMethods) {
				$accessProxies[$property->getName()] = new PropertyAccessProxy($property->getName(), $property, null, null);
			} else {
				$accessProxies[$property->getName()] = new PropertyAccessProxy($property->getName(), $property, 
						$this->getGetterMethod($property->getName(), $checkIfAcessable && !$property->isPublic(), $property),
						$this->getSetterMethod($property->getName(), $checkIfAcessable && !$property->isPublic(), $property));
			}
		}	
		return $accessProxies;
	}
	// @todo private properties in super class cause exception, wait for persistance api to solve
	public function analyzeProperty($propertyName, $settingRequired = true, $gettingRequired = true, $required = true) {
		$property = null;
		if ($this->class->hasProperty($propertyName)) {
			$property = $this->class->getProperty($propertyName);
			if ($this->superIgnored && !$this->isFromThisClass($property)) {
				$property = null;
			}
		}  
			
		if (is_null($property) 
				&& ($this->ignoreAccessMethods || !$this->scoutForPropertyMethods($propertyName))) {
			if (!$required) return null;
			throw new UnknownPropertyException('Property not found: ' . $this->class->getName() 
					. '::$' . $propertyName);
		}
		
		if ($this->ignoreAccessMethods) {
			return new PropertyAccessProxy($propertyName, $property, null, null);
		} 
		
		return new PropertyAccessProxy($propertyName, $property,
				$this->getGetterMethod($propertyName, $property === null 
						|| (!$property->isPublic() && $gettingRequired), $property),
				$this->getSetterMethod($propertyName, $property === null 
						|| (!$property->isPublic() && $settingRequired), $property));
	}
	
	private function scoutForPropertyMethods($propertyName) {
		return $this->class->hasMethod('get' . ucfirst($propertyName))
				|| $this->class->hasMethod('set' . ucfirst($propertyName)) || $this->class->hasMethod('is' . ucfirst($propertyName));
	}
	
	private function getGetterMethod($propertyName, $required, \ReflectionProperty $property = null) {
		$getterMethodName = 'get' . ucfirst($propertyName);
		$testMethodName = 'is' . ucfirst($propertyName);
		
		if ($this->class->hasMethod($getterMethodName)) {
			$getterMethod = $this->class->getMethod($getterMethodName);
		} else if ($this->class->hasMethod($testMethodName)) {
			$getterMethod = $this->class->getMethod($testMethodName);
		} else if ($required){
			throw new InaccessiblePropertyException($property, 'Getter method (' 
					. $getterMethodName . ' or ' . $testMethodName 
					. ') for inaccessible property required: ' 
					. ReflectionUtils::prettyClassPropName($this->class, $propertyName));
		} else {
			return null;
		}
	
		if (!$getterMethod->isPublic()) {
			if (!$required) return null;
			
			throw new InvalidPropertyAccessMethodException($getterMethod, 
					'Getter method must have public visibility. Given: ' 
						. $getterMethod->getDeclaringClass()->getName() . '::' 
						. $getterMethod->getName());
		}
	
		foreach ($getterMethod->getParameters() as $parameter) {
			if (!$parameter->isOptional()) {
				throw new InvalidPropertyAccessMethodException($getterMethod, 
						'Property getter method does not allow non optional parameters. Given: ' 
						. $getterMethod->getDeclaringClass()->getName() . '::' 
						. $getterMethod->getName());
			}
		}
	
		return $getterMethod;
	}
	
	public static function buildSetterName($propertyName) {
		return 'set' . ucfirst($propertyName);
	}
	
	private function getSetterMethod($propertyName, $required, \ReflectionProperty $property = null) {
		$setterMethodName = self::buildSetterName($propertyName);
		
		if (!$this->class->hasMethod($setterMethodName)) {
			if (!$required) return null;
			
			throw new InaccessiblePropertyException($property, 
					'Managed property setter method required: ' . ReflectionUtils::prettyClassMethName($this->class, $setterMethodName));
		}
	
		$setterMethod = $this->class->getMethod($setterMethodName);
		if (!$setterMethod->isPublic()) {
			if (!$required) return null;
			
			throw new InvalidPropertyAccessMethodException($setterMethod, 
					'Managed property setter method must have public visibility: ' 
							. ReflectionUtils::prettyReflMethName($setterMethod));
		}
	
		$parameters = $setterMethod->getParameters();
		foreach($parameters as $key => $parameter) {
			if($key > 0 && !$parameter->isOptional()) {
				throw new InvalidPropertyAccessMethodException($setterMethod,
						'Managed property setter method allows only one non optional parameter: '
								. ReflectionUtils::prettyReflMethName($setterMethod));
			}
		}
		
		if (!sizeof($parameters)) {
			throw new InvalidPropertyAccessMethodException($setterMethod, 
						'Managed property setter method requires parameter: '
								. ReflectionUtils::prettyReflMethName($setterMethod));
		}
	
		return $setterMethod;
	}
	
	public function getSetterConstraints($propertyName, &$setterMethod = null) {
		$setterMethod = $this->getSetterMethod($propertyName, false);
		if (is_null($setterMethod)) return null;
		
		$parameter = current($setterMethod->getParameters());
		return TypeConstraint::createFromParameterProperties(ReflectionUtils::extractParameterClass($parameter), 
				$parameter->isArray(), $parameter->allowsNull());
	}
	
	public static function parsePropertyName(\ReflectionMethod $method) {
		$methodName = $method->getName();
		$prefix = mb_substr($methodName, 0, 3);
		if (($prefix == 'get' && $methodName != 'get') 
				|| ($prefix == 'set' && $methodName != 'set')) {
			return lcfirst(mb_substr($methodName, 3));
		}
		
		if ('is' == mb_substr($methodName, 0, 2) && $methodName != 'is') {
			return lcfirst(mb_substr($methodName, 2));
		}
		
		throw new InvalidPropertyAccessMethodException($method, 
				'Property access method must have prefix \'get\', \'set\' or \'is\'. Given: ' 
						. $method->getDeclaringClass()->getName() . '::' . $methodName . '()');
	}
}
