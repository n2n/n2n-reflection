<?php

namespace n2n\reflection\attribute;

use n2n\reflection\attribute\legacy\LegacyConverter;
use n2n\reflection\ReflectionContext;
use n2n\util\ex\UnsupportedOperationException;
use n2n\util\type\ArgUtils;

class AttributeSet {
	/**
	 * The loaded Attributes are stored here
	 * @var Attribute[][][]
	 */
	private array $attributes = array();
	/**
	 * Store which Attributes have already been loaded.
	 * The key contains type and can contain attributename and reflectorname. If SessionScoped Attr
	 * @var array
	 */
	private array $loaded = array();

	private LegacyConverter $legacyConverter;

	private const TYPE_CLASS = 'cl';
	private const TYPE_CONSTANT = 'co';
	private const TYPE_PROPERTY = 'p';
	private const TYPE_METHOD = 'm';

	/**
	 * @param \ReflectionClass $class
	 */
	public function __construct(private \ReflectionClass $class) {
		$this->legacyConverter = new LegacyConverter(ReflectionContext::getAnnotationSet($this->class));
	}

	/**
	 * @return ClassAttribute[]
	 */
	public function getClassAttributes() {
		if (!$this->isLoaded(self::TYPE_CLASS)) {
			$this->loadType(self::TYPE_CLASS);
		}

		return $this->unGroup($this->attributes[self::TYPE_CLASS]);
	}

	/**
	 * @param string $attributeName
	 * @return boolean
	 */
	public function hasClassAttribute($attributeName) {
		return 0 !== count($this->loadAttributes(self::TYPE_CLASS, $attributeName));
	}

	/**
	 * @param string $attributeName
	 */
	public function getClassAttribute($attributeName) {
		if (!$this->isLoaded(self::TYPE_CLASS, $attributeName, $this->class->getName())) {
			$this->loadAttributeFromReflector(self::TYPE_CLASS, $attributeName, $this->class);
		}

		return $this->retrieveAttribute(self::TYPE_CLASS, $attributeName, $this->class->getName());
	}

	/**
	 * @return PropertyAttribute[]
	 */
	public function getPropertyAttributes() {
		if (!$this->isLoaded(self::TYPE_PROPERTY)) {
			$this->loadType(self::TYPE_PROPERTY);
		}

		return $this->unGroup($this->attributes[self::TYPE_PROPERTY]);
	}

	/**
	 * @param string $attributeName
	 * @return PropertyAttribute[]
	 */
	public function getPropertyAttributesByName($attributeName) {
		return $this->unGroup($this->loadAttributes(self::TYPE_PROPERTY, $attributeName));
	}

	/**
	 * @param string $propertyName
	 * @param string $attributeName
	 * @return boolean
	 */
	public function hasPropertyAttribute($propertyName, $attributeName) {
		return null !== $this->loadAttributeFromReflector(self::TYPE_PROPERTY, $attributeName,
				$this->class->getProperty($propertyName));
	}

	/**
	 * @param string $propertyName
	 * @param string $attributeName
     * @return PropertyAttribute
	 */
	public function getPropertyAttribute($propertyName, $attributeName) {
		return $this->loadAttributeFromReflector(self::TYPE_PROPERTY, $attributeName,
				$this->class->getProperty($propertyName));
	}
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function containsPropertyAttributeName($name) {
		return 0 !== count($this->loadAttributes(self::TYPE_PROPERTY, $name));
	}

	/**
	 * @return MethodAttribute[]
	 */
	public function getMethodAttributes() {
		if (!$this->isLoaded(self::TYPE_METHOD)) {
			$this->loadType(self::TYPE_METHOD);
		}

		return $this->unGroup($this->attributes[self::TYPE_METHOD]);
	}

	/**
	 * @param string $name
	 */
	public function getMethodAttributesByName($name) {
		return $this->unGroup($this->loadAttributes(self::TYPE_PROPERTY, $name));
	}

	/**
	 * @param string $methodName
	 * @param string $attributeName
	 * @return boolean
	 */
	public function hasMethodAttribute($methodName, $attributeName) {
		return null !== $this->loadAttributeFromReflector(self::TYPE_METHOD, $attributeName, $this->class->getMethod($methodName));
	}

	/**
	 * @param string $methodName
	 * @param string $attributeName
	 */
	public function getMethodAttribute($methodName, $attributeName) {
		return $this->loadAttributeFromReflector(self::TYPE_METHOD, $attributeName, $this->class->getMethod($methodName));
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function containsMethodAttributeName($name) {
		return 0 !== count($this->loadAttributes(self::TYPE_METHOD, $name));
	}

	private function retrieveAttributes(string $type, string $attributeName = null) {
		if (!isset($this->attributes[$type])) {
			return array();
		}

		if ($attributeName === null) {
			return $this->attributes[$type];
		}

		if (!isset($this->attributes[$type][$attributeName])) {
			return array();
		}

		return $this->attributes[$type][$attributeName];
	}

	/**
	 * @param $type
	 * @param $attributeName
	 * @param $reflectorName
	 * @return Attribute|null
	 */
	private function retrieveAttribute($type, $attributeName, $reflectorName) {
		if (!isset($this->attributes[$type][$attributeName][$reflectorName])) {
			return null;
		}

		return $this->attributes[$type][$attributeName][$reflectorName];
	}

	/**
	 * @param string $type
	 * @param string $attributeName
	 * @return Attribute[]
	 */
	private function loadAttributes(string $type, string $attributeName): array {
		if ($this->isLoaded($type, $attributeName)) {
			return $this->retrieveAttributes($type, $attributeName);
		}

		if (!isset($this->attributes[$type][$attributeName])) {
			$this->attributes[$type][$attributeName] = array();
		}

		$reflectors = $this->getReflectorsByType($type);
		foreach($reflectors as $reflector) {
			foreach ($reflector->getAttributes($attributeName) as $attribute) {
				$this->attributes[$type][$attributeName][$reflector->getName()] = $this->createAttribute($type, $attribute, $reflector);
			}
		}

		$this->loadLegacyAttributes($type, $attributeName);
		$this->setLoaded($type, $attributeName);

		return $this->attributes[$type][$attributeName];
	}

	private function loadType(string $type) {
		$reflectors = $this->getReflectorsByType($type);

		foreach ($reflectors as $reflector) {
			$reflectorName = $reflector->getName();
			foreach ($reflector->getAttributes() as $attribute) {
				$attributeName = $attribute->getName();
				if (!isset($this->propertyAttributes[$attributeName])) {
					$this->attributes[$type][$attributeName] = array();
				}
				$this->attributes[$type][$attributeName][$reflectorName] = $this->createAttribute($type, $attribute, $reflector);
			}
		}

		$this->loadLegacyType($type);
		$this->setLoaded($type);
	}

	private function getReflectorsByType(string $type) {
		$reflectors = [];

		if ($type === self::TYPE_CLASS) {
			$reflectors = [$this->class];
		}

		if ($type === self::TYPE_PROPERTY) {
			$reflectors = $this->class->getProperties();
		}

		if ($type === self::TYPE_METHOD) {
			$reflectors = $this->class->getMethods();
		}

		if ($type === self::TYPE_CONSTANT) {
			$reflectors = $this->class->getConstants();
		}
		return $reflectors;
	}

	private function loadLegacyType($type) {
		if ($type === self::TYPE_CLASS) {
			$this->attributes[$type] = array_merge($this->attributes[$type], $this->legacyConverter->getClassAttributes());
		} elseif ($type === self::TYPE_PROPERTY) {
			$this->attributes[$type] = array_merge($this->attributes[$type], $this->legacyConverter->getPropertyAttributes());
		} elseif ($type === self::TYPE_METHOD) {
			$this->attributes[$type] = array_merge($this->attributes[$type], $this->legacyConverter->getMethodAttributes());
		}
		// constants are not supported for legacy annos
	}

	private function loadLegacyAttributes(string $type, string $attributeName) {
		if ($type === self::TYPE_CLASS) {
			$this->attributes[$type][$attributeName] = array_merge($this->attributes[$type], $this->legacyConverter->getClassAttributesByName($attributeName));
		} elseif ($type === self::TYPE_PROPERTY) {
			$this->attributes[$type][$attributeName] = array_merge($this->attributes[$type], $this->legacyConverter->getPropertyAttributesByName($attributeName));
		} elseif ($type === self::TYPE_METHOD) {
			$this->attributes[$type][$attributeName] = array_merge($this->attributes[$type], $this->legacyConverter->getMethodAttributesByName($attributeName));
		}
	}

	private function loadLegacyAttribute(string $type, string $attributeName, string $reflectorName) {

		if ($this->isLoaded($type, $attributeName, $reflectorName)) {
			return $this->retrieveAttributes($type, $attributeName, $reflectorName);
		}

		if ($type === self::TYPE_CLASS) {
			return $this->legacyConverter->getClassAttribute($attributeName);
		}

		if ($type === self::TYPE_METHOD) {
			return $this->legacyConverter->getMethodAttribute($reflectorName, $attributeName);
		}

		if ($type === self::TYPE_PROPERTY) {
			return $this->legacyConverter->getPropertyAttribute($reflectorName, $attributeName);
		}

        throw new UnsupportedOperationException($type . ' not supported by AttributeSet::loadLegacyAttribute()');
	}

	/**
	 * @param string $type
	 * @param string $attributeName
	 * @param \Reflector $reflectors
	 * @return Attribute
	 */
	private function loadAttributeFromReflector(string $type, string $attributeName, \Reflector $reflector) {
		$reflectorName = $reflector->getName();
		if ($this->isLoaded($type, $attributeName, $reflectorName)) {
			return $this->retrieveAttribute($type, $attributeName, $reflectorName);
		}

		if (!isset($this->attributes[$type][$attributeName])) {
			$this->attributes[$type][$attributeName] = array();
		}

		foreach ($reflector->getAttributes($attributeName) as $attribute) {
			$this->attributes[$type][$attributeName][$reflectorName] = $this->createAttribute($type, $attribute, $reflector);
		}

		$this->loadLegacyAttribute($type, $attributeName, $reflectorName);
		$this->setLoaded(self::TYPE_PROPERTY, $attributeName);

		return $this->retrieveAttribute($type, $attributeName, $reflectorName);
	}

	private function isLoaded(string $type, $attributeName = null, $reflectorName = null) {
		$isTypeLoaded = isset($this->loaded[$this->loadedKey($type)]);
		$isAttributeLoaded = isset($this->loaded[$this->loadedKey($type, $attributeName)]);
		$isAttributeOnReflectorLoaded = isset($this->loaded[$this->loadedKey($type, $attributeName, $reflectorName)]);

		return $isTypeLoaded || $isAttributeLoaded || $isAttributeOnReflectorLoaded;
	}

	private function setLoaded(string $type, string $attributeName = null, string $reflectorName = null) {
		if ($this->isLoaded($type, $reflectorName, $attributeName)) {
			return;
		}

		$this->loaded[] = true;
	}

	private function createAttribute(string $type, \ReflectionAttribute $attribute, \Reflector $reflector) {
		if ($type === self::TYPE_CLASS) {
			ArgUtils::assertTrue($reflector instanceof \ReflectionClass);
			return new ClassAttribute($attribute, $reflector);
		}

		if ($type === self::TYPE_PROPERTY) {
			ArgUtils::assertTrue($reflector instanceof \ReflectionProperty);
			return new PropertyAttribute($attribute, $reflector);
		}

		if ($type === self::TYPE_METHOD) {
			ArgUtils::assertTrue($reflector instanceof \ReflectionMethod);
			return new MethodAttribute($attribute, $reflector);
		}

        throw new UnsupportedOperationException($type . ' not supported by AttributeSet::loadLegacyAttribute()');
	}

	/**
	 * @return string
	 */
	private function loadedKey($type, string $attributeName = null, string $reflectorName = null): string {
		return $type . '-' . $attributeName . '-' . $reflectorName;
	}

	private function unGroup(array $grouped) {
		$attributes = array();
		foreach ($grouped as $groupedAttributes) {
			foreach ($groupedAttributes as $attribute) {
				$attributes[] = $attribute;
			}
		}
		return $attributes;
	}
}