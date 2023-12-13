<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class FieldsForceSyncCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();
    }

    public function testShouldOutputHelpForTheCommand(): void
    {
        $result = self::runCommand('oro:cron:dotmailer:force-fields-sync', ['--help']);

        self::assertStringContainsString('Usage:', $result);
        self::assertStringContainsString('oro:cron:dotmailer:force-fields-sync', $result);
    }

    public function testRunCommand(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Channel::class);
        $channel = new Channel();
        $channel->setType(ChannelType::TYPE);
        $channel->setEnabled(true);
        $channel->setName('Test');
        $em->persist($channel);
        $em->flush();

        $result = self::runCommand('oro:cron:dotmailer:force-fields-sync');

        self::assertStringContainsString('Start update of address book contacts', $result);
        self::assertStringContainsString('Completed', $result);
    }

    public function testShouldNotBeExecutedWhenCommandIsNotActive(): void
    {
        $result = self::runCommand('oro:cron:dotmailer:force-fields-sync');

        self::assertStringContainsString('This CRON command is disabled.', $result);
    }
}
