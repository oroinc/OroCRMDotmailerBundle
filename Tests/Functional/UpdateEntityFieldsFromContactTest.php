<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactList;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldMappingData;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData;

class UpdateEntityFieldsFromContactTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadDotmailerContactData::class, LoadDataFieldMappingData::class]);
    }

    public function testImport()
    {
        $entity = new ApiContactList();
        //update contact
        $entity[] = [
                'id' => 200,
                'email' => 'john.doe@example.com',
                'datafields' => [
                    [
                        'key'   => 'FIRSTNAME',
                        'value' => ['John Changed']
                    ],
                    [
                        'key'   => 'LASTNAME',
                        'value' => ['Doe Changed']
                    ],
                    [
                        'key'   => 'FULLNAME',
                        'value' => ['fullname']
                    ],
                    [
                        'key'   => 'GENDER',
                        'value' => ['male']
                    ],
                    [
                        'key'   => 'LASTSUBSCRIBED',
                        'value' => ['2015-01-01T00:00:00z']
                    ],
                ]
            ];
        //create contact
        $entity[] = [
                'id' => 400,
                'email' => 'new.doe@example.com',
                'datafields' => [
                    [
                        'key'   => 'FIRSTNAME',
                        'value' => ['New John']
                    ],
                    [
                        'key'   => 'LASTNAME',
                        'value' => ['New Doe']
                    ],
                    [
                        'key'   => 'FULLNAME',
                        'value' => ['fullname']
                    ],
                    [
                        'key'   => 'GENDER',
                        'value' => ['male']
                    ],
                    [
                        'key'   => 'LASTSUBSCRIBED',
                        'value' => ['2015-01-01T00:00:00z']
                    ],
                ]
            ];

        $this->resource->expects($this->any())
            ->method('GetAddressBookContacts')
            ->willReturn($entity);

        $channel = $this->getReference('oro_dotmailer.channel.first');
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            ContactConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $repository = $this->managerRegistry->getRepository(Contact::class);
        $contact = $this->getReference('oro_dotmailer.orocrm_contact.john.doe');
        $updatedContact = $repository->find($contact->getId());
        //firstname left unchanged
        $this->assertEquals('John', $updatedContact->getFirstName());
        //lastname was changed based on mapping
        $this->assertEquals('Doe Changed', $updatedContact->getLastName());

        $createdContacts = $repository->findBy(['lastName' => 'New Doe']);
        $this->assertCount(1, $createdContacts);
        $createdContact = reset($createdContacts);
        //firstname left unchanged
        $this->assertEquals('new.doe@example.com', $createdContact->getPrimaryEmail());
    }
}
