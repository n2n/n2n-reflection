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
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\reflection\property;

interface AccessProxy {
	/**
	 * @return string 
	 */
	public function getPropertyName(): string;
	/**
	 * @return TypeConstraint null if no constraints assigned
	 */
	public function getConstraint();
	/**
	 * @param TypeConstraint $constraints
	 * @throws ConstraintsConflictException
	 */
	public function setConstraint(TypeConstraint $constraints);
	/**
	 * @param unknown $object
	 * @param unknown $value
	 * @param string $validate
	 */
	public function setValue($object, $value, $validate = true);
	/**
	 * @param unknown $object
	 * @return mixed
	 */
	public function getValue($object);
	/**
	 * @param unknown $nullReturnAllowed
	 */
	public function setNullReturnAllowed($nullReturnAllowed);
	/**
	 * @return boolean
	 */
	public function isNullReturnAllowed();
	
	public function __toString(): string;
}
