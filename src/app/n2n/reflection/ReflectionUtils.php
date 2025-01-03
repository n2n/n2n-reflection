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

//use n2n\core\TypeLoader;
use n2n\util\io\ob\OutputBuffer;
use n2n\reflection\annotation\Annotation;
//use n2n\core\TypeNotFoundException;
use n2n\reflection\attribute\Attribute;
use n2n\util\ex\err\FancyError;
use n2n\util\type\NamedTypeConstraint;
use n2n\util\type\UnionTypeConstraint;

class ReflectionUtils {

	const COMMON_MAX_CHARS = 255;

	public static function captureVarDump($expression, $maxChars = self::COMMON_MAX_CHARS): string {
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

	/**
	 * @throws \ReflectionException
	 */
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

	/**
	 * @param \ReflectionParameter $parameter
	 * @return \ReflectionClass|null
	 */
	public static function extractParameterClass(\ReflectionParameter $parameter): ?\ReflectionClass {
		$type = $parameter->getType();
		if (!($type instanceof \ReflectionNamedType) || $type->isBuiltin()) {
			return null;
		}

		try {
			return new \ReflectionClass($type->getName());
		} catch (\ReflectionException $e) {
			$thrownE = $e;
		}

		$declaringFunction = $parameter->getDeclaringFunction();
		throw new ReflectionError('Unhandleable type defined for parameter: ' . $parameter->getName(),
				$declaringFunction->getFileName(), $declaringFunction->getStartLine(), null, null, $e);
	}

	/**
	 * @param \ReflectionParameter $parameter
	 * @return array<\ReflectionClass>
	 */
	public static function extractParameterClasses(\ReflectionParameter $parameter): array {
		$type = $parameter->getType();

		if ($type === null) {
			return [];
		}

		try {
			return self::extractClasses($type);
		} catch (\ReflectionException $e) {
			$declaringFunction = $parameter->getDeclaringFunction();
			throw new ReflectionError('Unknown type defined for parameter: ' . $parameter->getName(),
					$declaringFunction->getFileName(), $declaringFunction->getStartLine(), null, null, $e);
		}
	}

	/**
	 * @throws \ReflectionException
	 */
	private static function extractClasses(\ReflectionType $type): array {
		if ($type instanceof \ReflectionNamedType) {
			if ($type->isBuiltin()) {
				return [];
			}

			return [new \ReflectionClass($type->getName())];
		}

		if ($type instanceof \ReflectionUnionType) {
			$classes = [];
			foreach ($type->getTypes() as $type) {
				array_push($classes, ...self::extractClasses($type));
			}
			return $classes;
		}

		return [];
	}


	public static function hasMethodInHierarchy(\ReflectionClass $class, $methodName): bool {
		do {
			if ($class->hasMethod($methodName)) {
				return true;
			}

			$class = $class->getParentClass();
		} while (is_object($class));

		return false;
	}
	
	public static function extractMethodHierarchy(\ReflectionClass $class, $methodName): array {
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
	 */
	public static function createReflectionClass(string $typeName): \ReflectionClass {
//		if (class_exists(TypeLoader::class, false)) {
//			TypeLoader::ensureTypeIsLoaded($typeName);
//		} else if (!class_exists($typeName)) {
//			throw new TypeNotFoundException('Type not found: ' . $typeName);
//		}

		return ReflectionRuntimeException::try(fn () => new \ReflectionClass($typeName));
	}
	/**
	 * @param \ReflectionClass $class
	 * @return mixed
	 * @throws ObjectCreationFailedException
	 */
	public static function createObject(\ReflectionClass $class, bool $callConstructor = true) {
		$args = array();
		
		if ($callConstructor && null !== ($constructor = $class->getConstructor())) {
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
			if ($callConstructor) {
				return $class->newInstanceArgs($args);
			} else {
				return $class->newInstanceWithoutConstructor();
			}
		} catch (\ReflectionException $e) {
			throw new ObjectCreationFailedException('Could not create instance: '
					. $class->getName(), 0, $e);
		}
	}
	
	
	public static function isClassA(?\ReflectionClass $class = null, ?\ReflectionClass $isAClass = null) {
		if (is_null($class) || is_null($isAClass)) return false;
		return $class->getName() == $isAClass->getName() || $class->isSubclassOf($isAClass);
	}
	
	public static function areClassesEqual(?\ReflectionClass $class1 = null, ?\ReflectionClass $class2 = null) {
		if (is_null($class1) || is_null($class2)) return false;
		return $class1 == $class2;
	}
	
	public static function isObjectA($object, ?\ReflectionClass $isAClass = null) {
		return /*is_object($object) &&*/ $isAClass !== null && is_a($object, $isAClass->getName());
	}
	
	// 	public static function unserialize($serializedStr) {
	// 		$obj = @unserialize($serializedStr);
	
	// 		if ($obj === false && $err = error_get_last()) {
	// 			throw new ReflectionException($err['message']);
	// 		}
	
	// 		return $obj;
	// 	}
	
	/**
	 * @param \ReflectionParameter $parameter
	 * @return boolean
	 */
	static function isArrayParameter(\ReflectionParameter $parameter) {
		return $parameter->getType() instanceOf \ReflectionNamedType
				&& $parameter->getType()->getName() === 'array';
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
 	
 	public static function atuschTime() {
 		return self::$time;
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

		if ($reflectionComponent instanceof Attribute) {
			$filePath = $reflectionComponent->getFile();
			$lineNo = $reflectionComponent->getLine();
			return;
		}
			
		throw new \InvalidArgumentException('Unsupported reflection compontent type: '
				. get_class($reflectionComponent));
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
