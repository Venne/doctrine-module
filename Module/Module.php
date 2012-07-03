<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\Module;

use Nette\DI\Container;
use Nette\Security\Permission;
use Nette\Config\Compiler;
use Nette\Config\Configurator;
use Nette\Object;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
abstract class Module extends \Venne\Module\Module
{


	/**
	 * @param \Nette\DI\Container|SystemContainer $container
	 */
	public function install(Container $container)
	{
		parent::install($container);

		/** @var $em \Doctrine\ORM\EntityManager */
		$em = $container->entityManager;
		$tool = new \Doctrine\ORM\Tools\SchemaTool($em);

		// Add entities to driver path
		/** @var $driver \Doctrine\ORM\Mapping\Driver\AnnotationDriver */
		$driver = $em->getConfiguration()->getMetadataDriverImpl();
		$driver->addPaths(array($this->getPath()));

		// Create db schema
		$classes = array();
		$entities = $container->robotLoader->getIndexedClassesBySubclass("\Nette\Object", ucfirst($this->getName()) . "Module\\");
		foreach ($entities as $entity => $file) {
			$ref = \Nette\Reflection\ClassType::from($entity);
			if ($ref->hasAnnotation("Entity")) {
				$classes[] = $em->getClassMetadata($entity);
			}
		}
		$tool->createSchema($classes);

		if (function_exists("apc_fetch")) {
			\apc_clear_cache();
			\apc_clear_cache('user');
			\apc_clear_cache('opcode');
		}
	}


	/**
	 * @param \Nette\DI\Container|SystemContainer $container
	 */
	public function uninstall(Container $container)
	{
		$em = $container->entityManager;
		$connection = $em->getConnection();
		$dbPlatform = $connection->getDatabasePlatform();
		$tool = new \Doctrine\ORM\Tools\SchemaTool($em);

		// load
		$classes = array();
		$entities = $container->robotLoader->getIndexedClassesBySubclass("\DoctrineModule\ORM\BaseEntity", ucfirst($this->getName()) . "Module\\");
		foreach ($entities as $entity => $file) {
			$ref = \Nette\Reflection\ClassType::from($entity);
			if ($ref->hasAnnotation("Entity")) {
				$classes[] = $em->getClassMetadata($entity);
			}
		}

		// delete entities
		$connection->beginTransaction();
		try {
			foreach ($classes as $class) {
				$repository = $em->getRepository($class->getName());
				foreach ($repository->findAll() as $entity) {
					$em->remove($entity);
				}
			}
			$em->flush();
			$connection->commit();
		} catch (\Exception $e) {
			$connection->rollback();
		}

		// drop schema
		$tool->dropSchema($classes);

		if (function_exists("apc_fetch")) {
			\apc_clear_cache();
			\apc_clear_cache('user');
			\apc_clear_cache('opcode');
		}
	}

}

