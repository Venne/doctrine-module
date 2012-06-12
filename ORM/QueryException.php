<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\ORM;

use Doctrine;
use Venne;
use Nette;

/**
 * @author Filip Procházka
 */
class QueryException extends \Exception
{


	/** @var Doctrine\ORM\Query */
	private $query;



	/**
	 * @param string $message
	 * @param Doctrine\ORM\Query $query
	 * @param \Exception $previous
	 */
	public function __construct($message = "", Doctrine\ORM\Query $query, \Exception $previous = NULL)
	{
		parent::__construct($message, NULL, $previous);

		$this->query = $query;
	}



	/**
	 * @return Doctrine\ORM\Query
	 */
	public function getQuery()
	{
		return $this->query;
	}

}