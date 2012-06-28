<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule;

use Nette\Config\Compiler;
use Nette\Config\Configurator;
use Nette\DI\Container;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class Module extends \Venne\Module\Module
{


	/** @var string */
	protected $version = "2.0";

	/** @var string */
	protected $description = "Doctrine module for Venne:CMS";


	function __construct()
	{
		require_once __DIR__ . "/Mapping/DiscriminatorEntry.php";
	}

	public function install(Container $container)
	{
		parent::install($container);

		// Add parameters to config.neon
		$adapter = new \Nette\Config\Adapters\NeonAdapter();
		$data = $adapter->load($container->parameters['configDir'] . '/config.neon');
		$data['parameters']['database'] = array(
			'driver' => 'pdo_sqlite',
			'host' => '',
			'user' => '',
			'password' => '',
			'dbname' => '',
			'charset' => 'utf8',
			'driverOptions' => array(
				'1002' => 'SET NAMES utf8',
			),
			'proxyDir' => '%appDir%/proxy',
			'proxyNamespace' => 'proxy',
			'port' => '',
			'path' => '',
			'memory' => '',
			'collation' => '',
		);
		file_put_contents($container->parameters['configDir'] . '/config.neon', $adapter->dump($data));
	}


	public function compile(Compiler $compiler)
	{
		$compiler->addExtension($this->getName(), new DI\DoctrineExtension($this->getPath(), $this->getNamespace()));
	}

}
