<?php

namespace Deploy\Composer;

include dirname(__FILE__) . '/../../bootstrap.php';

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\RollbackEvent;
use Deploy\Core\Utils;
use Monolog\Logger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\Filesystem\Filesystem;

class DeployTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  vfsStreamDirectory
     */
    private $root;

    /**
     * @var Deploy
     */
    private $composerDeploy;

    /**
     * @var Utils
     */
    private $utils;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $deploy_path;

    private $options;

    private $binary_path;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->deploy_path = 'deploy_dir';
        $this->root = vfsStream::setup($this->deploy_path, null, array(
            'releases' => array(
                'previous_release' => array(
                    'composer.phar' => "composer phar",
                    'composer.json' => "composer json"
                ),
                'current_release' => array(
                    'composer.phar' => "composer phar",
                    'composer.json' => "composer json"
                ),
                'new_release' => array(
                    'composer.phar' => "composer phar",
                    'composer.json' => "composer json"
                )
            )
        ));

        $this->binary_path = "composer.phar";
        $working_dirs = array('.');
        $this->options = array('--verbose', '--prefer-dist');
        $update_vendors = false;
        $timeout = 1000;

        $this->composerDeploy = new Deploy(
            $this->binary_path,
            $working_dirs,
            $this->options,
            $update_vendors,
            $timeout
        );
        $this->composerDeploy->setDeployPath(vfsStream::url($this->deploy_path));

        $this->logger = $this->getMock('Monolog\Logger', array(), array("deploy"));
        $this->composerDeploy->setLogger($this->logger);

        $this->utils = $this->getMock('Deploy\Core\Utils', array('exec'), array($this->logger));
        $this->composerDeploy->setUtils($this->utils);

        $filesystem = new TestFileSystem();
        $this->utils->setFilesystem($filesystem);
    }

    private function getPath($resource)
    {
        return vfsStream::url($this->deploy_path . $resource);
    }

    /**
     * It should run composer install in release which is currently being deployed
     */
    public function testOnDeployEvent()
    {
        $arg1 = array_merge(array('php', $this->binary_path, 'install', '-d', $this->getPath('/releases/new_release/.')), $this->options);

        $this->utils
            ->expects($this->once())
            ->method('exec')
            ->with($this->equalTo($arg1));

        $event = new DeployEvent();
        $event->setTargetDir($this->getPath('/releases/new_release'));
        $event->setCurrentDir($this->getPath('/releases/current_release'));
        $event->setTimestamp('new_release');
        $event->setSourceDir($this->getPath('/source'));

        $this->composerDeploy->onDeployEvent($event);
    }

    /**
     * It should run composer install in current release
     */
    public function testOnDeployEventUndo()
    {
        $arg1 = array_merge(array('php', $this->binary_path, 'install', '-d', $this->getPath('/releases/current_release/.')), $this->options);

        $this->utils
            ->expects($this->once())
            ->method('exec')
            ->with($this->equalTo($arg1));

        $event = new DeployEvent();
        $event->setTargetDir($this->getPath('/releases/new_release'));
        $event->setCurrentDir($this->getPath('/releases/current_release'));
        $event->setTimestamp('new_release');
        $event->setSourceDir($this->getPath('/source'));

        $this->composerDeploy->onDeployEventUndo($event);
    }

    /**
     * It should run composer install in previous release (to which we are rolling back)
     */
    public function testOnRollbackEvent()
    {
        $arg1 = array_merge(array('php', $this->binary_path, 'install', '-d', $this->getPath('/releases/previous_release/.')), $this->options);

        $this->utils
            ->expects($this->once())
            ->method('exec')
            ->with($this->equalTo($arg1));

        $event = new RollbackEvent();
        $event->setTargetDir($this->getPath('/releases/previous_release'));
        $event->setCurrentDir($this->getPath('/releases/current_release'));

        $this->composerDeploy->onRollbackEvent($event);
    }
}

class TestFileSystem extends Filesystem
{
    public function dumpFile($filename, $content, $mode = 0666)
    {
        file_put_contents($filename, $content);
    }
}