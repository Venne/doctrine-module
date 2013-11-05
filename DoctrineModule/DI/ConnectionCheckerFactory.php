<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\DI;

use Nette\Object;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class ConnectionCheckerFactory extends Object
{

	/** @var Callback */
	protected $checkConnection;


	public function __construct($checkConnection)
	{
		$this->checkConnection = $checkConnection;
	}


	public function invoke()
	{
		return $this->checkConnection->invoke();
	}


	function __invoke()
	{
		return $this->invoke();
	}

}

