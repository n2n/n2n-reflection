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
namespace n2n\reflection\magic;

use n2n\core\N2N;
use n2n\core\module\Module;
use n2n\reflection\ReflectionUtils;
use n2n\util\ex\IllegalStateException;
use n2n\core\TypeNotFoundException;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeUtils;
use n2n\util\type\TypeConstraint;
use n2n\reflection\ReflectionErrorException;
use n2n\util\magic\MagicLookupFailedException;

class MagicMethodInvoker {
	private \ReflectionFunctionAbstract $method;
	private $classParamObjects = array();
	private $paramValues = array();
	/**
	 * @var \n2n\util\type\TypeConstraint|null
	 */
	private $returnTypeConstraint = null;

	public function __construct(private ?MagicContext $magicContext = null) {
		$this->magicContext = $magicContext;
	}

	public function setMethod(?\ReflectionFunctionAbstract $method): void {
		$this->method = $method;
	}

	function setClosure(\Closure $closure): void {
		$this->method = new \ReflectionFunction($closure);
	}

	/**
	 * @return \ReflectionFunctionAbstract
	 */
	public function getMethod(): \ReflectionFunctionAbstract {
		return $this->method;
	}
// 	/**
// 	 * @return Module
// 	 */
// 	private function getModule() {
// 		if (is_null($this->module)) {
// 			$namespaceName = $this->method->getNamespaceName();
// 			if ($this->method instanceof \ReflectionMethod) {
// 				$namespaceName = $this->method->getDeclaringClass()->getNamespaceName();
// 			}
// 			$this->module = N2N::getModuleOfTypeName($namespaceName);
// 		}
// 		return $this->module;
// 	}

	/**
	 * 
	 * @param string $name
	 * @param string $value
	 */
	public function setParamValue($name, $value): void {
		$this->paramValues[$name] = $value;
	}
	/**
	 * 
	 * @param string $name
	 * @param object $obj
	 */
	public function setClassParamObject($className, $obj): void {
		$this->classParamObjects[$className] = $obj;
	}
	
	public function getClassParamObject($className) {
		if (isset($this->classParamObjects[$className])) {
			return $this->classParamObjects[$className];
		}
		
		return null;
	}
	
	/**
	 * @param TypeConstraint $typeConstraint
	 */
	public function setReturnTypeConstraint(?TypeConstraint $typeConstraint): void {
		$this->returnTypeConstraint = $typeConstraint;
	}

	public function getReturnTypeConstraint(): ?TypeConstraint {
		return $this->returnTypeConstraint;
	}
	
	/**
	 * 
	 * @return array
	 * @throws CanNotFillParameterException
	 */
	public function buildArgs(\ReflectionFunctionAbstract $method, array $firstArgs) {
		$args = array();
		foreach ($method->getParameters() as $parameter) {
			if (!empty($firstArgs)) {
				$args[] = array_shift($firstArgs);
				continue;
			}
			
			$parameterName = $parameter->getName();
			if (array_key_exists($parameterName, $this->paramValues)) {
				$args[] = $this->paramValues[$parameterName];
				continue;
			}

			$parameterClass = null;
			
			try {
				$parameterClass = ReflectionUtils::extractParameterClass($parameter);
			} catch (\ReflectionException $e) {
				throw new CanNotFillParameterException($parameter, $e->getMessage(), 0, $e->getPrevious());
			}

			if ($parameterClass !== null 
					&& null !== ($obj = $this->getClassParamObject($parameterClass->getName()))) {
				$args[] = $obj;
				continue;
			}
			
			$previousE = null;
			try {
				$args[] = $this->lookupParameterValue($parameter);
				continue;
			} catch (MagicLookupFailedException $e) {
				$previousE = $e;
			}
			
			$eMsg = 'Can not fill parameter \'' . $parameter->getName() . '\' of magic method '
					. TypeUtils::prettyReflMethName($method) . '.';
			
			if (!empty($this->classParamObjects)) {
				$eMsg .= ' Available magic param types: ' . implode(', ', array_keys($this->classParamObjects));
			}
			
			if (!empty($this->paramValues)) {
				$eMsg .= ' Available magic param names: ' . implode(', ', array_keys($this->paramValues));
			}
			
			throw new CanNotFillParameterException($parameter, $eMsg, 0, $previousE);
		}
		
		return $args;
	}

	/**
	 * @param \ReflectionParameter $parameter
	 * @return mixed
	 * @throws ReflectionErrorException
	 * @throws MagicLookupFailedException
	 */
	private function lookupParameterValue(\ReflectionParameter $parameter) {
		$parameterClass = ReflectionUtils::extractParameterClass($parameter);

		if ($this->magicContext !== null && $parameterClass !== null) {
			$fallbackAvailable = $parameter->isDefaultValueAvailable() || $parameter->allowsNull();
			return $this->magicContext->lookup($parameterClass, !$fallbackAvailable,
					$this->determineNamespaceOfParameter($parameter));
		}

		if ($parameter->isDefaultValueAvailable()) {
			return $parameter->getDefaultValue();
		} else if ($parameter->allowsNull()) {
			return null;
		}

		throw new \LogicException();
	}

	private function determineNamespaceOfParameter(\ReflectionParameter $parameter) {
		if (null !== ($class = $parameter->getDeclaringClass())) {
			return $class->getNamespaceName();
		}

		return $parameter->getDeclaringFunction()->getNamespaceName();
	}

	/**
	 * 
	 * @param object $object
	 * @return mixed
	 */	
	public function invoke($object = null, \ReflectionFunctionAbstract|\Closure $method = null, array $firstArgs = []): mixed {
		if ($method instanceof \Closure) {
			$method = new \ReflectionFunction($method);
		}

		if ($method === null) {
			$method = $this->method;
		}
		
		if ($method === null) {
			throw new IllegalStateException('No method defined.');
		}
		
		$returnValue = null;
		if ($method instanceof \ReflectionMethod) {
			$returnValue = $method->invokeArgs($object, $this->buildArgs($method, $firstArgs));
		} else if ($method->isClosure()) {
			$returnValue = call_user_func(
					\Closure::bind(
							$method->getClosure(),
							$method->getClosureThis(),
							$method->getClosureScopeClass()->name),
					...$this->buildArgs($method, $firstArgs));
		} else {
			$returnValue = $method->invokeArgs($this->buildArgs($method, $firstArgs));
		}
		
		$this->valReturn($method, $returnValue);
		
		return $returnValue;
	}
	
	/**
	 * @param \ReflectionFunctionAbstract $method
	 * @param mixed|null $value
	 */
	private function valReturn($method, $value) {
		if ($this->returnTypeConstraint === null
				|| $this->returnTypeConstraint->isValueValid($value)) {
			return;
		}
		
		throw new ReflectionErrorException(TypeUtils::prettyReflMethName($method) . ' must return ' 
						. $this->returnTypeConstraint . '. '.  TypeUtils::getTypeInfo($value) . ' returned.',
				$method->getFileName(), $method->getStartLine());
	}
}

class CanNotFillParameterException extends \ReflectionException {
	private $parameter;
	/**
	 * 
	 * @param \ReflectionParameter $parameter
	 * @param string $message
	 * @param string $code
	 * @param \Exception $previous
	 */
	public function __construct(\ReflectionParameter $parameter, $message, $code = 0, \Exception $previous = null) {
		parent::__construct($message, $code, $previous);
		
		$this->parameter = $parameter;
	}
	/**
	 * @return \ReflectionParameter
	 */
	public function getParameter() {
		return $this->parameter;
	}
}
