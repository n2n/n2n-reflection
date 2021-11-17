<?php

namespace n2n\reflection\attribute\legacy;

use n2n\reflection\attribute\AttributeSet;

/**
 * Annotations implementing this interface can be converted by { @link LegacyConverter } for { @link AttributeSet }.
 */
interface LegacyAnnotation {
	/**
	 * Returns the Attribute name
	 * @return string
	 */
	public function getAttributeName(): string;

	/**
	 * Returns an instance of Attribute
	 * @return mixed
	 */
	public function toAttributeInstance();
}