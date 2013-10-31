<?php

namespace Deploy\Core\Command;

use Deploy\Core\Core;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Deploy new project version');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $core = new Core($output);

        $core->deploy();
    }
}