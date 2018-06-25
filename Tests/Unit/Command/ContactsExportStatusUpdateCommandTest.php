<?php
namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Command\ContactsExportStatusUpdateCommand;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class ContactsExportStatusUpdateCommandTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, ContactsExportStatusUpdateCommand::class);
    }

    public function testShouldImplementContainerAwareInterface()
    {
        $this->assertClassImplements(ContainerAwareInterface::class, ContactsExportStatusUpdateCommand::class);
    }

    public function testShouldImplementCronCommandInterface()
    {
        $this->assertClassImplements(CronCommandInterface::class, ContactsExportStatusUpdateCommand::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new ContactsExportStatusUpdateCommand();
    }

    public function testShouldBeRunEveryFiveMinutes()
    {
        $command = new ContactsExportStatusUpdateCommand();

        self::assertEquals('*/5 * * * *', $command->getDefaultDefinition());
    }

    public function testShouldAllowSetContainer()
    {
        $container = new Container();

        $command = new ContactsExportStatusUpdateCommand();

        $command->setContainer($container);

        $this->assertAttributeSame($container, 'container', $command);
    }
}
