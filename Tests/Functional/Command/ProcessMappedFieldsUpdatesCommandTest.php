<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadUpdatesLogData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProcessMappedFieldsUpdatesCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
    }

    public function testShouldOutputHelpForTheCommand()
    {
        $result = $this->runCommand('oro:cron:dotmailer:mapped-fields-updates:process', ['--help']);

        self::assertStringContainsString('Usage:', $result);
        self::assertStringContainsString('oro:cron:dotmailer:mapped-fields-updates:process', $result);
    }

    public function testRunCommand()
    {
        $this->loadFixtures([LoadUpdatesLogData::class]);
        $result = $this->runCommand('oro:cron:dotmailer:mapped-fields-updates:process');

        //check that entity update flag was properly set for ab contact after job run
        $managerRegistry = $this->getContainer()->get('doctrine');
        $entityUpdated = $managerRegistry->getRepository(AddressBookContact::class)
            ->findBy([
                'marketingListItemId' => $this->getReference('oro_dotmailer.orocrm_contact.john.doe')->getId(),
                'entityUpdated' => true
            ]);
        self::assertCount(1, $entityUpdated);
        self::assertStringContainsString('Start queue processing', $result);
        self::assertStringContainsString('Completed', $result);
    }
}
