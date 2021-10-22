<?php

namespace n2n\reflection\attribute\legacy\mock;

use n2n\reflection\annotation\AnnotationTrait;
use n2n\reflection\annotation\ClassAnnotation;
use n2n\reflection\annotation\ClassAnnotationTrait;
use n2n\reflection\attribute\legacy\LegacyAnnotation;
use n2n\reflection\attribute\mock\AttrA;

#[AttrA]
class LegacyClassAnnoMock implements ClassAnnotation, LegacyAnnotation {
	use ClassAnnotationTrait, AnnotationTrait;

	public function getAttributeName(): string {
		return AttrA::class;
	}

	public function toAttributeInstance() {
		return new AttrA();
	}
}