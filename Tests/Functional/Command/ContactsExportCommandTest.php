<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\IntegrationBundle\Command\ReverseSyncCommand;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use OroCRM\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\DotmailerBundle\Command\ContactsExportCommand;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class ContactsExportCommandTest extends WebTestCase
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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $syncProcessorMock;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookContactsExportData',
            ]
        );

        $this->syncProcessorMock = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->exportManagerMock = $this->getMockBuilder('OroCRM\Bundle\DotmailerBundle\Model\ExportManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->syncProcessor = $this->getContainer()
            ->get(ReverseSyncCommand::SYNC_PROCESSOR);
        $this->exportManager = $this->getContainer()
            ->get(ContactsExportCommand::EXPORT_MANAGER);

        $this->getContainer()
            ->set(ContactsExportCommand::EXPORT_MANAGER, $this->exportManagerMock);
        $this->getContainer()
            ->set(ReverseSyncCommand::SYNC_PROCESSOR, $this->syncProcessorMock);
    }

    protected function tearDown()
    {
        $this->getContainer()
            ->set(ContactsExportCommand::EXPORT_MANAGER, $this->exportManager);
        $this->getContainer()
            ->set(ReverseSyncCommand::SYNC_PROCESSOR, $this->syncProcessor);

        $jobRepository = $this->getContainer()->get('akeneo_batch.job_repository');

        $reflection = new \ReflectionObject($jobRepository);
        $property = $reflection->getProperty('jobManager');
        $property->setAccessible(true);
        /** @var EntityManager $entityManager */
        $entityManager = $property->getValue($jobRepository);
        $entityManager
            ->getConnection()
            ->close();
        $entityManager->close();

        parent::tearDown();
    }

    /**
     * Test execute started jos with correct parameters, call Export Manager before start new export
     * and remove old export status records before start new one
     */
    public function testExecute()
    {
        $registry = $this->getContainer()->get('doctrine');
        $repository = $registry->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport');

        $this->exportManagerMock
            ->expects($this->any())
            ->method('isExportFinished')
            ->will(
                $this->returnValueMap(
                    [
                        [$this->getReference('orocrm_dotmailer.channel.first'), true],
                        [$this->getReference('orocrm_dotmailer.channel.second'), true],
                        [$this->getReference('orocrm_dotmailer.channel.third'), true],
                        [$this->getReference('orocrm_dotmailer.channel.fourth'), false],
                    ]
                )
            );

        $this->syncProcessorMock
            ->expects($this->at(0))
            ->method('process')
            ->with($this->getReference('orocrm_dotmailer.channel.first'), ContactConnector::TYPE);
        $this->syncProcessorMock
            ->expects($this->at(1))
            ->method('process')
            ->with($this->getReference('orocrm_dotmailer.channel.second'), ContactConnector::TYPE);
        $this->syncProcessorMock
            ->expects($this->at(2))
            ->method('process')
            ->with($this->getReference('orocrm_dotmailer.channel.third'), ContactConnector::TYPE);

        $this->exportManagerMock
            ->expects($this->exactly(4))
            ->method('updateExportResults');

        $result = $this->runCommand(ContactsExportCommand::NAME, ['--verbose' => true]);
        $result = trim($result);
        /**
         * Check no errors in output
         */
        $this->assertEquals(
            sprintf(
                'Previous export do not complete for Integration "%s", checking previous export state ...',
                $this->getReference('orocrm_dotmailer.channel.fourth')->getName()
            ),
            $result
        );

        /**
         * Check previous export record removed
         */
        $this->assertNull($repository->findOneBy(
            ['importId' => '2fb9cba7-e588-445a-8731-4796c86b1097']
        ));

        /**
         * Check actual records presented
         */
        $this->assertNotNull($repository->findOneBy(
            ['importId' => '1fb9cba7-e588-445a-8731-4796c86b1097']
        ));
    }
}
