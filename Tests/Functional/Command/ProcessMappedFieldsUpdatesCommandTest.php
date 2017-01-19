<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProcessMappedFieldsUpdatesCommandTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testShouldOutputHelpForTheCommand()
    {
        $result = $this->runCommand('oro:cron:dotmailer:mapped-fields-updates:process', ['--help']);

        self::assertContains('Usage:', $result);
        self::assertContains('oro:cron:dotmailer:mapped-fields-updates:process', $result);
    }

    public function testRunCommand()
    {
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadUpdatesLogData',
            ]
        );
        $result = $this->runCommand('oro:cron:dotmailer:mapped-fields-updates:process');

        //check that entity update flag was properly set for ab contact after job run
        $managerRegistry = $this->getContainer()->get('doctrine');
        $entityUpdated = $managerRegistry->getRepository('OroDotmailerBundle:AddressBookContact')
            ->findBy(
                [
                    'marketingListItemId' => $this->getReference('oro_dotmailer.orocrm_contact.john.doe')->getId(),
                    'entityUpdated' => true
                ]
            );
        $this->assertCount(1, $entityUpdated);
     
        $this->assertContains('Start queue processing', $result);
        $this->assertContains('Completed', $result);
    }
}
