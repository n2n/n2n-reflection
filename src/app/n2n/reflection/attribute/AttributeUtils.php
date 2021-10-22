<?php

namespace n2n\reflection\attribute;

class AttributeUtils {

	public static function extractAttributeLine(\ReflectionAttribute $attribute, \Reflector $reflector) {
//		$attributeDeclarationLine = 0;
//		$declaringClass = $reflector->getDeclaringClass();
//		$classFile      = new \SplFileObject($declaringClass->getFileName());
//
//		$visibility = '(private|protected|public)';
//		$pre = '\s function';
//
//		if ($reflector instanceof \ReflectionProperty) {
//			$visibility = '(private|protected|public|var)';
//			$pre = '\s $';
//		}
//
//		$reflectorName = $reflector->getName();
//		foreach ($classFile as $line => $content) {
//			if (preg_match('/#\[(.*?' . $attribute->getName() . '.*?)]/', $content)) {
//				$attributeDeclarationLine = $line + 1;
//			}
//
//			if (preg_match('/'. $visibility . ' \s ' . $pre .  '\\' . $reflectorName . '/x', $content)) {
//				return $attributeDeclarationLine;
//			}
//		}

		return -1;
	}
}