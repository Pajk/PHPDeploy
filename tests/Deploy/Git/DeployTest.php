<?php

namespace Deploy\Git;

include dirname(__FILE__) . '/../../bootstrap.php';

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\InitEvent;
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
    private $gitDeploy;

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

    private $repository;
    private $binary_path;
    private $branch;
    private $cached_copy_dir;
    private $enable_submodules;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->deploy_path = 'deploy_dir';
        $this->root = vfsStream::setup($this->deploy_path);

        $this->repository = "git@repo.git";
        $this->binary_path = "git";
        $this->branch = "master";
        $this->cached_copy_dir = "local-clone";
        $this->enable_submodules = false;

        $this->gitDeploy = new Deploy(
            $this->repository,
            $this->binary_path,
            $this->branch,
            $this->cached_copy_dir,
            $this->enable_submodules
        );

        $this->gitDeploy->setDeployPath(vfsStream::url($this->deploy_path));

        $this->logger = $this->getMock('Monolog\Logger', array(), array("deploy"));
        $this->gitDeploy->setLogger($this->logger);

        $this->utils = $this->getMock('Deploy\Core\Utils', array('exec', 'getTimestamp', 'symlink', 'remove', 'copy'), array($this->logger));
        $this->gitDeploy->setUtils($this->utils);

        $filesystem = new TestFileSystem();
        $this->utils->setFilesystem($filesystem);
    }

    private function getPath($resource)
    {
        return vfsStream::url($this->deploy_path . $resource);
    }

    public function testOnPreInit()
    {
        $this->utils
            ->expects($this->at(0))
            ->method('exec')
            ->with(array($this->binary_path, '--version'));

        $this->utils
            ->expects($this->at(1))
            ->method('exec')
            ->with(array($this->binary_path, 'ls-remote', $this->repository, 'HEAD'));

        $event = new InitEvent();

        $this->gitDeploy->onPreInitEvent($event);

        $this->assertEquals($this->getPath('/' . $this->cached_copy_dir), $event->getSourceDir());

        return $event;
    }

    public function checkoutBranch($revision, $offset = 0, $enable_submodules = false)
    {
        $this->utils
            ->expects($this->at($offset++))
            ->method('exec')
            ->with(array($this->binary_path, "fetch", "origin"));

        $process = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();
        $process
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue($revision));

        $this->utils
            ->expects($this->at($offset++))
            ->method('exec')
            ->with(array($this->binary_path, "rev-parse", "origin/{$this->branch}"))
            ->will($this->returnValue($process));

        $this->utils
            ->expects($this->at($offset++))
            ->method('exec')
            ->with(array($this->binary_path, "checkout", "-f", $revision))
            ->will($this->returnValue($process));

        $this->gitDeploy->setEnableSubmodules($enable_submodules);

        if ($this->gitDeploy->getEnableSubmodules()) {
            $this->utils
                ->expects($this->at($offset++))
                ->method('exec')
                ->with(array($this->binary_path, "submodule", "update", "--init"));
        }
    }

    /**
     * @depends testOnPreInit
     * @param InitEvent $event
     */
    public function testOnInit(InitEvent $event)
    {
        for ($i = 0; $i < 2; $i++) {
            $this->utils
                ->expects($this->at(0))
                ->method('exec')
                ->with(array($this->binary_path, "clone", "--recursive", $this->repository, $event->getSourceDir()));

            $this->checkoutBranch("last_commit_hash", 1, $i);

            $this->gitDeploy->onInitEvent($event);
        }
    }

    public function testOnPreDeployEvent()
    {
        $structure = array($this->cached_copy_dir => array());

        vfsStream::create($structure);

        $event = new DeployEvent();

        $this->gitDeploy->onPreDeployEvent($event);

        $this->assertEquals($this->getPath("/{$this->cached_copy_dir}"), $event->getSourceDir());

        return $event;
    }

    /**
     * @depends testOnPreDeployEvent
     */
    public function testOnDeployEvent(DeployEvent $event)
    {
        $new_release = "new_release";
        $current_release = "current_release";

        $structure = array('releases' => array(
            $current_release => array(),
            $new_release => array()
        ));

        vfsStream::create($structure);

        $event->setTargetDir($this->getPath("/releases/{$new_release}"));

        $revision = "last_commit_hash";

        $this->checkoutBranch($revision, 0);

        $this->utils
            ->expects($this->at(3))
            ->method('exec')
            ->with(array($this->binary_path,  "checkout-index", "-a", "-f", "--prefix={$event->getTargetDir()}/"));

        $this->gitDeploy->onDeployEvent($event);

        $rev = file_get_contents($event->getTargetDir() . '/REVISION');
        $this->assertEquals($revision, $rev);
    }

}

class TestFileSystem extends Filesystem
{
    public function dumpFile($filename, $content, $mode = 0666)
    {
        file_put_contents($filename, $content);
    }
}
