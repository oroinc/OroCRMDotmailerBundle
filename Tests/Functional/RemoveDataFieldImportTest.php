<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiDataField;
use DotMailer\Api\DataTypes\ApiDataFieldList;

use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;

/**
 * @dbIsolation
 */
class RemoveDataFieldImportTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldData'
            ]
        );
    }

    public function testImport()
    {
        $entity = new ApiDataFieldList();
        $expectedPresentedName = 'FIRSTNAME';
        $expectedRemovedName = 'LASTNAME';
        $entity[] = new ApiDataField(['Name' => $expectedPresentedName]);
        $this->resource->expects($this->any())
            ->method('GetDataFields')
            ->will($this->returnValue($entity));
        $channel = $this->getReference('oro_dotmailer.channel.first');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            DataFieldConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $dataField = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:DataField')
            ->findBy(['name' => $expectedPresentedName]);

        $this->assertCount(1, $dataField, 'Data field must exist');

        $addressBook = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:DataField')
            ->findBy(['name' => $expectedRemovedName]);

        $this->assertCount(0, $addressBook, 'Data field must be removed');
    }
}
