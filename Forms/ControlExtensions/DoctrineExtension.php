<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace DoctrineModule\Forms\ControlExtensions;

use Venne;
use Nette\Object;
use Venne\Forms\IControlExtension;
use Venne\Forms\Form;
use DoctrineModule\Forms\Controls\ManyToMany;
use DoctrineModule\Forms\Controls\ManyToOne;
use DoctrineModule\Forms\Mappers\EntityMapper;

/**
 * @author     Josef Kříž
 */
class DoctrineExtension extends Object implements IControlExtension
{

	/**
	 * @param Form $form
	 */
	public function check($form)
	{
		if (!$form->getMapper() instanceof EntityMapper) {
			throw new \Nette\InvalidArgumentException("Form mapper must be instanceof 'EntityMapper'. '" . get_class($form->getMapper()) . "' is given.");
		}

		if (!$form->getData() instanceof \DoctrineModule\Entities\IEntity) {
			throw new \Nette\InvalidArgumentException("Form data must be instanceof 'IEntity'. '" . get_class($form->getData()) . "' is given.");
		}
	}


	/**
	 * @return array
	 */
	public function getControls(Form $form)
	{
		$this->check($form);

		return array(
			'one', 'many', 'manyToOne', 'manyToMany', 'oneToMany', 'oneToOne'
		);
	}


	/**
	 * @param $form
	 * @param $name
	 * @return \DoctrineModule\Forms\Containers\EntityContainer
	 */
	public function addOne($form, $name)
	{
		$entity = $form->getMapper()->getRelated($form, $name);
		return $form[$name] = new \DoctrineModule\Forms\Containers\EntityContainer($entity);
	}


	/**
	 * @param $form
	 * @param $name
	 * @param $containerFactory
	 * @param null $entityFactory
	 * @return \DoctrineModule\Forms\Containers\CollectionContainer
	 */
	public function addMany($form, $name, $containerFactory, $entityFactory = NULL)
	{
		\FormsModule\Containers\Replicator::register();

		$collection = $form->getMapper()->getCollection($form->getData(), $name);
		return $form[$name] = new \DoctrineModule\Forms\Containers\CollectionContainer($collection, $containerFactory);
	}


	/**
	 * @param $form
	 * @param $name
	 * @param null $label
	 * @param null $items
	 * @param null $size
	 * @param array $criteria
	 * @param array $orderBy
	 * @param null $limit
	 * @param null $offset
	 * @return ManyToOne
	 */
	public function  addManyToOne($form, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
	{
		$form[$name] = $control = new ManyToOne($label, $size);
		$form->form->onAttached[] = $this->callback('ManyToOne', $form, $control, $name, $criteria, $orderBy, $limit, $offset);
		return $form[$name];
	}


	/**
	 * @param $form
	 * @param $name
	 * @param null $label
	 * @param null $items
	 * @param null $size
	 * @param array $criteria
	 * @param array $orderBy
	 * @param null $limit
	 * @param null $offset
	 * @return ManyToOne
	 */
	public function addOneToOne($form, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
	{
		$form[$name] = $control = new ManyToOne($label, $size);
		$form->form->onAttached[] = $this->callback('OneToOne', $form, $control, $name, $criteria, $orderBy, $limit, $offset);
		return $form[$name];
	}


	/**
	 * @param $form
	 * @param $name
	 * @param null $label
	 * @param null $items
	 * @param null $size
	 * @param array $criteria
	 * @param array $orderBy
	 * @param null $limit
	 * @param null $offset
	 * @return ManyToMany
	 */
	public function addManyToMany($form, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
	{
		$form[$name] = $control = new ManyToMany($label, $items, $size);
		$form->getForm()->onAttached[] = $this->callback('ManyToMany', $form, $control, $name, $criteria, $orderBy, $limit, $offset);
		return $form[$name];
	}


	/**
	 * @param $form
	 * @param $name
	 * @param null $label
	 * @param null $items
	 * @param null $size
	 * @param array $criteria
	 * @param array $orderBy
	 * @param null $limit
	 * @param null $offset
	 * @return ManyToMany
	 */
	public function addOneToMany($form, $name, $label = NULL, $items = NULL, $size = NULL, array $criteria = array(), array $orderBy = NULL, $limit = NULL, $offset = NULL)
	{
		$form[$name] = $control = new ManyToMany($label, $items, $size);
		$form->form->onAttached[] = $this->callback('OneToMany', $form, $control, $name, $criteria, $orderBy, $limit, $offset);
		return $form[$name];
	}


	public function callback($type, $container, $control, $name, $criteria, $orderBy, $limit, $offset)
	{
		return function() use ($type, $container, $control, $name, $criteria, $orderBy, $limit, $offset)
		{
			$ref = $container->data->getReflection()->getProperty($name)->getAnnotation($type);

			$class = $ref["targetEntity"];
			if (substr($class, 0, 1) != "\\") {
				$class = "\\" . $container->data->getReflection()->getNamespaceName() . "\\" . $class;
			}

			$items = $container->form->mapper->entityManager->getRepository($class)->findBy($criteria, $orderBy, $limit, $offset);
			$control->setItems($items);

			if ($type === 'ManyToOne' || $type === 'OneToOne') {
				$control->setPrompt("---------");
			}
		};
	}
}
