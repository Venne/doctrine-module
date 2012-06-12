<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\Forms;

use Nette\Application\UI\Presenter;
use DoctrineModule\Forms\Mapping\EntityFormMapper;
use Doctrine\ORM\EntityManager;
use AssetsModule\Managers\AssetManager;

/**
 * @author	 Josef Kříž
 */
class Form extends \FormsModule\Form
{


	/** @var array of function(Form $form, $entity); Occurs when the form is submitted, valid and entity is saved */
	public $onSave = array();

	/** @var string key of application stored request */
	private $onSaveRestore;

	/** @var Mapping\EntityFormMapper */
	private $mapper;

	/** @var object */
	protected $entity;

	/** @var \Doctrine\ORM\EntityManager */
	protected $entityManager;



	/**
	 * @param EntityFormMapper $mapper
	 * @param EntityManager $entityManager
	 */
	public function __construct(AssetManager $assetManager, EntityFormMapper $mapper, EntityManager $entityManager)
	{
		$this->mapper = $mapper;
		//$this->entity = $entity;
		$this->entityManager = $entityManager;

		//$this->getMapper()->assing($entity, $this);
		parent::__construct($assetManager);
	}



	/**
	 * @param object $entity
	 */
	public function setEntity($entity)
	{
		$this->entity = $entity;
		$this->getMapper()->assing($entity, $this);
	}



	/**
	 * @return Mapping\EntityFormMapper
	 */
	public function getMapper()
	{
		return $this->mapper;
	}



	/**
	 * @return object
	 */
	public function getEntity()
	{
		return $this->entity;
	}
	
	
	public function getEntityManager()
	{
		return $this->entityManager;
	}



	
	/**
	 * @param Nette\ComponentModel\Container $obj
	 */
	protected function attached($obj)
	{
		parent::attached($obj);

		if ($obj instanceof Presenter) {
			if (!$this->isSubmitted()) {
				$this->getMapper()->load();
			} else {
				$this->getMapper()->save();
			}
		}
	}


}

\Nette\Forms\Container::extensionMethod("addOneToManyContainer", function(\Nette\Forms\Container $container, $name, $containerFactory, $entityFactory = NULL)
{
	$container[$name] = new Containers\CollectionContainer($container->getEntity(), $containerFactory, $entityFactory);
	return $container[$name];
});

\Nette\Forms\Container::extensionMethod("addManyToOneContainer", function(\Nette\Forms\Container $container, $name)
{
	$entity = $container->getMapper()->getAssociation($container->getEntity(), $name);
	return $container[$name] = new Containers\Doctrine\EntityContainer($entity);
});

\Nette\Forms\Container::extensionMethod("addOneToOneContainer", function(\Nette\Forms\Container $container, $name)
{
	$entity = $container->getMapper()->getAssociation($container->getEntity(), $name);
	return $container[$name] = new Containers\Doctrine\EntityContainer($entity);
});


\Nette\Forms\Container::extensionMethod("addManyToOne", function(\Nette\Forms\Container $container, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
{
	$ref = $container->entity->getReflection()->getProperty($name);

	if ($ref->hasAnnotation("Form")) {
		$ref = $ref->getAnnotation("Form");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}
	} else {
		$ref = $ref->getAnnotation("ManyToOne");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}
	}

	$items = $container->form->entityManager->getRepository($class)->findBy($criteria, $orderBy, $limit, $offset);

	$container[$name] = new Controls\ManyToOne($label, $items, $size);
	$container[$name]->setPrompt("---------");
	return $container[$name];
});

\Nette\Forms\Container::extensionMethod("addManyToMany", function(\Nette\Forms\Container $container, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
{
	$ref = $container->entity->getReflection()->getProperty($name);

	if ($ref->hasAnnotation("Form")) {
		$ref = $ref->getAnnotation("Form");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}
	} else {
		$ref = $ref->getAnnotation("ManyToMany");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}
	}

	$items = $container->form->entityManager->getRepository($class)->findBy($criteria, $orderBy, $limit, $offset);

	$container[$name] = new Controls\ManyToMany($label, $items, $size);
	return $container[$name];
});

\Nette\Forms\Container::extensionMethod("addOneToMany", function(\Nette\Forms\Container $container, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
{
	$ref = $container->entity->getReflection()->getProperty($name);

	if ($ref->hasAnnotation("Form")) {
		$ref = $ref->getAnnotation("Form");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}
	} else {
		$ref = $ref->getAnnotation("OneToMany");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}
	}

	$items = $container->form->entityManager->getRepository($class)->findBy($criteria, $orderBy, $limit, $offset);

	$container[$name] = new Controls\ManyToMany($label, $items, $size);
	return $container[$name];
});