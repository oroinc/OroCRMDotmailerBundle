<?php
namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Command\FieldsForceSyncCommand;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class FieldsForceSyncCommandTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, FieldsForceSyncCommand::class);
    }

    public function testShouldImplementContainerAwareInterface()
    {
        $this->assertClassImplements(ContainerAwareInterface::class, FieldsForceSyncCommand::class);
    }

    public function testShouldImplementCronCommandInterface()
    {
        $this->assertClassImplements(CronCommandInterface::class, FieldsForceSyncCommand::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new FieldsForceSyncCommand();
    }

    public function testShouldBeRunDaily()
    {
        $command = new FieldsForceSyncCommand();

        self::assertEquals('0 1 * * *', $command->getDefaultDefinition());
    }

    public function testShouldAllowSetContainer()
    {
        $container = new Container();

        $command = new FieldsForceSyncCommand();

        $command->setContainer($container);

        $this->assertAttributeSame($container, 'container', $command);
    }
}
