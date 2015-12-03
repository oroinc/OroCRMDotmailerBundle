<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use OroCRM\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroCRM\Bundle\DotmailerBundle\Command\ContactsExportStatusUpdateCommand;

/**
 * @dbIsolation
 */
class ContactsExportStatusUpdateCommandTest extends WebTestCase
{
    /**
     * @var ExportManager
     */
    protected $exportManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $exportManagerMock;

    /**
     * @var ReverseSyncProcessor
     */
    protected $syncProcessor;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
            ]
        );

        $this->exportManager = $this->getContainer()
            ->get(ContactsExportStatusUpdateCommand::EXPORT_MANAGER);
        $this->exportManagerMock = $this->getMockBuilder('OroCRM\Bundle\DotmailerBundle\Model\ExportManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->getContainer()
            ->set(ContactsExportStatusUpdateCommand::EXPORT_MANAGER, $this->exportManagerMock);

        $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->beginTransaction();
    }

    protected function tearDown()
    {
        $this->getContainer()
            ->set(ContactsExportStatusUpdateCommand::EXPORT_MANAGER, $this->exportManager);

        // clear DB from separate connection, close to avoid connection limit and memory leak
        $manager = $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager();
        $manager->rollback();
        $manager->getConnection()->close();

        parent::tearDown();
    }

    /**
     * Test execute started jos with correct parameters, call Export Manager before start new export
     * and remove old export status records before start new one
     */
    public function testExecute()
    {
        $expectedChannel = $this->getReference('orocrm_dotmailer.channel.third');
        $secondExpectedChannel = $this->getReference('orocrm_dotmailer.channel.fourth');

        $this->exportManagerMock
            ->expects($this->any())
            ->method('isExportFinished')
            ->will(
                $this->returnValueMap(
                    [
                        [$this->getReference('orocrm_dotmailer.channel.first'), true],
                        [$this->getReference('orocrm_dotmailer.channel.second'), true],
                        [$expectedChannel, false],
                        [$secondExpectedChannel, false],
                    ]
                )
            );

        $this->exportManagerMock
            ->expects($this->exactly(2))
            ->method('updateExportResults')
            ->withConsecutive([$expectedChannel], [$secondExpectedChannel]);

        $this->runCommand(ContactsExportStatusUpdateCommand::NAME, ['--verbose' => true]);
    }
}
