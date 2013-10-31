<?php

use Deploy\Core\Event\DeployEvents;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

$container->setParameter('deploy_core.class', 'Deploy\Core\Deploy');
$container->setParameter('deploy_core.symfony_event_dispatcher.class', 'Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher');
$container->setParameter('deploy_core.event_dispatcher.class', 'Deploy\Core\EventDispatcher\DeployEventDispatcher');
$container->setParameter('deploy_core.logger.class', 'Monolog\Logger');
$container->setParameter('deploy_core.logger.file_handler', new Reference('file_handler'));
$container->setParameter('deploy_core.logger.echo_handler', new Reference('echo_handler'));

$container
    ->register('event_dispatcher', '%deploy_core.event_dispatcher.class%')
    ->addArgument(new Reference("symfony_event_dispatcher"));

$container
    ->register('symfony_event_dispatcher', '%deploy_core.symfony_event_dispatcher.class%')
    ->addArgument(new Reference("service_container"));

$container
    ->register('file_handler', 'Monolog\Handler\StreamHandler')
    ->addArgument('%deploy_core.deploy_path%/%deploy_core.logger_file%')
    ->addArgument('%deploy_core.logger_level%');

$container
    ->register('echo_handler', 'Deploy\Core\Monolog\Handler\ConsoleHandler')
    ->addArgument('%echo_output%')
    ->addArgument('%deploy_core.logger_echo_level%')
    ->addMethodCall('setFormatter', array(new Reference('echo_formatter')));

$container
    ->register('echo_formatter', 'Deploy\Core\Monolog\Formatter\ConsoleFormatter')
    ->addArgument("%%start_tag%%%%level_name%%:%%end_tag%% %%message%%\n");

$container
    ->register('logger', '%deploy_core.logger.class%')
    ->addArgument('%deploy_core.logger_name%')
    ->addMethodCall('pushHandler', array('%deploy_core.logger.file_handler%'))
    ->addMethodCall('pushHandler', array('%deploy_core.logger.echo_handler%'));

$container
    ->register('utils', 'Deploy\Core\Utils')
    ->addArgument(new Reference('logger'));

$container
    ->setDefinition('deploy_core', new DefinitionDecorator('deploy_plugin'))
    ->setClass('%deploy_core.class%')
    ->addArgument('%deploy_core.history%')
    ->addArgument('%deploy_core.logger_file%')
    ->addTag('deploy.event_listener', array('event' => DeployEvents::PRE_INIT))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::INIT))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::PRE_DEPLOY))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::POST_DEPLOY, 'priority' => -100))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::PRE_ROLLBACK))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::POST_ROLLBACK, 'priority' => -100));

$container
    ->register('deploy_plugin')
    ->setAbstract(true)
    ->addMethodCall('setLogger', array(new Reference('logger')))
    ->addMethodCall('setUtils', array(new Reference('utils')))
    ->addMethodCall('setDeployPath', array('%deploy_core.deploy_path%'));