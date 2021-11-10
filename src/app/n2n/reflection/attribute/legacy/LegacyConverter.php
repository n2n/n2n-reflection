<?php

namespace n2n\reflection\attribute\legacy;

use n2n\reflection\annotation\AnnotationSet;
use n2n\reflection\annotation\ClassAnnotation;
use n2n\reflection\annotation\MethodAnnotation;
use n2n\reflection\annotation\PropertyAnnotation;
use n2n\reflection\attribute\Attribute;
use n2n\reflection\attribute\ClassAttribute;
use n2n\reflection\attribute\MethodAttribute;
use n2n\reflection\attribute\PropertyAttribute;

/**
 * Used for converting deprecated annotations to new Attribute API
 */
class LegacyConverter {
	/**
	 * @var AnnotationSet|null
	 */
	private $annotationSet;

	private $classAttributes = array();
	private $propertyAttributes = array();
	private $methodAttributes = array();

	/**
	 * @param AnnotationSet|null $annotationSet
	 */
	public function __construct(AnnotationSet $annotationSet = null) {
		$this->annotationSet = $annotationSet;

		foreach ($this->annotationSet->getClassAnnotations() as $classAnnotation) {
			if (!$classAnnotation instanceof LegacyAnnotation) continue;

			$this->classAttributes[$classAnnotation->getAttributeName()] = $this->toAttr($classAnnotation);
		}

		foreach ($this->annotationSet->getAllPropertyAnnotations() as $propertyAnnotation) {
			if (!$propertyAnnotation instanceof LegacyAnnotation) continue;

			$attributeName = $propertyAnnotation->getAttributeName();
			if (!isset($this->propertyAttributes[$attributeName])) {
				$this->propertyAttributes[$attributeName] = array();
			}

			$propertyName = $propertyAnnotation->getAnnotatedProperty()->getName();
			$this->propertyAttributes[$attributeName][$propertyName] = $this->toAttr($propertyAnnotation);
		}

		foreach ($this->annotationSet->getAllMethodAnnotations() as $methodAnnotation) {
			if (!$methodAnnotation instanceof LegacyAnnotation) continue;

			$attributeName = $methodAnnotation->getAttributeName();
			if (!isset($this->methodAttributes[$attributeName])) {
				$this->methodAttributes[$attributeName] = array();
			}

			$methodName = $methodAnnotation->getAnnotatedMethod()->getName();
			$this->methodAttributes[$attributeName][$methodName] = $this->toAttr($methodAnnotation);
		}
	}

	/**
	 * @return ClassAttribute[]
	 */
	public function getClassAttributes() {
		if ($this->classAttributes !== null) {
			return $this->classAttributes;
		}

		return $this->classAttributes;
	}

	/**
	 * @param string $attributeName
	 * @return bool
	 */
	public function hasClassAttribute(string $attributeName) {
		return isset($this->classAttributes[$attributeName]);
	}

	/**
	 * @param string $attributeName
	 * @return ClassAttribute|null
	 */
	public function getClassAttribute(string $attributeName) {
        if (!isset($this->classAttributes[$attributeName])) {
            return null;
        }

		return $this->classAttributes[$attributeName];
	}

	/**
	 * @param string $attributeName
	 * @return ClassAttribute[]
	 */
	public function getClassAttributesByName(string $attributeName) {
		if (!isset($this->classAttributes[$attributeName])) {
			return [];
		}

		return $this->classAttributes[$attributeName];
	}

	/**
	 * @return PropertyAttribute[]
	 */
	public function getPropertyAttributes() {
		return $this->propertyAttributes;
	}

	/**
	 * @param $attributeName string
	 * @return PropertyAttribute[]
	 */
	public function getPropertyAttributesByName(string $attributeName) {
		if (!isset($this->propertyAttributes[$attributeName])) {
			return [];
		}

		return $this->propertyAttributes[$attributeName];
	}

	/**
	 * @param $propertyName
	 * @param $attributeName
	 * @return bool
	 */
	public function hasPropertyAttribute(string $propertyName, string $attributeName) {
		return isset($this->propertyAttributes[$attributeName][$propertyName]);
	}

	/**
	 * @param $propertyName
	 * @param $attributeName
	 * @return PropertyAttribute
	 */
	public function getPropertyAttribute(string $propertyName, string $attributeName) {
		if (!isset($this->propertyAttributes[$attributeName][$propertyName])) {
			return null;
		}
		return $this->propertyAttributes[$attributeName][$propertyName];
	}

	/**
	 * @param $propertyName
	 * @param $attributeName
	 * @return bool
	 */
	public function containsPropertyAttributeName(string $propertyName, string $attributeName) {
		return isset($this->propertyAttributes[$attributeName][$propertyName]);
	}

	/**
	 * @return MethodAttribute[]
	 */
	public function getMethodAttributes() {
		return $this->methodAttributes;
	}

	/**
	 * @param $methodName string
	 * @return MethodAttribute[]
	 */
	public function getMethodAttributesByName(string $attributeName) {
		if (!isset($this->methodAttributes[$attributeName])) {
            return [];
        }
        return $this->methodAttributes[$attributeName];
	}

	/**
	 * @param string $attributeName
	 * @return bool
	 */
	public function hasMethodAttribute(string $methodName, string $attributeName) {
		return isset($this->methodAttributes[$attributeName][$methodName]);
	}

	/**
	 * @param string $attributeName
	 * @return MethodAttribute
	 */
	public function getMethodAttribute(string $methodName, string $attributeName) {
		if (isset($this->methodAttributes[$attributeName][$methodName])) {
			return $this->methodAttributes[$attributeName][$methodName];
		}
		return null;
	}

	/**
	 * @param string $attributeName
	 * @return bool
	 */
	public function containsMethodAttributeName(string $attributeName) {
		return isset($this->methodAttributes[$attributeName]);
	}

	private function toAttr(LegacyAnnotation $annotation): Attribute {
		$reflectionAttribute = null;
		if ($annotation instanceof ClassAnnotation) {
			$reflector = new \ReflectionClass($annotation::class);
			$reflectionAttribute = new ClassAttribute(null, $annotation->getAnnotatedClass());
		} else if ($annotation instanceof PropertyAnnotation) {
			$reflector = new \ReflectionClass($annotation);
			$reflectionAttribute = current($reflector->getAttributes($annotation->getAttributeName()));
			$reflectionAttribute = new PropertyAttribute(null, $annotation->getAnnotatedProperty());
		} else if ($annotation instanceof MethodAnnotation) {
			$reflector = new \ReflectionClass($annotation);
			$reflectionAttribute = current($reflector->getAttributes($annotation->getAttributeName()));
			$reflectionAttribute = new MethodAttribute(null, $annotation->getAnnotatedMethod());
		}
		return $reflectionAttribute;
	}
}