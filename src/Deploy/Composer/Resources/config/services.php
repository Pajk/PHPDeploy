<?php

use Deploy\Core\Event\DeployEvents;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

$container->setParameter('deploy_composer.class', 'Deploy\Composer\Deploy');

$container
    ->setDefinition('deploy_composer', new DefinitionDecorator('deploy_plugin'))
    ->setclass('%deploy_composer.class%')
    ->addArgument('%deploy_composer.binary_path%')
    ->addArgument('%deploy_composer.working_dirs%')
    ->addArgument('%deploy_composer.options%')
    ->addArgument('%deploy_composer.update_vendors%')
    ->addArgument('%deploy_composer.timeout%')
    ->addTag('deploy.event_listener', array('event' => DeployEvents::INIT))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::PRE_DEPLOY))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::DEPLOY))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::ROLLBACK));
