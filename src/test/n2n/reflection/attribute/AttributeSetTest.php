<?php
namespace n2n\reflection\attribute;

use n2n\reflection\attribute\mock\AttrA;
use n2n\reflection\attribute\mock\AttrB;
use n2n\reflection\attribute\mock\AttrC;
use n2n\reflection\attribute\mock\MockClass;
use n2n\reflection\attribute\mock\PreSuffix;
use PHPUnit\Framework\TestCase;
use n2n\reflection\ReflectionRuntimeException;

class AttributeSetTest extends TestCase {
    private $attributeSet;

	protected function setUp(): void {
		$this->attributeSet = new AttributeSet(new \ReflectionClass(MockClass::class));
	}

	public function testReadClassAttributes() {
		$classAttributes = $this->attributeSet->getClassAttributes();

		foreach($classAttributes as $classAttribute) {
			$this->assertInstanceOf(ClassAttribute::class, $classAttribute);
			$this->assertInstanceOf(\ReflectionAttribute::class, $classAttribute->getAttribute());
			$this->assertIsNumeric($classAttribute->getLine());
			$this->assertIsString($classAttribute->getFile());
		}
	}

    public function testReadClassAttribute() {
        $classAttribute = $this->attributeSet->getClassAttribute(AttrB::class);

        $this->assertInstanceOf(ClassAttribute::class, $classAttribute);
        $this->assertInstanceOf(\ReflectionAttribute::class, $classAttribute->getAttribute());
        $this->assertEquals(AttrB::class, $classAttribute->getAttribute()->getName());
        $this->assertIsString($classAttribute->getFile());
        $this->assertEquals(7, $classAttribute->getLine());
    }

    public function testReadClassConstantAttributes() {
        $attributes = $this->attributeSet->getClassConstantAttributes();

        foreach($attributes as $attribute) {
            $this->assertInstanceOf(ClassConstantAttribute::class, $attribute);
            $this->assertInstanceOf(\ReflectionAttribute::class, $attribute->getAttribute());
            $this->assertIsNumeric($attribute->getLine());
            $this->assertIsString($attribute->getFile());
        }
    }

    public function testReadClassConstantAttribute() {
        $attribute = $this->attributeSet->getClassConstantAttribute('TEST', AttrA::class);

        $this->assertInstanceOf(ClassConstantAttribute::class, $attribute);
        $this->assertInstanceOf(\ReflectionAttribute::class, $attribute->getAttribute());
        $this->assertEquals(AttrA::class, $attribute->getAttribute()->getName());
        $this->assertEquals(14, $attribute->getLine());
        $this->assertIsString($attribute->getFile());

        $this->assertInstanceOf(ClassConstantAttribute::class, $this->attributeSet->getClassConstantAttribute('TEST', AttrB::class));
        $this->assertInstanceOf(ClassConstantAttribute::class, $this->attributeSet->getClassConstantAttribute('TEST', AttrC::class));

        $attribute = $this->attributeSet->getClassConstantAttribute('PUBLIC_CONST', AttrA::class);
        $this->assertNotNull($attribute);
        $this->assertEquals(16, $attribute->getLine());
        $this->assertNull($this->attributeSet->getClassConstantAttribute('PUBLIC_CONST', AttrB::class));

        $attribute = $this->attributeSet->getClassConstantAttribute('PROTECTED_CONST', AttrB::class);
        $this->assertNotNull($attribute);
        $this->assertEquals(18, $attribute->getLine());
        $this->assertNull($this->attributeSet->getClassConstantAttribute('PROTECTED_CONST', AttrC::class));

        $this->assertNull($this->attributeSet->getClassConstantAttribute('PRIVATE_CONST', AttrA::class));
        $this->assertNull($this->attributeSet->getClassConstantAttribute('PRIVATE_CONST', AttrB::class));
        $this->assertNull($this->attributeSet->getClassConstantAttribute('PRIVATE_CONST', AttrC::class));
    }

	public function testReadPropertyAttributes() {
		$propertyAttributes = $this->attributeSet->getPropertyAttributes();

		foreach($propertyAttributes as $propertyAttribute) {
			$this->assertInstanceOf(PropertyAttribute::class, $propertyAttribute);
			$this->assertIsNumeric($propertyAttribute->getLine());
			$this->assertIsString($propertyAttribute->getFile());
		}
	}

    public function testReadPropertyAttribute() {
        $attribute = $this->attributeSet->getPropertyAttribute('publicProperty', AttrA::class);

        $this->assertInstanceOf(PropertyAttribute::class, $attribute);
        $this->assertInstanceOf(\ReflectionAttribute::class, $attribute->getAttribute());
        $this->assertEquals(AttrA::class, $attribute->getAttribute()->getName());
        $this->assertIsString($attribute->getFile());

        $this->assertEquals(22, $attribute->getLine());
        $protectedPropertyAttr = $this->attributeSet->getPropertyAttribute('protectedProperty', AttrB::class);
        $this->assertEquals(24, $protectedPropertyAttr->getLine());

        $privateProperty = $this->attributeSet->getPropertyAttribute('privateProperty', AttrA::class);
        $this->assertNull($privateProperty);
    }

	public function testReadMethodAttributes() {
		$methodAttributes = $this->attributeSet->getMethodAttributes();
		foreach($methodAttributes as $methodAttribute) {
			$this->assertInstanceOf(MethodAttribute::class, $methodAttribute);
			$this->assertInstanceOf(\ReflectionAttribute::class, $methodAttribute->getAttribute());
			$this->assertIsNumeric($methodAttribute->getLine());
			$this->assertIsString($methodAttribute->getFile());
		}

		$this->assertNotNull($this->attributeSet->getMethodAttribute('publicMethod', AttrA::class));
		$this->assertNotNull($this->attributeSet->getMethodAttribute('protectedMethod', AttrB::class));
		$this->assertNull($this->attributeSet->getMethodAttribute('privateMethod', AttrA::class));

		$this->assertTrue($this->attributeSet->hasMethodAttribute('publicMethod', AttrA::class));
		$this->assertTrue($this->attributeSet->hasMethodAttribute('protectedMethod', AttrB::class));
		$this->assertFalse($this->attributeSet->hasMethodAttribute('privateMethod', AttrB::class));
	}

    public function testReadMethodAttribute() {
        $attribute = $this->attributeSet->getMethodAttribute('publicMethod', AttrA::class);
        $this->assertInstanceOf(MethodAttribute::class, $attribute);
        $this->assertInstanceOf(\ReflectionAttribute::class, $attribute->getAttribute());
        $this->assertEquals(AttrA::class, $attribute->getAttribute()->getName());
        $this->assertIsString($attribute->getFile());

        $this->assertEquals(30, $attribute->getLine());
        $protectedMethod = $this->attributeSet->getMethodAttribute('protectedMethod', AttrB::class);
        $this->assertEquals(35, $protectedMethod->getLine());

        $privateMethod = $this->attributeSet->getMethodAttribute('privateMethod', AttrA::class);
        $this->assertNull($privateMethod);
    }

    public function testHasClassAttribute() {
        $this->assertTrue($this->attributeSet->hasClassAttribute(AttrA::class));
        $this->assertTrue($this->attributeSet->hasClassAttribute(AttrB::class));
        $this->assertTrue($this->attributeSet->hasClassAttribute(AttrC::class));
    }

    public function testHasClassConstantAttribute() {
        $this->assertTrue($this->attributeSet->hasClassConstantAttribute('TEST', AttrA::class));
        $this->assertTrue($this->attributeSet->hasClassConstantAttribute('TEST', AttrB::class));
        $this->assertTrue($this->attributeSet->hasClassConstantAttribute('TEST', AttrC::class));

        $this->assertTrue($this->attributeSet->hasClassConstantAttribute('PUBLIC_CONST', AttrA::class));
        $this->assertFalse($this->attributeSet->hasClassConstantAttribute('PUBLIC_CONST', AttrB::class));
        $this->assertTrue($this->attributeSet->hasClassConstantAttribute('PROTECTED_CONST', AttrB::class));
        $this->assertFalse($this->attributeSet->hasClassConstantAttribute('PROTECTED_CONST', AttrC::class));
    }

    public function testHasPropertyAttribute() {
        $this->assertTrue($this->attributeSet->hasPropertyAttribute('publicProperty', AttrA::class));
        $this->assertTrue($this->attributeSet->hasPropertyAttribute('protectedProperty', AttrB::class));
        $this->assertFalse($this->attributeSet->hasPropertyAttribute('privateProperty', AttrC::class));

        $this->assertTrue($this->attributeSet->hasMethodAttribute('publicMethod', AttrA::class));
        $this->assertTrue($this->attributeSet->hasMethodAttribute('protectedMethod', AttrB::class));
        $this->assertFalse($this->attributeSet->hasMethodAttribute('privateMethod', AttrC::class));
    }

    public function testHasMethodAttribute() {
        $this->assertTrue($this->attributeSet->hasMethodAttribute('publicMethod', AttrA::class));
        $this->assertTrue($this->attributeSet->hasMethodAttribute('protectedMethod', AttrB::class));
        $this->assertFalse($this->attributeSet->hasMethodAttribute('privateMethod', AttrC::class));
    }

    public function testGetClassConstantAttributesByName() {
        $this->assertCount(2, $this->attributeSet->getClassConstantAttributesByName(AttrA::class));
        $this->assertCount(2, $this->attributeSet->getClassConstantAttributesByName(AttrB::class));
        $this->assertCount(1, $this->attributeSet->getClassConstantAttributesByName(AttrC::class));
        $this->assertEmpty($this->attributeSet->getClassConstantAttributesByName(Attribute::class));
    }

    public function testGetPropertyAttributesByName() {
        $this->assertNotEmpty($this->attributeSet->getPropertyAttributesByName(AttrA::class));
        $this->assertNotEmpty($this->attributeSet->getPropertyAttributesByName(AttrB::class));
        $this->assertEmpty($this->attributeSet->getPropertyAttributesByName(AttrC::class));
    }

    public function testGetMethodAttributesByName() {
        $this->assertNotEmpty($this->attributeSet->getMethodAttributesByName(AttrA::class));
        $this->assertNotEmpty($this->attributeSet->getMethodAttributesByName(AttrB::class));
        $this->assertEmpty($this->attributeSet->getMethodAttributesByName(AttrC::class));
    }

    public function testContainsClassConstantAttributeName() {
        $this->assertTrue($this->attributeSet->containsClassConstantAttributeName(AttrA::class));
        $this->assertFalse($this->attributeSet->containsClassConstantAttributeName(Attribute::class));
    }

    public function testContainsPropertyAttributeName() {
        $this->assertTrue($this->attributeSet->containsPropertyAttributeName(AttrA::class));
        $this->assertFalse($this->attributeSet->containsPropertyAttributeName(AttrC::class));
    }

    public function testContainsMethodAttributeName() {
        $this->assertTrue($this->attributeSet->containsMethodAttributeName(AttrA::class));
        $this->assertFalse($this->attributeSet->containsMethodAttributeName(AttrC::class));
    }

	public function testPreSuffixPropertiesCorrectly() {
		$attr = $this->attributeSet->getPropertyAttribute('key', PreSuffix::class);
		$instance = $attr->getInstance();
		$this->assertEquals($instance->getPrefix(), 'key[');
		$this->assertEquals($instance->getSuffix(), ']');
	}

	public function testReadNonExistentClassAttribute() {
		$attribute = $this->attributeSet->getClassAttribute('NonExistentClass');
		$this->assertNull($attribute);
	}

	public function testReadNonExistentClassConstantAttribute() {
		$attribute = $this->attributeSet->getClassConstantAttribute('TEST', 'NonExistentClass');
		$this->assertNull($attribute);
	}

	public function testReadNonExistentPropertyAttribute() {
		$attribute = $this->attributeSet->getPropertyAttribute('publicProperty', 'NonExistentClass');
		$this->assertNull($attribute);
	}

	public function testReadNonExistentMethodAttribute() {
		$attribute = $this->attributeSet->getMethodAttribute('publicMethod', 'NonExistentClass');
		$this->assertNull($attribute);
	}

	public function testHasNonExistentClassAttribute() {
		$this->assertFalse($this->attributeSet->hasClassAttribute('NonExistentClass'));
	}

	public function testHasNonExistentClassConstantAttribute() {
		$this->assertFalse($this->attributeSet->hasClassConstantAttribute('TEST', 'NonExistentClass'));
	}

	public function testHasNonExistentPropertyAttribute() {
		$this->assertFalse($this->attributeSet->hasPropertyAttribute('publicProperty', 'NonExistentClass'));
	}

	public function testHasNonExistentMethodAttribute() {
		$this->assertFalse($this->attributeSet->hasMethodAttribute('publicMethod', 'NonExistentClass'));
	}

	public function testGetClassConstantAttributesByNonExistentName() {
		$this->assertEmpty($this->attributeSet->getClassConstantAttributesByName('NonExistentClass'));
	}

	public function testGetPropertyAttributesByNonExistentName() {
		$this->assertEmpty($this->attributeSet->getPropertyAttributesByName('NonExistentClass'));
	}

	public function testGetMethodAttributesByNonExistentName() {
		$this->assertEmpty($this->attributeSet->getMethodAttributesByName('NonExistentClass'));
	}

	public function testContainsNonExistentClassConstantAttributeName() {
		$this->assertFalse($this->attributeSet->containsClassConstantAttributeName('NonExistentClass'));
	}

	public function testContainsNonExistentPropertyAttributeName() {
		$this->assertFalse($this->attributeSet->containsPropertyAttributeName('NonExistentClass'));
	}

	public function testContainsNonExistentMethodAttributeName() {
		$this->assertFalse($this->attributeSet->containsMethodAttributeName('NonExistentClass'));
	}

	public function testGetPropertyAttributeWithNonExistentProperty() {
		$this->expectException(ReflectionRuntimeException::class);
		$this->attributeSet->getPropertyAttribute('nonExistentProperty', AttrA::class);
	}

	public function testHasPropertyAttributeWithNonExistentProperty() {
		$this->expectException(ReflectionRuntimeException::class);
		$this->assertFalse($this->attributeSet->hasPropertyAttribute('nonExistentProperty', AttrA::class));
	}

	public function testGetMethodAttributeWithNonExistentMethod() {
		$this->expectException(ReflectionRuntimeException::class);
		$this->attributeSet->getMethodAttribute('nonExistentMethod', AttrA::class);
	}

	public function testHasMethodAttributeWithNonExistentMethod() {
		$this->expectException(ReflectionRuntimeException::class);
		$this->attributeSet->hasMethodAttribute('nonExistentMethod', AttrA::class);
	}
}