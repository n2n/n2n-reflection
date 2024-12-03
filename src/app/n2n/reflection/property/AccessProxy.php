<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\reflection\property;

use n2n\util\type\TypeConstraint;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\util\ex\IllegalStateException;

interface AccessProxy {

	/**
	 * @return TypeConstraint
	 * @deprecated
	 */
	public function getConstraint(): TypeConstraint;

	/**
	 * @param TypeConstraint $constraint
	 * @throws ConstraintsConflictException
	 * @deprecated
	 */
	public function setConstraint(TypeConstraint $constraint);

	/**
	 * @return TypeConstraint
	 * @throws IllegalStateException if {@link self::isReadable()} returns false.
	 */
	function getGetterConstraint(): TypeConstraint;

	/**
	 * @return bool
	 */
	function isReadable(): bool;

	/**
	 * @return TypeConstraint|null null if {@link self::isWritable()} returns false.
	 */
	function getSetterConstraint(): ?TypeConstraint;

	/**
	 * @return bool
	 */
	function isWritable(): bool;

	function isConstant(): bool;

	/**
	 * @param TypeConstraint|null $getterConstraint
	 * @param TypeConstraint|null $setterConstraint
	 * @return AccessProxy
	 */
	function createRestricted(?TypeConstraint $getterConstraint = null,
			?TypeConstraint $setterConstraint = null): AccessProxy;

	/**
	 * @param object $object
	 * @param mixed $value
	 * @param bool $validate
	 * @throws PropertyAccessException
	 */
	public function setValue(object $object, mixed $value, bool $validate = true): void;
	/**
	 * @param object $object
	 * @return mixed
	 * @throws PropertyAccessException
	 */
	public function getValue(object $object): mixed;
	/**
	 * @param bool $nullReturnAllowed
	 * @deprecated
	 */
	public function setNullReturnAllowed(bool $nullReturnAllowed): void;
	/**
	 * @return boolean
	 * @deprecated
	 */
	public function isNullReturnAllowed();
	
	public function __toString(): string;
}
