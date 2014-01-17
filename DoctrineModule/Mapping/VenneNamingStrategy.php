<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\Mapping;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;


/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class VenneNamingStrategy extends DefaultNamingStrategy
{

	public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
	{
		return strtolower($this->classToNamespace($sourceEntity)) . '_' . parent::joinTableName($sourceEntity, $targetEntity, $propertyName);
	}


	protected function classToNamespace($className)
	{
		return substr($className, 0, strpos($className, '\\') - 6);
	}

}
