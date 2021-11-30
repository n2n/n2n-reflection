<?php

namespace n2n\reflection\attribute\legacy\mock;

use n2n\reflection\annotation\AnnotationTrait;
use n2n\reflection\annotation\PropertyAnnotation;
use n2n\reflection\annotation\PropertyAnnotationTrait;
use n2n\reflection\attribute\legacy\LegacyAnnotation;
use n2n\reflection\attribute\mock\PreSuffix;

class LegacyPreSuffixMock implements PropertyAnnotation, LegacyAnnotation {
	use PropertyAnnotationTrait, AnnotationTrait;

	private string $prefix;
	private string $suffix;

	public function __construct(string $prefix, string $suffix) {
		$this->prefix = $prefix;
		$this->suffix = $suffix;
	}

	public function getAttributeName(): string {
		return PreSuffix::class;
	}

	public function toAttributeInstance() {
		return new PreSuffix($this->prefix, $this->suffix);
	}
}