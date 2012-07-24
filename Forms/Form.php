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
 * @author     Josef Kříž
 */
class Form extends \FormsModule\Form implements \DoctrineModule\Forms\Containers\IObjectContainer
{


	/**
	 * Occurs when the entity values are being mapped to form
	 * @var array of function(array $values, object $entity);
	 */
	public $onLoad = array();

	/**
	 *  Occurs when the form values are being mapped to entity
	 * @var array of function($values, Nette\Forms\Container $container);
	 */
	public $onSave = array();

	/** @var array of functions */
	public $onAttached = array();

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
		$this->getMapper()->assign($entity, $this);
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

		$this->onAttached();

		if ($obj instanceof Presenter) {
			if (!$this->isSubmitted()) {
				$this->getMapper()->load();
			} else {
				$this->getMapper()->save();
			}
		}
	}


	public function addOne($name)
	{
		$entity = $this->getMapper()->getRelated($this, $name);
		return $this[$name] = new \DoctrineModule\Forms\Containers\EntityContainer($entity);
	}


	public function addMany($name, $containerFactory, $entityFactory = NULL)
	{
		$collection = $this->getMapper()->getCollection($this->getEntity(), $name);
		return $this[$name] = new \DoctrineModule\Forms\Containers\CollectionContainer($collection, $containerFactory);
	}

}

\Nette\Forms\Container::extensionMethod("addManyToOne", function(\Nette\Forms\Container $container, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
{
	$container[$name] = $control = new Controls\ManyToOne($label, $size);

	$fc = function() use ($container, $control, $name, $criteria, $orderBy, $limit, $offset) {
		$ref = $container->entity->getReflection()->getProperty($name);

		$ref = $ref->hasAnnotation("Form") ? $ref->getAnnotation("Form") : $ref->getAnnotation("ManyToOne");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}

		$items = $container->form->entityManager->getRepository($class)->findBy($criteria, $orderBy, $limit, $offset);
		$control->setItems($items);
		$control->setPrompt("---------");
	};

	$container->form->onAttached[] = $fc;
	return $container[$name];
});

\Nette\Forms\Container::extensionMethod("addOneToOne", function(\Nette\Forms\Container $container, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
{
	$container[$name] = $control = new Controls\ManyToOne($label, $size);

	$fc = function() use ($container, $control, $name, $criteria, $orderBy, $limit, $offset) {
		$ref = $container->entity->getReflection()->getProperty($name);

		$ref = $ref->hasAnnotation("Form") ? $ref->getAnnotation("Form") : $ref->getAnnotation("OneToOne");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}

		$items = $container->form->entityManager->getRepository($class)->findBy($criteria, $orderBy, $limit, $offset);
		$control->setItems($items);
		$control->setPrompt("---------");
	};

	$container->form->onAttached[] = $fc;
	return $container[$name];
});

\Nette\Forms\Container::extensionMethod("addManyToMany", function(\Nette\Forms\Container $container, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
{
	$container[$name] = $control = new Controls\ManyToMany($label, $items, $size);

	$fc = function() use ($container, $control, $name, $criteria, $orderBy, $limit, $offset) {
		$ref = $container->entity->getReflection()->getProperty($name);

		$ref = $ref->hasAnnotation("Form") ? $ref->getAnnotation("Form") : $ref->getAnnotation("ManyToMany");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}

		$items = $container->form->entityManager->getRepository($class)->findBy($criteria, $orderBy, $limit, $offset);
		$control->setItems($items);
	};

	$container->form->onAttached[] = $fc;
	return $container[$name];
});

\Nette\Forms\Container::extensionMethod("addOneToMany", function(\Nette\Forms\Container $container, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
{
	$container[$name] = $control = new Controls\ManyToMany($label, $items, $size);

	$fc =  function() use ($container, $control, $name, $criteria, $orderBy, $limit, $offset) {
		$ref = $container->entity->getReflection()->getProperty($name);

		$ref = $ref->hasAnnotation("Form") ? $ref->getAnnotation("Form") : $ref->getAnnotation("OneToMany");
		$class = $ref["targetEntity"];
		if (substr($class, 0, 1) != "\\") {
			$class = "\\" . $container->entity->getReflection()->getNamespaceName() . "\\" . $class;
		}

		$items = $container->form->entityManager->getRepository($class)->findBy($criteria, $orderBy, $limit, $offset);
		$control->setItems($items);
	};

	$container->form->onAttached[] = $fc;
	return $container[$name];
});