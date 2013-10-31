<?php

use Deploy\Core\Event\DeployEvents;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

$container->setParameter('deploy_git.class', 'Deploy\Git\Deploy');

$container
    ->setDefinition('deploy_git', new DefinitionDecorator('deploy_plugin'))
    ->setClass('%deploy_git.class%')
    ->addArgument('%deploy_git.repository%')
    ->addArgument('%deploy_git.binary_path%')
    ->addArgument('%deploy_git.branch%')
    ->addArgument('%deploy_git.cached_copy_dir%')
    ->addArgument('%deploy_git.enable_submodules%')
    ->addTag('deploy.event_listener', array('event' => DeployEvents::PRE_INIT))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::INIT))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::PRE_DEPLOY))
    ->addTag('deploy.event_listener', array('event' => DeployEvents::DEPLOY));
