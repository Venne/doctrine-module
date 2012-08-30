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
use Venne;
use Nette;
use Nette\ComponentModel\IContainer;
use Venne\Forms\IObjectContainer;
use Venne\Forms\Form;

/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 *
 * @method \Kdyby\Doctrine\Forms\Form getForm(bool $need = TRUE)
 * @method \Kdyby\Doctrine\Forms\Form|\Kdyby\Doctrine\Forms\EntityContainer|\Kdyby\Doctrine\Forms\CollectionContainer getParent()
 * @method void onSave(array $values, \Nette\Forms\Container $container)
 * @method void onLoad(array $values, object $entity)
 */
class EntityContainer extends Nette\Forms\Container implements IObjectContainer
{

	/**
	 * Occurs when the entity values are being mapped to form
	 * @var array of function(array $values, object $entity);
	 */
	public $onLoad = array();

	/**
	 * Occurs when the form values are being mapped to entity
	 * @var array of function(array $values, Nette\Forms\Container $container);
	 */
	public $onSave = array();

	/**
	 * @var object
	 */
	private $data;

	/**
	 * @var \Kdyby\Doctrine\Forms\EntityMapper
	 */
	private $mapper;

	/**
	 * @var \Kdyby\Doctrine\Forms\ContainerBuilder
	 */
	private $builder;



	/**
	 * @param object $data
	 * @param \Kdyby\Doctrine\Forms\EntityMapper $mapper
	 */
	public function __construct($data, EntityMapper $mapper = NULL)
	{
		parent::__construct();
		$this->monitor('Venne\Forms\Form');

		$this->data = $data;
		$this->mapper = $mapper;
	}



	/**
	 * @return \Kdyby\Doctrine\Forms\ContainerBuilder
	 */
	private function getBuilder()
	{
		if ($this->builder === NULL) {
			$class = $this->getMapper()->getMeta($this->getData());
			$this->builder = new ContainerBuilder($this, $class);
		}

		return $this->builder;
	}



	/**
	 * @param string $field
	 * @return \Nette\Forms\Controls\BaseControl
	 */
	public function add($field)
	{
		$this->getBuilder()->addFields($fields = func_get_args());
		return $this[reset($fields)];
	}



	/**
	 * @param  \Nette\ComponentModel\IContainer
	 * @throws \Nette\InvalidStateException
	 */
	protected function validateParent(Nette\ComponentModel\IContainer $parent)
	{
		parent::validateParent($parent);

		if (!$parent instanceof IObjectContainer && !$this->getForm(FALSE) instanceof IObjectContainer) {
			throw new \Nette\InvalidStateException(
				'Valid parent for Kdyby\Doctrine\Forms\EntityContainer '.
					'is only Kdyby\Doctrine\Forms\IObjectContainer, '.
					'instance of "'. get_class($parent) . '" given'
			);
		}
	}



	/**
	 * @return object
	 */
	public function getData()
	{
		return $this->data;
	}



	/**
	 * @return \Kdyby\Doctrine\Forms\EntityMapper
	 */
	private function getMapper()
	{
		return $this->mapper ? : $this->getForm()->getMapper();
	}



	/**
	 * @param \Nette\ComponentModel\Container $obj
	 */
	protected function attached($obj)
	{
		parent::attached($obj);

		if ($obj instanceof Form) {
			foreach ($this->getMapper()->getIdentifierValues($this->data) as $key => $id) {
				$this->addHidden($key)->setDefaultValue($id);
			}

			$this->getMapper()->assign($this->data, $this);
		}
	}



	/**
	 * @param string $name
	 * @param object $entity
	 *
	 * @return \Kdyby\Doctrine\Forms\EntityContainer
	 */
	public function addOne($name, $entity = NULL)
	{
		$entity = $entity ? : $this->getMapper()->getRelated($this, $name);
		return $this[$name] = new EntityContainer($entity);
	}



	/**
	 * @param $name
	 * @param $factory
	 * @param int $createDefault
	 *
	 * @return \Kdyby\Doctrine\Forms\CollectionContainer
	 */
	public function addMany($name, $factory, $createDefault = 0)
	{
		$collection = $this->getMapper()->getCollection($this->data, $name);
		$this[$name] = $container = new CollectionContainer($collection, $factory);
		$container->createDefault = $createDefault;
		return $container;
	}

}