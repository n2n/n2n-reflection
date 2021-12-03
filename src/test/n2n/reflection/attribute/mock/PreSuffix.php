<?php

namespace n2n\reflection\attribute\mock;

#[\Attribute]
class PreSuffix {
	private string $prefix;
	private string $suffix;

	public function __construct(string $prefix, string $suffix) {
		$this->prefix = $prefix;
		$this->suffix = $suffix;
	}

	public function getPrefix(): string {
		return $this->prefix;
	}

	public function setPrefix(string $prefix): void {
		$this->prefix = $prefix;
	}

	public function getSuffix(): string {
		return $this->suffix;
	}

	public function setSuffix(string $suffix): void {
		$this->suffix = $suffix;
	}
}