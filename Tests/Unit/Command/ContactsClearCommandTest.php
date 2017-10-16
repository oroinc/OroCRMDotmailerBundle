<?php
namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Command\ContactsClearCommand;
use Oro\Bundle\DotmailerBundle\Command\ContactsExportStatusUpdateCommand;
use Oro\Component\Testing\ClassExtensionTrait;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class ContactsClearCommandTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, ContactsClearCommand::class);
    }

    public function testShouldImplementContainerAwareInterface()
    {
        $this->assertClassImplements(ContainerAwareInterface::class, ContactsClearCommand::class);
    }

    public function testShouldImplementCronCommandInterface()
    {
        $this->assertClassImplements(CronCommandInterface::class, ContactsClearCommand::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new ContactsClearCommand();
    }

    public function testShouldBeRunEveryFiveMinutes()
    {
        $command = new ContactsClearCommand();
        self::assertEquals('0 1 * * *', $command->getDefaultDefinition());
    }

    public function testShouldAllowSetContainer()
    {
        $container = new Container();
        $command = new ContactsClearCommand();
        $command->setContainer($container);
        $this->assertAttributeSame($container, 'container', $command);
    }
}
