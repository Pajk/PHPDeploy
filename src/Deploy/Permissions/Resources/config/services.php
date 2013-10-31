<?php

use Deploy\Core\Event\DeployEvents;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

$container->setParameter('deploy_permissions.class', 'Deploy\Permissions\Deploy');

$container
    ->setDefinition('deploy_permissions', new DefinitionDecorator('deploy_plugin'))
    ->setClass('%deploy_permissions.class%')
    ->addArgument('%deploy_permissions.rwx%')
    ->addTag('deploy.event_listener', array('event' => DeployEvents::DEPLOY));
