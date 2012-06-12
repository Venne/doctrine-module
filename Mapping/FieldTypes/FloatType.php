<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\Mapping\FieldTypes;

use Venne;
use DoctrineModule\Mapping;
use Nette;


/**
 * @author Filip Procházka
 */
class FloatType extends Nette\Object implements Mapping\IFieldType
{

	/**
	 * @param float $value
	 * @param float $current
	 * @return float
	 */
	public function load($value, $current)
	{
		return $value;
	}



	/**
	 * @param float $value
	 * @return float
	 */
	public function save($value)
	{
		return $value;
	}

}