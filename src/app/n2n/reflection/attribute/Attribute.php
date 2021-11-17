<?php

namespace n2n\reflection\attribute;

interface Attribute {
	public function getFile(): string;
	public function getLine(): int;
	public function getAttribute(): \ReflectionAttribute|null;
	public function getInstance(): mixed;
}