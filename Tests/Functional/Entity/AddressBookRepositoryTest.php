<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Entity;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 * @dbReindex
 */
class AddressBookRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData',
            ]
        );
    }

    public function testGetAddressBooksToSyncOriginIds()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository('OroCRMDotmailerBundle:AddressBook');
        $this->assertInstanceOf('OroCRM\Bundle\DotmailerBundle\Entity\Repository\AddressBookRepository', $repository);

        $channel = $this->getReference('orocrm_dotmailer.channel.third');
        $actual = $repository->getAddressBooksToSyncOriginIds($channel);
        $expected = [['originId' => 25], ['originId' => 35]];

        $this->assertEquals($expected, $actual);
    }
}
