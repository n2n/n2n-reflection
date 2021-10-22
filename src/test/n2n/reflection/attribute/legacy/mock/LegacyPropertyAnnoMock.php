<?php

namespace n2n\reflection\attribute\legacy\mock;

use n2n\reflection\annotation\AnnotationTrait;
use n2n\reflection\annotation\PropertyAnnotation;
use n2n\reflection\annotation\PropertyAnnotationTrait;
use n2n\reflection\attribute\legacy\LegacyAnnotation;
use n2n\reflection\attribute\mock\AttrB;

#[AttrB]
class LegacyPropertyAnnoMock implements PropertyAnnotation, LegacyAnnotation {
	use PropertyAnnotationTrait, AnnotationTrait;

	public function getAttributeName(): string {
		return AttrB::class;
	}

	public function toAttributeInstance() {
		return new AttrB();
	}
}