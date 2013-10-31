<?php

use Deploy\Core\Event\DeployEvents;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

$container->setParameter('deploy_phinx.class', 'Deploy\Phinx\Deploy');

$container
    ->setDefinition('deploy_phinx', new DefinitionDecorator('deploy_plugin'))
    ->setClass('%deploy_phinx.class%')
    ->addArgument('%deploy_phinx.binary_path%')
    ->addArgument('%deploy_phinx.config_files%')
    ->addTag('deploy.event_listener', array('event' => DeployEvents::DEPLOY))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::ROLLBACK));
