<?php

namespace Deploy\Core;

include dirname(__FILE__) . '/../../bootstrap.php';

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\RollbackEvent;
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
    private $coreDeploy;

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

    private $logger_file;

    private $history;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->deploy_path = 'deploy_dir';
        $this->root = vfsStream::setup($this->deploy_path);

        $this->history = 5;
        $this->logger_file = "deploy.log";

        $this->coreDeploy = new Deploy($this->history, $this->logger_file);

        $this->coreDeploy->setDeployPath(vfsStream::url($this->deploy_path));

        $this->logger = $this->getMock('Monolog\Logger', array(), array("deploy"));
        $this->coreDeploy->setLogger($this->logger);

        $this->utils = $this->getMock('Deploy\Core\Utils', array('exec', 'getTimestamp', 'symlink', 'remove', 'copy'), array($this->logger));
        $this->coreDeploy->setUtils($this->utils);

        $filesystem = new TestFileSystem();
        $this->utils->setFilesystem($filesystem);
    }

    private function getPath($resource)
    {
        return vfsStream::url($this->deploy_path . $resource);
    }

    public function testOnPreDeployEventWithoutCurrentRelease()
    {
        $timestamp = "current_timestamp";

        $this->utils
            ->expects($this->once())
            ->method('getTimestamp')
            ->will($this->returnValue($timestamp));

        $process = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue("no such file or directory"));

        $this->utils
            ->expects($this->once())
            ->method('exec')
            ->with(array('ls','-l', $this->getPath('')))
            ->will($this->returnValue($process));

        $event = new DeployEvent();

        $this->coreDeploy->onPreDeployEvent($event);

        $this->assertEquals($this->getPath("/releases/{$timestamp}"), $event->getTargetDir());
        $this->assertNull($event->getCurrentDir());
        $this->assertEquals($timestamp, $event->getTimestamp());
    }

    public function testOnPreDeployEventWithCurrentRelease()
    {
        $timestamp = "new_release";
        $current_release = "current_release";

        $structure = array('releases' => array(
            $current_release => array(),
            $timestamp => array()
        ));

        vfsStream::create($structure);

        $this->utils
            ->expects($this->once())
            ->method('getTimestamp')
            ->will($this->returnValue($timestamp));

        $process = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue("
lrwxr-xr-x   1 pavel  staff   23 Nov 13 18:00 current -> releases/{$current_release}
drwxr-xr-x   2 pavel  staff   68 Nov 11 09:48 logs
drwxr-xr-x  20 pavel  staff  680 Nov 13 18:00 releases"));

        $this->utils
            ->expects($this->once())
            ->method('exec')
            ->with(array('ls','-l', $this->getPath('')))
            ->will($this->returnValue($process));

        $event = new DeployEvent();

        $this->coreDeploy->onPreDeployEvent($event);

        $this->assertEquals($this->getPath("/releases/{$timestamp}"), $event->getTargetDir());
        $this->assertEquals($this->getPath("/releases/{$current_release}"), $event->getCurrentDir());
        $this->assertEquals($timestamp, $event->getTimestamp());

        return $event;
    }

    /**
     * @depends testOnPreDeployEventWithCurrentRelease
     */
    public function testOnPostDeployEvent(DeployEvent $event)
    {
        $timestamp = "new_release";
        $current_release = "current_release";

        $structure = array('releases' => array(
            $current_release => array(),
            $timestamp => array()
        ));

        vfsStream::create($structure);

        $process2 = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process2
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue("20140214112505  20140214104716  20140214074203  20140214065145  20140213044608
20140214110742  20140214080153  20140214071741  20140213051719"));

        $this->utils
            ->expects($this->once())
            ->method('exec')
            ->will($this->returnValue($process2));

        $this->utils
            ->expects($this->exactly(5))
            ->method('remove')
            ->with($this->logicalOr(
                $this->equalTo($this->getPath('/current')),
                $this->equalTo($this->getPath("/releases/20140213044608")),
                $this->equalTo($this->getPath("/releases/20140214110742")),
                $this->equalTo($this->getPath("/releases/20140214080153")),
                $this->equalTo($this->getPath("/releases/20140214071741")),
                $this->equalTo($this->getPath("/releases/20140213051719"))
            ))
            ->will($this->returnValue(true));

        $this->utils
            ->expects($this->once())
            ->method('symlink')
            ->with(
                $this->equalTo($event->getTargetDir()),
                $this->equalTo($this->getPath('/current'))
            );

        $this->utils
            ->expects($this->once())
            ->method('copy')
            ->with(
                $this->equalTo($this->getPath("/{$this->logger_file}")),
                $this->equalTo($this->getPath("/logs/{$event->getTimestamp()}.{$this->logger_file}"))
            );

        $this->coreDeploy->onPostDeployEvent($event);
    }

    public function testOnPreRollbackEvent()
    {
        $previous_release = "previous_release";
        $current_release = "current_release";

        $structure = array('releases' => array(
            $previous_release => array(),
            $current_release => array(),
        ));

        vfsStream::create($structure);

        $process1 = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process1
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue("current_release    previous_release"));

        $process2 = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process2
            ->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue("
lrwxr-xr-x   1 pavel  staff   23 Nov 13 18:00 current -> releases/{$current_release}
drwxr-xr-x   2 pavel  staff   68 Nov 11 09:48 logs
drwxr-xr-x  20 pavel  staff  680 Nov 13 18:00 releases"));

        $this->utils
            ->expects($this->exactly(2))
            ->method('exec')
            ->will($this->onConsecutiveCalls(
                $this->returnValue($process1),
                $this->returnValue($process2)
            ));

        $event = new RollbackEvent();

        $this->coreDeploy->onPreRollbackEvent($event);

        $this->assertEquals($this->getPath("/releases/{$previous_release}"), $event->getTargetDir());
        $this->assertEquals($this->getPath("/releases/{$current_release}"), $event->getCurrentDir());

        return $event;
    }

    /**
     * @depends testOnPreRollbackEvent
     */
    public function testOnPostRollbackEvent(RollbackEvent $event)
    {
        $previous_release = "previous_release";
        $current_release = "current_release";
        $timestamp = "timestamp";

        $structure = array('releases' => array(
            $previous_release => array(),
            $current_release => array()
        ));

        vfsStream::create($structure);

        $this->utils
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($this->getPath('/current')));

        $this->utils
            ->expects($this->once())
            ->method('symlink')
            ->with(
                $this->equalTo($event->getTargetDir()),
                $this->equalTo($this->getPath('/current'))
            );

        $this->utils
            ->expects($this->once())
            ->method('copy')
            ->with(
                $this->equalTo($this->getPath("/{$this->logger_file}")),
                $this->equalTo($this->getPath("/logs/{$timestamp}.rollback.log"))
            );

        $this->utils
            ->expects($this->once())
            ->method("getTimestamp")
            ->will($this->returnValue($timestamp));

        $this->coreDeploy->onPostRollbackEvent($event);
    }

}

class TestFileSystem extends Filesystem
{
    public function dumpFile($filename, $content, $mode = 0666)
    {
        file_put_contents($filename, $content);
    }
}
