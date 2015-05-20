<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Model;

use OroCRM\Bundle\DotmailerBundle\Tests\Functional\AbstractImportTest;

class ExportManagerTest extends AbstractImportTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData'
            ]
        );
    }

    public function updateExportResultsTest()
    {

    }
}
