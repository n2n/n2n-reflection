<?php

namespace n2n\reflection\attribute\legacy\mock;

use n2n\reflection\annotation\AnnotationTrait;
use n2n\reflection\annotation\MethodAnnotation;
use n2n\reflection\annotation\MethodAnnotationTrait;
use n2n\reflection\attribute\legacy\LegacyAnnotation;
use n2n\reflection\attribute\mock\AttrC;

#[AttrC]
class LegacyMethodAnnoMock implements MethodAnnotation, LegacyAnnotation {
	use MethodAnnotationTrait, AnnotationTrait;

	public function getAttributeName(): string {
		return AttrC::class;
	}

	public function toAttributeInstance() {
		return new AttrC();
	}
}