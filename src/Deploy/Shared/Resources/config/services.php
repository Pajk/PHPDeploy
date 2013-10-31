<?php

use Deploy\Core\Event\DeployEvents;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

$container->setParameter('deploy_shared.class', 'Deploy\Shared\Deploy');

$container
    ->setDefinition('deploy_shared', new DefinitionDecorator('deploy_plugin'))
    ->setClass('%deploy_shared.class%')
    ->addArgument('%deploy_shared.files%')
    ->addArgument('%deploy_shared.folders%')
    ->addArgument('%deploy_shared.template_extensions%')
    ->addTag('deploy.event_listener', array('event' => DeployEvents::PRE_INIT))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::INIT))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::DEPLOY));
