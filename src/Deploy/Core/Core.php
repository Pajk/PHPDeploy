<?php

namespace Deploy\Core;


use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\DeployEvents;
use Deploy\Core\Event\InitEvent;
use Deploy\Core\Event\RegisterListenersPass;
use Deploy\Core\Event\RollbackEvent;
use Deploy\Core\EventDispatcher\DeployEventDispatcher;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Core
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var DeployEventDispatcher
     */
    private $event_dispatcher;

    /**
     * @var Utils
     */
    private $utils;

    private $deploy_path;

    public function __construct(OutputInterface $output)
    {
        $this->setUpContainer($output);

        $this->event_dispatcher = $this->container->get('event_dispatcher');

        $this->logger = $this->container->get('logger');

        $this->utils = $this->container->get('utils');

        $this->deploy_path = $this->container->getParameter('deploy_core.deploy_path');
    }

    protected function setUpContainer(OutputInterface $output)
    {
        $this->container = new ContainerBuilder();
        $this->container->addCompilerPass(new RegisterListenersPass, PassConfig::TYPE_AFTER_REMOVING);

        $this->container->setParameter('echo_output', $output);

        $plugins = include __DIR__ . "/../../../config/plugins.php";
        $config = include __DIR__ . "/../../../config/config.php";

        /** @var $pluginExtension ExtensionInterface */
        foreach($plugins as $pluginExtension) {
            $params = array();
            if (isset($config[$pluginExtension->getAlias()])) {
                $params = $config[$pluginExtension->getAlias()];
            }
            $this->container->registerExtension($pluginExtension);
            $this->container->loadFromExtension($pluginExtension->getAlias(), $params);
        }

        // load additional parameters
        $loader = new PhpFileLoader($this->container,new FileLocator(__DIR__.'/../../../config'));
        $loader->load('parameters.php');

        $this->container->compile();
    }

    protected function getBuildInPluginPath($pluginName)
    {
        return __DIR__ . "/../" . $pluginName;
    }

    public function init()
    {
        $event = new InitEvent();
        try {
            $this->event_dispatcher->dispatch(DeployEvents::PRE_INIT, $event);
            $this->event_dispatcher->dispatch(DeployEvents::INIT, $event);
            $this->event_dispatcher->dispatch(DeployEvents::POST_INIT, $event);
            $this->logger->info("==== Init finished");
        } catch (\RuntimeException $e) {
            if ($e->getCode() == 500) {
                echo "ALERT: ", $e->getMessage(), "\n";
                echo "Fatal error, cannot continue or undo changes which were already made.\n";
            } else {
                $this->event_dispatcher->dispatch(DeployEvents::FAILED, $event);
                $this->logger->alert($e->getMessage());
                $this->logger->info("==== Init failed");
            }
        }
    }

    public function deploy()
    {
        $event = new DeployEvent();
        try {
            $this->event_dispatcher->dispatch(DeployEvents::PRE_DEPLOY, $event);
            $this->event_dispatcher->dispatch(DeployEvents::DEPLOY, $event);
            $this->event_dispatcher->dispatch(DeployEvents::POST_DEPLOY, $event);
            $this->logger->info("==== Deploy finished");
        } catch (\RuntimeException $e) {
            if ($e->getCode() == 500) {
                echo "ALERT: ", $e->getMessage(), "\n";
                echo "Fatal error, cannot continue or undo changes which were already made.\n";
            } else {
                $this->event_dispatcher->dispatch(DeployEvents::FAILED, $event);
                $this->logger->alert($e->getMessage());
                $this->logger->info("==== Deploy failed");
            }
        }
    }

    public function rollback()
    {
        $event = new RollbackEvent();
        try {
            $this->event_dispatcher->dispatch(DeployEvents::PRE_ROLLBACK, $event);
            $this->event_dispatcher->dispatch(DeployEvents::ROLLBACK, $event);
            $this->event_dispatcher->dispatch(DeployEvents::POST_ROLLBACK, $event);
            $this->logger->info("==== Rollback finished");
        } catch (\RuntimeException $e) {
            if ($e->getCode() == 500) {
                echo "ALERT: ", $e->getMessage(), "\n";
                echo "Fatal error, cannot continue or undo changes which were already made.\n";
            } else {
                $this->event_dispatcher->dispatch(DeployEvents::FAILED, $event);
                $this->logger->alert($e->getMessage());
                $this->logger->info("==== Rollback failed");
            }
        }
    }

} 