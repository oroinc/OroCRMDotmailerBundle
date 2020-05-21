<?php
namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Async;

use Oro\Bundle\DotmailerBundle\Async\ExportContactsStatusUpdateProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ExportContactsStatusUpdateProcessorTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $processor = self::getContainer()->get('oro_dotmailer.async.export_contacts_status_update_processor');

        self::assertInstanceOf(ExportContactsStatusUpdateProcessor::class, $processor);
    }
}
