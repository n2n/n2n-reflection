<?php

namespace n2n\reflection\attribute;

use ReflectionAttribute;

class AttributeUtils {
    public static function extractClassConstantAttributeLine(ReflectionAttribute $attribute, \ReflectionClassConstant $const): int {
        $fileName = $const->getDeclaringClass()->getFileName();
        $reflectorMatchPattern = '/(private|protected|public|) \s const \s ' . $const->getName() . '/x';
        return self::findAttributeDeclaration($fileName, $attribute->getName(), $reflectorMatchPattern);
    }

    public static function extractMethodAttributeLine(ReflectionAttribute $attribute, \ReflectionMethod $method): int {
        $fileName = $method->getDeclaringClass()->getFileName();
        $reflectorMatchPattern = '/(private|protected|public) \s function \s ' . $method->getName() . '/x';
        return self::findAttributeDeclaration($fileName, $attribute->getName(), $reflectorMatchPattern);
    }

	public static function extractPropertyAttributeLine(\ReflectionAttribute $attribute, \ReflectionProperty $property): int {
		$fileName = $property->getDeclaringClass()->getFileName();
        $reflectorMatchPattern = '/(private|protected|public|var) \s \$' . $property->getName() . '/x';
        return self::findAttributeDeclaration($fileName, $attribute->getName(), $reflectorMatchPattern);
	}

    public static function extractClassAttributeLine(?ReflectionAttribute $attribute, \ReflectionClass $class) {
        $fileName = $class->getFileName();
        $reflectorMatchPattern = '/class ' . $class->getShortName() . '/';
        return self::findAttributeDeclaration($fileName, $attribute->getName(), $reflectorMatchPattern);
    }

    /**
     * @param string $fileName
     * @param string $attributeName
     * @param string $reflectorMatchPattern
     * @return int
     */
    private static function findAttributeDeclaration(string $fileName, string $attributeName, string $reflectorMatchPattern): int {
        $attributeNameSplit = explode('\\', $attributeName);
        $attributeName = array_pop($attributeNameSplit);

        $file = new \SplFileObject($fileName);

        $attributeDeclarationLine = 0;
        foreach ($file as $line => $content) {
            if (preg_match('/#\[(.*?' . $attributeName . '.*?)]/', $content)) {
                $attributeDeclarationLine = $line + 1;
            }

            if (preg_match($reflectorMatchPattern, $content)) {
                return $attributeDeclarationLine;
            }
        }

        return -1;
    }
}
