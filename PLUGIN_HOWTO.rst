=======================
Maintenance Mode Plugin
=======================

First we need to prepare directory structure and create all files:

.. code::

    * Deploy
      * Maintenance
        * DependencyInjection
          * Configuration.php
          * DeployMaintenanceExtension.php
        * Resources
          * config
            * services.php
        * Deploy.php


Lets start with ``DeployMaintenanceExtension.php``. It's task is to validate configuration and load services definition.

.. sourcecode:: php

    <?php
    namespace Deploy\Maintenance\DependencyInjection;

    use Symfony\Component\Config\Definition\Processor;
    use Symfony\Component\Config\FileLocator;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
    use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

    class DeployMaintenanceExtension implements ExtensionInterface
    {

        public function getAlias()
        {
            return 'deploy_maintenance';
        }

        public function load(array $configs, ContainerBuilder $container)
        {
            $configuration = new Configuration();
            $processor = new Processor();
            $config = $processor->processConfiguration($configuration, $configs);

            $loader = new PhpFileLoader(
                $container,
                new FileLocator(__DIR__.'/../Resources/config')
            );
            $loader->load('services.php');

            foreach($config as $key => $val) {
                $container->setParameter($this->getAlias() . '.' . $key, $val);
            }
        }

        public function getNamespace()
        {
            return false;
        }

        public function getXsdValidationBasePath()
        {
            return false;
        }
    }

For configuration validation it uses class ``Configuration`` which in case of this plugin checks that only "template_file" and "target_file" options are specified.

.. sourcecode:: php

    <?php
    namespace Deploy\Maintenance\DependencyInjection;

    use Symfony\Component\Config\Definition\Builder\TreeBuilder;
    use Symfony\Component\Config\Definition\ConfigurationInterface;

    class Configuration implements ConfigurationInterface
    {
        public function getConfigTreeBuilder()
        {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('deploy_maintenance');

            $rootNode
                ->children()
                    ->scalarNode('template_file')->isRequired()->end()
                    ->scalarNode('target_file')->isRequired()->end()
                ->end();

            return $treeBuilder;
        }
    }

Next we can write a definition for service which this plugin will provide. This definition belongs to ``services.php``. We use parameters which we defined in class Configuration and pass it to service's constructor. This plugin needs to do something only before and after deploy so let's subscribe to PRE_DEPLOY and POST_DEPLOY events.

.. sourcecode:: php
    
    <?php
    use Deploy\Core\Event\DeployEvents;
    use Symfony\Component\DependencyInjection\DefinitionDecorator;

    $container->setParameter('deploy_maintenance.class', 'Deploy\Maintenance\Deploy');

    $container
        ->setDefinition('deploy_maintenance', new DefinitionDecorator('deploy_plugin'))
        ->setClass('%deploy_maintenance.class%')
        ->addArgument('%deploy_maintenance.template_file%')
        ->addArgument('%deploy_maintenance.target_file%')
        ->addTag('deploy.event_listener', array('event' => DeployEvents::PRE_DEPLOY))
        ->addTag('deploy.event_listener', array('event' => DeployEvents::POST_DEPLOY));

    /** @var  LoggerInterface */
    protected $logger;

    /** @var  Utils */
    protected $utils;

    /** @var  string absolute path */
    protected $deploy_path;

Now we can create very simple service just to test this out. Lets edit ``Deploy.php`` file. We can use logger, utils and read deploy_path because our class inherits from ``DeployBase`` class and our service from ``deploy_plugin`` service.

.. sourcecode:: php

    <?php
    namespace Deploy\Maintenance;

    use Deploy\Core\Event\DeployEvent;
    use Deploy\DeployBase;

    class Deploy extends DeployBase
    {
        private $template_file;
        private $target_file;

        public function __construct($template_file, $target_file)
        {
            $this->template_file = $template_file;
            $this->target_file = $target_file;
        }

        public function onPreDeployEvent(DeployEvent $event)
        {
            $this->logger->debug("Maintenance::PreDeploy");

            $this->logger->alert("Copy {$this->template_file} to {$this->target_file}.");
        }

        public function onPreDeployEventUndo(DeployEvent $event)
        {
            $this->logger->debug("Maintenance::PreDeploy undo");

            $this->logger->alert("Remove {$this->target_file}.");
        }

        public function onPostDeployEvent(DeployEvent $event)
        {
            $this->logger->debug("Maintenance::PostDeploy");

            $this->logger->alert("Remove {$this->target_file}.");
        }
    }

Last thing we need is to enable this plugin in ``config/plugins.php``, so just create a new instance  and configure it in ``config/config.php``.

.. sourcecode:: php

    <?php
    // plugins.php
    return array(
        ...,
        new \Deploy\Maintenance\DependencyInjection\DeployMaintenanceExtension()
    );

    // config.php
    return array(
        ...,
        'deploy_maintenance' => array(
            'template_file' => 'web/maintenance.html',
            'target_file' => 'web/index.html'
        )
    );

Now if you try to deploy with ``./run deploy`` you will see alerts logged from Maintenance plugin. You can take a look at the final `Deploy.php`_ implementation.

.. _Deploy.php: https://github.com/Pajk/PHPDeploy/blob/master/src/Deploy/Maintenance/Deploy.php

