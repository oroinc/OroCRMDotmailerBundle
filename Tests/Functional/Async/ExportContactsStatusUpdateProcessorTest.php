<?php
namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Async;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\DotmailerBundle\Async\ExportContactsStatusUpdateProcessor;

/**
 * @dbIsolationPerTest
 */
class ExportContactsStatusUpdateProcessorTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $processor = self::getContainer()->get('orocrm_dotmailer.async.export_contacts_status_update_processor');

        self::assertInstanceOf(ExportContactsStatusUpdateProcessor::class, $processor);
    }
}
