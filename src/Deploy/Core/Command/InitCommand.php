<?php

namespace Deploy\Core\Command;

use Deploy\Core\Core;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Init project deploy');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $core = new Core($output);

        $core->init();
    }
}