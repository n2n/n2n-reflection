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
namespace n2n\reflection;

use n2n\core\TypeLoader;
use n2n\io\ob\OutputBuffer;
use n2n\util\StringUtils;
use n2n\reflection\annotation\Annotation;
use n2n\core\TypeNotFoundException;

class ReflectionUtils {
	const COMMON_MAX_CHARS = 100;
	const ENCODED_NAMESPACE_LEVEL_DEFAULT_SEPARATOR = '-';
	
	public static function captureVarDump($expression, $maxChars = self::COMMON_MAX_CHARS) {
		$outputBuffer = new OutputBuffer();
		$outputBuffer->start();
		var_dump($expression);
		$outputBuffer->end();
		$dump = $outputBuffer->get();
		if (isset($maxChars) && $maxChars < mb_strlen($dump)) {
			return mb_substr($dump, 0, (int) $maxChars) . '...';
		}
		return $outputBuffer->get();
	}
	
	public static function getNamespace($obj): string {
		if (is_object($obj)) {
			return (new \ReflectionClass($obj))->getNamespaceName();
		}
		
		if (is_string($obj)) {
			$parts = explode('\\', $obj);
			array_pop($parts);
			return implode('\\', $parts);
		}
			
		throw new \InvalidArgumentException();
	}
	
	public static function buildUsefullValueIdentifier($value, $maxChars = self::COMMON_MAX_CHARS) {
		if (is_scalar($value)) {
			return mb_substr($value, 0, (int) $maxChars);
		}
		return self::getTypeInfo($value);
	}
	
	public static function getTypeInfo($value) {
		if (is_object($value)) {
			return get_class($value);	
		}
		return gettype($value);
	}
	
	public static function buildScalar($value) {
		if (is_scalar($value)) {
			return $value;
		}
		
		return self::getTypeInfo($value);
	}
	
	public static function extractParameterClass(\ReflectionParameter $parameter) {
		try {
			return $parameter->getClass();
		} catch (\ReflectionException $e) {
			$tlE = TypeLoader::getLatestException();
			
			if ($tlE !== null && $e->getFile() == $tlE->getFile() && $e->getLine() == $tlE->getLine()) {
				$e = new TypeNotFoundException($tlE->getMessage(), null, $e);
			}
			
			$declaringFunction = $parameter->getDeclaringFunction();
			throw new ReflectionErrorException('Unkown type defined for parameter: ' . $parameter->getName(),
					$declaringFunction->getFileName(), $declaringFunction->getStartLine(), null, null, $e);
			
// 			throw new TypeNotFoundException('Unkown type defined for parameter: ' . $parameter->getName(), 0, $e);
		}
	}
	
	public static function extractMethodHierarchy(\ReflectionClass $class, $methodName) {
		$methods = array();
		
		do {
			if ($class->hasMethod($methodName)) {
				$method = $class->getMethod($methodName);
				$methods[] = $method;
				$class = $method->getDeclaringClass();
			}

			$class = $class->getParentClass();
		} while (is_object($class));
		
		return $methods;
	}
	/**
	 * 
	 * @param string $typeName
	 * @return \ReflectionClass
	 * @throws TypeNotFoundException
	 */
	public static function createReflectionClass($typeName): \ReflectionClass {
		TypeLoader::ensureTypeIsLoaded($typeName);
		return new \ReflectionClass($typeName);
	}
	/**
	 * @param \ReflectionClass $class
	 * @throws ObjectCreationFailedException
	 * @return object
	 */
	public static function createObject(\ReflectionClass $class) {
		$args = array();
		
		if (null !== ($constructor = $class->getConstructor())) {
			foreach ($constructor->getParameters() as $parameter) {
				if ($parameter->isOptional()) continue;
	
// 				if ($fillWithNull) {
// 					if ($parameter->allowsNull()) {
// 						$args[] = null;
// 						continue;
// 					}
					
// 					throw new ObjectCreationFailedException('Constructor ' . $constructor->getDeclaringClass()->getName()
// 							. '::' . $constructor->getName() . '() contains parameter which does not allow null value: $'
// 							. $parameter->getName());
// 				}
				
				throw new ObjectCreationFailedException('Constructor ' . $constructor->getDeclaringClass()->getName() 
						. '::' . $constructor->getName() . '() contains non-optional parameter: $' 
						. $parameter->getName());
			}
		}
	
		if ($class->isAbstract()) {
			throw new ObjectCreationFailedException('Class is abstract: ' . $class->getName());
		}
	
		try {
			return $class->newInstanceArgs($args);
		} catch (\ReflectionException $e) {
			throw new ObjectCreationFailedException('Could not create instance: ' 
					. $class->getName(), 0, $e);
		}
	}
	
	
	public static function isClassA(\ReflectionClass $class = null, \ReflectionClass $isAClass = null) {
		if (is_null($class) || is_null($isAClass)) return false;
		return $class->getName() == $isAClass->getName() || $class->isSubclassOf($isAClass);
	}
	
	public static function areClassesEqual(\ReflectionClass $class1 = null, \ReflectionClass $class2 = null) {
		if (is_null($class1) || is_null($class2)) return false;
		return $class1 == $class2;
	}
	
	public static function isObjectA($object, \ReflectionClass $isAClass = null) {
		return /*is_object($object) &&*/ $isAClass !== null && is_a($object, $isAClass->getName());
	}
	
	
		
	public static function buildTypeAcronym($typeName) {
		if (preg_match_all('/[A-Z0-9]+/', $typeName, $matches)) {
			return strtolower(implode('', $matches[0]));
		}
		
		return null;
	}
	
	public static function prettyName($typeName) {
		$typeName = preg_replace('/((?<=[a-z0-9])[A-Z]|(?<=.)[A-Z](?=[a-z]))/', ' ${0}', (string) $typeName);
		
		$typeName = preg_replace_callback('/_./',
				function ($treffer) {
					return ' ' . mb_strtoupper($treffer[0][1]);
				}, $typeName);
		
		$typeName = str_replace(array('[', ']'), array(' (', ')'), $typeName);
		
		return ucfirst($typeName);
	}
	
	public static function prettyClassPropName(\ReflectionClass $class, $propertyName) {
		return self::prettyPropName($class->getName(), $propertyName);
	}
	
	public static function prettyPropName($className, $propertyName) {
		if ($className instanceof \ReflectionClass) {
			$className = $className->getName();
		}
		return $className . '::$' . $propertyName;
	}
	
	public static function prettyReflPropName(\ReflectionProperty $property) {
		return self::prettyPropName($property->getDeclaringClass()->getName(), $property->getName());
	}
	
	public static function prettyMethName($className, $methodName) {
		return $className . '::' . $methodName . '()';
	}
	
	public static function prettyClassMethName(\ReflectionClass $class, $methodName) {
		return self::prettyMethName($class->getName(), $methodName);
	}
	
	public static function prettyReflMethName(\ReflectionFunctionAbstract $method) {
		if ($method instanceof \ReflectionMethod) {
			return self::prettyMethName($method->getDeclaringClass()->getName(), $method->getName());
		}
		
		return $method->getName() . '()';		
	}
	
	public static function prettyValue($value, $maxChars = self::COMMON_MAX_CHARS) {
		if (is_scalar($value)) {
			return StringUtils::reduce($value, $maxChars);
		}
		
		return self::getTypeInfo($value);
	} 
	
	/**
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function stripSpecialChars($string) {
		return preg_replace('/[^0-9a-zA-Z_]/', '', $string);
 	}
 	
 	public static function encodeNamespace($namespace, $namespaceLevelSepartor = self::ENCODED_NAMESPACE_LEVEL_DEFAULT_SEPARATOR) {
 		if (ReflectionUtils::hasSpecialChars($namespace, false)) {
 			throw new \InvalidArgumentException('Invalid namespace: ' . $namespace);
 		}
 		
 		return str_replace('\\', $namespaceLevelSepartor, trim((string) $namespace, '\\'));
 	}
 	
 	public static function decodeNamespace($encodedNamespace, $namespaceLevelSepartor = self::ENCODED_NAMESPACE_LEVEL_DEFAULT_SEPARATOR) {
 		$namespace = str_replace($namespaceLevelSepartor, '\\', trim((string) $encodedNamespace, self::ENCODED_NAMESPACE_LEVEL_DEFAULT_SEPARATOR));
 		
 		if (ReflectionUtils::hasSpecialChars($namespace, false)) {
 			throw new \InvalidArgumentException('Invalid namespace: ' . $namespace);
 		}
 		
 		return $namespace;
 	}
 	/**
 	 * 
 	 * @param string $string
 	 * @return bool
 	 */
 	public static function hasSpecialChars($string, $treatSeparatorAsSpecial = true) {
 		return preg_match('/[^0-9a-zA-Z_' . ($treatSeparatorAsSpecial ? '' : '\\\\') . ']/', $string);
 	}
 	
 	public static function unserialize($serializedStr) {
 		$obj = @unserialize($serializedStr);
 		
 		if ($obj === false && $err = error_get_last()) {
 			throw new ReflectionException($err['message']);
 		}
 	
 		return $obj;
 	}
 	
 	public static function purifyNamespace($namespace) {
 		return trim(str_replace('/', '\\', $namespace), '\\');
 	}
 	
 	private static $times = 0;
 	public static function atuschBreak($maxtimes) {
 		if (self::$times++ >= $maxtimes) {
 			return true;
 		}
 		
 		return false;
 	}
 	
 	private static $timeStarts = array();
 	private static $time = 0;
 	
 	public static function atuschStart() {
 		self::$timeStarts[] = microtime(true);
 	}
 	
 	public static function atuschEnd() {
 		$time =  microtime(true) - array_pop(self::$timeStarts);
 		
 		if (empty(self::$timeStarts)) {
 			self::$time += $time;
 		}
 		
 		return $time;
 	}
 	
 	
 	
 	
 	/**
 	 * Safe for TypeLoader
 	 * @param string $expression
 	 * @param string $relativeNamespace
 	 * @param string $relativeUsed
 	 * @return string
 	 */
 	public static function qualifyTypeName($expression) {
 				return trim(preg_replace('#[\\\\/]{2,}#', '\\', $expression), '\\');
 	}
 	
 	public static function tp($reflectionComponent, &$filePath, &$lineNo) {
 		if ($reflectionComponent instanceof \ReflectionClass) {
 			$filePath = $reflectionComponent->getFileName();
 			$lineNo = $reflectionComponent->getStartLine();
 			return;
 		}
 		
 		if ($reflectionComponent instanceof \ReflectionMethod) {
 			$filePath = $reflectionComponent->getFileName();
			$lineNo = $reflectionComponent->getStartLine();
			return;
		}
		
		if ($reflectionComponent instanceof Annotation) { 
			$filePath = $reflectionComponent->getFileName();
			$lineNo = $reflectionComponent->getLine();
			return;
		}
			
		throw new \InvalidArgumentException('Unsupported reflection compontent type.');
 	}
 	
	public static function getLastTracePoint() {
		
	}
 	
 	public static function getLastMatchingUserTracemPointOfException(\Exception $e, $minBack = 0, $scriptPath = null/*, $outOfMdule = null*/) {
 		$back = (int) $minBack;
 		foreach($e->getTrace() as $key => $tracePoint) {
 			if ($back-- > 0) continue;
 			
 			if (!isset($tracePoint['file'])) continue;
 			 			 				
 			if (isset($scriptPath)) {
 				if ($tracePoint['file'] == $scriptPath) {
 					return $tracePoint;
 				}
 				continue;
 			}
 				
 			// 			if (isset($outOfMdule)) {
 			// 				if (TypeLoader::isFilePartOfNamespace($tracePoint['file'], (string) $outOfMdule)) {
 			// 					continue;
 			// 				} else {
 			// 					return $tracePoint;
 			// 				}
 			// 			}
 				
 			//if (substr($tracePoint['file'], 0, mb_strlen($modulePath)) == $modulePath) {
 			return $tracePoint;
 			//}
 		}
 	
 		return null;
 	}
}
