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
