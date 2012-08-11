<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\Composer\Installers;

use Venne;
use Venne\Module\Composer\Installers\BaseInstaller;
use Nette\DI\Container;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class DoctrineInstaller extends BaseInstaller
{


	/**
	 * {@inheritDoc}
	 */
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		$this->repo = $repo;
		$extra = $package->getExtra();
		$container = $this->container;

		if (!$container->hasService('doctrine') || !$container->doctrine->createCheckConnection()) {
			throw new \Exception('Database connection not found!');
		}

		// find files
		$robotLoader = new \Nette\Loaders\RobotLoader;
		$robotLoader->setCacheStorage(new \Nette\Caching\Storages\MemoryStorage());
		$robotLoader->addDirectory($this->getInstallPath($package));
		$robotLoader->register();
		$entities = $robotLoader->getIndexedClasses();
		$robotLoader->unregister();

		// autoload
		$downloadPath = $this->getInstallPath($package);
		$generator = new \Composer\Autoload\AutoloadGenerator();
		$map = $generator->parseAutoloads(array(array($package, $downloadPath)));
		/** @var $classLoader \Composer\Autoload\ClassLoader */
		$classLoader = $generator->createLoader($map);
		$classLoader->register();

		$em = $container->entityManager;
		/** @var $metadataDriver \Doctrine\ORM\Mapping\Driver\AnnotationDriver */
		$metadataDriver = $em->getConfiguration()->getMetadataDriverImpl();
		$tool = new \Doctrine\ORM\Tools\SchemaTool($em);

		$paths = array();
		foreach (\Nette\Utils\Finder::findDirectories('Entities')->from($this->getInstallPath($package)) as $file) {
			$paths[] = $file->getPath() . '/Entities';
		}
		$metadataDriver->addPaths($paths);

		$classes = array();
		foreach ($entities as $class => $item) {
			if (\Nette\Reflection\ClassType::from($class)->hasAnnotation('Entity')) {
				$classes[] = $em->getClassMetadata($class);
			}
		}

		try {
			$em->getConnection()->beginTransaction();
			$tool->createSchema($classes);
			$em->getConnection()->commit();
		} catch (Exception $e) {
			$em->getConnection()->rollback();
			$em->close();
			throw $e;
		}

		$this->cleanCache();
	}


	/**
	 * {@inheritDoc}
	 */
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		$this->repo = $repo;
		$extra = $package->getExtra();
		$container = $this->container;

		if (!$container->hasService('doctrine') || !$container->doctrine->createCheckConnection()) {
			throw new \Exception('Database connection not found!');
		}

		// find files
		$robotLoader = new \Nette\Loaders\RobotLoader;
		$robotLoader->setCacheStorage(new \Nette\Caching\Storages\MemoryStorage());
		$robotLoader->addDirectory($this->getInstallPath($package));
		$robotLoader->register();
		$entities = $robotLoader->getIndexedClasses();
		$robotLoader->unregister();

		// autoload
		$downloadPath = $this->getInstallPath($package);
		$generator = new \Composer\Autoload\AutoloadGenerator();
		$map = $generator->parseAutoloads(array(array($package, $downloadPath)));
		/** @var $classLoader \Composer\Autoload\ClassLoader */
		$classLoader = $generator->createLoader($map);
		$classLoader->register();

		$em = $container->entityManager;
		/** @var $metadataDriver \Doctrine\ORM\Mapping\Driver\AnnotationDriver */
		$metadataDriver = $em->getConfiguration()->getMetadataDriverImpl();
		$tool = new \Doctrine\ORM\Tools\SchemaTool($em);

		$paths = array();
		foreach (\Nette\Utils\Finder::findDirectories('Entities')->from($this->getInstallPath($package)) as $file) {
			$paths[] = $file->getPath() . '/Entities';
		}
		$metadataDriver->addPaths($paths);

		$classes = array();
		foreach ($entities as $class => $item) {
			if (\Nette\Reflection\ClassType::from($class)->hasAnnotation('Entity')) {
				$classes[] = $em->getClassMetadata($class);
			}
		}

		try {
			$em->getConnection()->beginTransaction();
			$tool->dropSchema($classes);
			$em->getConnection()->commit();
		} catch (Exception $e) {
			$em->getConnection()->rollback();
			$em->close();
			throw $e;
		}

		$this->cleanCache();
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
