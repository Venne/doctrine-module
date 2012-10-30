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

use Venne;
use Nette\DI\ContainerBuilder;
use Nette\Config\CompilerExtension;
use Nette\Utils\Strings;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class DoctrineExtension extends CompilerExtension
{

	/** @var bool */
	protected static $isConnected;

	const CONNECTIONS_PREFIX = 'connections',
		ENTITY_MANAGERS_PREFIX = 'entityManagers',
		SCHEMA_MANAGERS_PREFIX = 'schemaManagers',
		EVENT_MANAGERS_PREFIX = 'eventManagers',
		CONFIGURATIONS_PREFIX = 'configurations';

	/** @var array */
	public $configurationDefaults = array(
		'annotationReader' => array(
			'namespace' => 'Doctrine\ORM\Mapping',
		),
		'proxiesDir' => '%appDir%/proxies',
		'proxiesNamespace' => 'Proxies',
		'mappingDriver' => 'annotation',
	);

	/** @var array */
	public $schemaManagerDefaults = array(
		'connection' => 'default',
	);

	/** @var array */
	public $eventManagerDefaults = array();

	/** @var array */
	public $connectionDefaults = array(
		'debugger' => TRUE,
		'collation' => FALSE,
		'eventManager' => NULL,
		'autowired' => FALSE,
	);

	/** @var array */
	public $entityManagerDefaults = array(
		'entityDirs' => array('%appDir%'),
		'proxyDir' => '%appDir%/proxies',
		'proxyNamespace' => 'App\Model\Proxies',
		'proxyAutogenerate' => NULL,
		'useAnnotationNamespace' => FALSE,
		'metadataFactory' => NULL,
		'resultCacheDriver' => NULL,
		'console' => FALSE,
	);

	/** @var array */
	public $defaults = array(
		'debugger' => TRUE,
	);

	/** @var array */
	public $metadataDriverClasses = array(
		'driverChain' => 'Doctrine\ORM\Mapping\Driver\DriverChain',
		'annotation' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
		'xml' => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
		'yml' => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
		'php' => 'Doctrine\ORM\Mapping\Driver\PHPDriver',
		'staticphp' => 'Doctrine\ORM\Mapping\Driver\StaticPHPDriver'
	);

	/** @var string|NULL */
	protected $consoleEntityManager;


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		// Cache
		$container->addDefinition($this->prefix("cache"))
			->setInternal(true)
			->setClass("DoctrineModule\Cache", array('@cacheStorage'));

		$container->addDefinition("doctrinePanel")
			->setClass("DoctrineModule\Diagnostics\Panel")
			->setFactory("DoctrineModule\Diagnostics\Panel::register")
			->setShared(false)
			->setAutowired(false);

		//if ($config["debugger"] == "development") {
		//	$container->getDefinition("entityManagerConfig")
		//		->addSetup("setSQLLogger", "@doctrinePanel");
		//}


		// configurations
		foreach ($config["configurations"] as $name => $configuration) {
			$cfg = $configuration + $this->configurationDefaults;
			$this->processConfiguration($name, $cfg);
		}


		// connections
		foreach ($config["connections"] as $name => $connection) {
			$cfg = $connection + $this->connectionDefaults;
			$this->processConnection($name, $cfg);
		}


		// schemaManagers
		foreach ($config["schemaManagers"] as $name => $sm) {
			$cfg = $sm + $this->schemaManagerDefaults;
			$this->processSchemaManager($name, $cfg);
		}


		// eventManagers
		foreach ($config["eventManagers"] as $name => $evm) {
			$cfg = $evm + $this->eventManagerDefaults;
			$this->processEventManager($name, $cfg);
		}


		// entityManagers
		foreach ($config["entityManagers"] as $name => $em) {
			$cfg = $em + $this->entityManagerDefaults;

			if (isset($cfg['connection']) && is_array($cfg['connection'])) {
				$this->processConnection($name, $cfg['connection'] + $this->connectionDefaults);
				$cfg['connection'] = $name;
			}

			if (isset($cfg['configuration']) && is_array($cfg['configuration'])) {
				$this->processConfiguration($name, $cfg['configuration'] + $this->configurationDefaults);
				$cfg['configuration'] = $name;
			}


			$this->processEntityManager($name, $cfg);
		}

		$container->addDefinition($this->prefix('checkConnection'))
			->setFactory("DoctrineModule\DI\DoctrineExtension::checkConnection")
			->setShared(false);

		$container->addDefinition($this->prefix("entityFormMapper"))
			->setClass("DoctrineModule\Forms\Mappers\EntityMapper", array("@entityManager"));

		$this->processConsole();
	}


	protected function processConfiguration($name, array $config)
	{
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->configurationsPrefix($name . 'AnnotationRegistry'))
			->setFactory("Doctrine\Common\Annotations\AnnotationRegistry::registerFile", array($container->parameters["libsDir"] . '/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'))
			->setShared(false)
			->setInternal(true);
		$container->addDefinition($this->configurationsPrefix($name . 'AnnotationReader'))
			->setClass('Doctrine\Common\Annotations\SimpleAnnotationReader', array($this->configurationsPrefix('@' . $name . 'AnnotationRegistry')))
			->addSetup("addNamespace", 'Doctrine\\ORM\\Mapping')
			->setShared(false)
			->setInternal(true);
		$container->addDefinition($this->configurationsPrefix($name . 'CachedAnnotationReader'))
			->setClass("Doctrine\Common\Annotations\CachedReader", array($this->configurationsPrefix('@' . $name . 'AnnotationReader'), "@doctrine.cache"))
			->setInternal(true);

		$paths = array();
		foreach (\Nette\Utils\Finder::findFiles('Entities/*.php')->from($container->parameters['libsDir'] . '/venne') as $file) {
			$paths[] = $file->getPath();
		}

		$container->addDefinition($this->configurationsPrefix($name . 'AnnotationDriver'))
			->setClass("Doctrine\ORM\Mapping\Driver\AnnotationDriver", array($this->configurationsPrefix('@' . $name . 'CachedAnnotationReader'), $paths))
			->addSetup('setFileExtension', '.php')
			->setInternal(true);


		$paths = array();
		foreach (\Nette\Utils\Finder::findFiles('*.dcm.yml')->from($container->parameters['libsDir'] . '/venne') as $file) {
			$paths[] = $file->getPath();
		}
		$paths = array_unique($paths);
		$container->addDefinition($this->configurationsPrefix($name . 'YmlDriver'))
			->setClass("Doctrine\ORM\Mapping\Driver\YamlDriver", array($paths))
			->setInternal(true);


		$container->addDefinition($this->configurationsPrefix($name))
			->setClass("Doctrine\ORM\Configuration")
			->addSetup('setMetadataCacheImpl', '@' . $this->prefix("cache"))
			->addSetup("setQueryCacheImpl", '@' . $this->prefix("cache"))
			->addSetup("setMetadataDriverImpl", $this->configurationsPrefix('@' . $name . ucfirst($config['mappingDriver']). 'Driver'))
			->addSetup("setProxyDir", $config['proxiesDir'])
			->addSetup("setProxyNamespace", $config['proxiesNamespace'])
			->setInternal(true);

		if ($container->parameters["debugMode"]) {
			$container->getDefinition($this->configurationsPrefix($name))
				->addSetup("setAutoGenerateProxyClasses", true);
		}
	}


	protected function processConsole()
	{
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->prefix('consoleCommandDBALRunSql'))
			->setClass('Doctrine\DBAL\Tools\Console\Command\RunSqlCommand')
			->addTag('commnad')
			->setAutowired(FALSE);
		$container->addDefinition($this->prefix('consoleCommandDBALImport'))
			->setClass('Doctrine\DBAL\Tools\Console\Command\ImportCommand')
			->addTag('command')
			->setAutowired(FALSE);

		// console commands - ORM
		$container->addDefinition($this->prefix('consoleCommandORMCreate'))
			->setClass('Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand')
			->addTag('command')
			->setAutowired(FALSE);
		$container->addDefinition($this->prefix('consoleCommandORMUpdate'))
			->setClass('Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand')
			->addTag('command')
			->setAutowired(FALSE);
		$container->addDefinition($this->prefix('consoleCommandORMDrop'))
			->setClass('Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand')
			->addTag('command')
			->setAutowired(FALSE);
		$container->addDefinition($this->prefix('consoleCommandORMGenerateProxies'))
			->setClass('Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand')
			->addTag('command')
			->setAutowired(FALSE);
		$container->addDefinition($this->prefix('consoleCommandORMRunDql'))
			->setClass('Doctrine\ORM\Tools\Console\Command\RunDqlCommand')
			->addTag('command')
			->setAutowired(FALSE);
		$container->addDefinition($this->prefix('consoleCommandORMConvertMapping'))
			->setClass('Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand')
			->addTag('command')
			->setAutowired(FALSE);

		// Helpers
		$container->addDefinition($this->prefix('entityManagerHelper'))
			->setClass('Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper', array('@entityManager'))
			->addTag('commandHelper', 'em')
			->setAutowired(FALSE);
		$container->addDefinition($this->prefix('connectionHelper'))
			->setClass('Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper', array('@' . $this->connectionsPrefix('default')))
			->addTag('commandHelper', 'db')
			->setAutowired(FALSE);
		$container->addDefinition($this->prefix('dialogHelper'))
			->setClass('Symfony\Component\Console\Helper\DialogHelper')
			->addTag('commandHelper', 'dialog')
			->setAutowired(FALSE);
	}

	protected function processEventManager($name, array $config)
	{
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->eventManagersPrefix($name))
			->setClass("Doctrine\Common\EventManager");
	}


	protected function processSchemaManager($name, array $config)
	{
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->schemaManagersPrefix($name))
			->setClass("Doctrine\DBAL\Schema\AbstractSchemaManager")
			->setFactory($this->connectionsPrefix('@' . $config['connection']) . "::getSchemaManager");
	}


	public function processEntityManager($name, array $config)
	{
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->entityManagersPrefix($name))
			->setClass("Doctrine\ORM\EntityManager")
			->setFactory("\Doctrine\ORM\EntityManager::create", array(
				$this->connectionsPrefix('@' . $config['connection']),
				$this->configurationsPrefix('@' . $name),
				$this->eventManagersPrefix('@' . $name)
			)
		);
	}


	public function processConnection($name, array $config)
	{
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->connectionsPrefix($name))
			->setClass("Doctrine\DBAL\Connection")
			->setFactory("Doctrine\DBAL\DriverManager::getConnection", array($config, $config['eventManager']));
	}


	/**
	 * @param string
	 * @return string
	 */
	protected function connectionsPrefix($id)
	{
		$name = Strings::startsWith($id, '@') ?
			('@' . static::CONNECTIONS_PREFIX . '.' . substr($id, 1)) : (static::CONNECTIONS_PREFIX . '.' . $id);
		return $this->prefix($name);
	}


	/**
	 * @param string
	 * @return string
	 */
	protected function entityManagersPrefix($id)
	{
		$name = Strings::startsWith($id, '@') ?
			('@' . static::ENTITY_MANAGERS_PREFIX . '.' . substr($id, 1)) : (static::ENTITY_MANAGERS_PREFIX . '.' . $id);
		return $this->prefix($name);
	}


	/**
	 * @param string
	 * @return string
	 */
	protected function eventManagersPrefix($id)
	{
		$name = Strings::startsWith($id, '@') ?
			('@' . static::EVENT_MANAGERS_PREFIX . '.' . substr($id, 1)) : (static::EVENT_MANAGERS_PREFIX . '.' . $id);
		return $this->prefix($name);
	}


	/**
	 * @param string
	 * @return string
	 */
	protected function schemaManagersPrefix($id)
	{
		$name = Strings::startsWith($id, '@') ?
			('@' . static::SCHEMA_MANAGERS_PREFIX . '.' . substr($id, 1)) : (static::SCHEMA_MANAGERS_PREFIX . '.' . $id);
		return $this->prefix($name);
	}


	/**
	 * @param string
	 * @return string
	 */
	protected function configurationsPrefix($id)
	{
		$name = Strings::startsWith($id, '@') ?
			('@' . static::CONFIGURATIONS_PREFIX . '.' . substr($id, 1)) : (static::CONFIGURATIONS_PREFIX . '.' . $id);
		return $this->prefix($name);
	}


	public static function checkConnection(\Nette\DI\Container $context, \Doctrine\ORM\EntityManager $entityManager)
	{
		if (self::$isConnected === NULL) {
			$ret = true;
			$connection = $entityManager->getConnection();
			$old = set_error_handler(function() use (& $ret)
			{
				$ret = false;
			});

			try {
				$c = $connection->connect();
				if (!is_bool($c)) {
					$ret = false;
				}
				$connection->getSchemaManager()->tablesExist('user'); // try connect with some sql
			} catch (\Exception $ex) {
				$ret = false;
			}

			set_error_handler($old);
			self::$isConnected = $ret;
		}

		return self::$isConnected;
	}


	public function beforeCompile()
	{
		$container = $this->getContainerBuilder();

		$this->prepareRepositories();
		$this->registerListeners();
	}


	protected function prepareRepositories()
	{
		$container = $this->getContainerBuilder();

		foreach ($container->findByTag("repository") as $name => $repository) {

			$definition = $container->getDefinition($name);
			$refl = \Nette\Reflection\ClassType::from($definition->class);

			if (!$refl->isSubclassOf("\\DoctrineModule\\Entities\\IEntity") && $refl->hasAnnotation("Entity")) {
				throw new \Nette\DI\ServiceCreationException("Class {$definition->class} is not instance of entity");
			}

			$anot = $refl->getAnnotation("Entity");
			$definition->setFactory("@entityManager::getRepository", array("\\" . $definition->class));
			$definition->class = substr($anot["repositoryClass"], 0, 1) == "\\" ? substr($anot["repositoryClass"], 1) : $anot["repositoryClass"];
			$definition->setAutowired(false);
		}
	}


	protected function registerListeners()
	{
		$container = $this->getContainerBuilder();
		$evm = $container->getDefinition('doctrine.eventManagers.default');

		foreach ($this->getSortedServices($container, "listener") as $item) {
			$evm->addSetup("addEventSubscriber", "@{$item}");
		}
	}

	/**
	 * @param \Nette\DI\ContainerBuilder $container
	 * @param $tag
	 * @return array
	 */
	protected function getSortedServices(ContainerBuilder $container, $tag)
	{
		$items = array();
		$ret = array();
		foreach ($container->findByTag($tag) as $route => $meta) {
			$priority = isset($meta['priority']) ? $meta['priority'] : (int)$meta;
			$items[$priority][] = $route;
		}

		krsort($items);

		foreach ($items as $items2) {
			foreach ($items2 as $item) {
				$ret[] = $item;
			}
		}
		return $ret;
	}

}

