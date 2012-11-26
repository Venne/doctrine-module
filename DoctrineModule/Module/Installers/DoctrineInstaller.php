<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\Module\Installers;

use Venne;
use Venne\Utils\File;
use Nette\DI\Container;
use Venne\Module\IModule;
use Doctrine\ORM\EntityManager;
use Nette\Config\Adapters\NeonAdapter;
use Venne\Module\Installers\BaseInstaller;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class DoctrineInstaller extends BaseInstaller
{

	/** @var Container */
	protected $context;

	/** @var string */
	protected $resourcesDir;

	/** @var string */
	protected $configDir;

	/** @var EntityManager */
	protected $entityManager;


	/**
	 * @param Nette\DI\Container $context
	 * @param Doctrine\ORM\EntityManager $entityManager
	 */
	public function __construct(Container $context, EntityManager $entityManager)
	{
		$this->context = $context;
		$this->resourcesDir = $context->parameters['resourcesDir'];
		$this->configDir = $context->parameters['configDir'];
		$this->entityManager = $entityManager;
	}


	/**
	 * @param \Venne\Module\IModule $module
	 */
	public function install(IModule $module)
	{
		$classes = $this->prepare($module);

		$tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);

		try {
			$this->entityManager->getConnection()->beginTransaction();
			$tool->createSchema($classes);
			$this->entityManager->getConnection()->commit();
		} catch (Exception $e) {
			$this->entityManager->getConnection()->rollback();
			$this->entityManager->close();
			throw $e;
		}

		$this->cleanCache();
	}


	/**
	 * @param \Venne\Module\IModule $module
	 */
	public function uninstall(IModule $module)
	{
		$classes = $this->prepare($module);

		$tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);

		try {
			$this->entityManager->getConnection()->beginTransaction();
			$tool->dropSchema($classes);
			$this->entityManager->getConnection()->commit();
		} catch (Exception $e) {
			$this->entityManager->getConnection()->rollback();
			$this->entityManager->close();
			throw $e;
		}

		$this->cleanCache();
	}


	/**
	 * @param \Venne\Module\IModule $module
	 * @return array
	 * @throws \Exception
	 */
	protected function prepare(IModule $module)
	{
		if (!$this->context->hasService('doctrine') || !$this->context->doctrine->createCheckConnection()) {
			throw new \Exception('Database connection not found!');
		}

		// find files
		$robotLoader = new \Nette\Loaders\RobotLoader;
		$robotLoader->setCacheStorage(new \Nette\Caching\Storages\MemoryStorage());
		$robotLoader->addDirectory($module->getPath());
		$robotLoader->register();
		$entities = $robotLoader->getIndexedClasses();
		$robotLoader->unregister();

		// paths
		$paths = array();
		foreach (\Nette\Utils\Finder::findDirectories('Entities')->from($module->getPath()) as $file) {
			$paths[] = $file->getPath() . '/Entities';
		}
		$this->entityManager->getConfiguration()->getMetadataDriverImpl()->addPaths($paths);

		// classes
		$classes = array();
		foreach ($entities as $class => $item) {
			if (\Nette\Reflection\ClassType::from($class)->hasAnnotation('Entity')) {
				$classes[] = $this->entityManager->getClassMetadata($class);
			}
		}

		return $classes;
	}


	protected function cleanCache()
	{
		if (function_exists("apc_fetch")) {
			\apc_clear_cache();
			\apc_clear_cache('user');
			\apc_clear_cache('opcode');
		}
	}
}

