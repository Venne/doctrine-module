<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\Forms\Containers;

use Doctrine;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Venne;
use Nette;
use Nette\ComponentModel\IContainer;
use DoctrineModule\Forms\Containers\Doctrine\EntityContainer;

/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
interface IObjectContainer extends Nette\ComponentModel\IContainer
{

}