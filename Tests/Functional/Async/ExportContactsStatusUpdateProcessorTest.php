<?php
namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Async;

use Oro\Bundle\DotmailerBundle\Async\ExportContactsStatusUpdateProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
