<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\ImportExport\Serializer;

use OroCRM\Bundle\DotmailerBundle\ImportExport\Serializer\DateTimeNormalizer;
use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;

class DateTimeNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateTimeNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->normalizer = new DateTimeNormalizer();
    }

    public function testSupportsNormalization()
    {
        $this->assertFalse($this->normalizer->supportsNormalization([]));
    }

    public function testSupportsDenormalization()
    {
        $context = ['channelType' => ChannelType::TYPE];
        $this->assertFalse($this->normalizer->supportsDenormalization(array(), 'stdClass'));
        $this->assertFalse($this->normalizer->supportsDenormalization(array(), 'DateTime'));
        $this->assertFalse($this->normalizer->supportsDenormalization('2013-12-31', 'DateTime'));
        $this->assertTrue($this->normalizer->supportsDenormalization('2013-12-31', 'DateTime', null, $context));
    }

    /**
     * @expectedException \Symfony\Component\Serializer\Exception\RuntimeException
     * @expectedExceptionMessage Do not support normalization.
     */
    public function testNormalize()
    {
        $date = new \DateTime('2013-12-31 23:59:59+0200');
        $this->normalizer->normalize($date, null);
    }

    public function testDenormalize()
    {
        $this->assertEquals(
            new \DateTime('2013-12-31 23:59:59+0200'),
            $this->normalizer->denormalize(
                '2013-12-31T23:59:59+0200',
                'DateTime',
                null,
                ['channelType' => ChannelType::TYPE]
            )
        );
        $this->assertEquals(
            new \DateTime('2013-12-31 00:00:00'),
            $this->normalizer->denormalize('2013-12-31', 'DateTime', null, ['channelType' => ChannelType::TYPE])
        );
        $this->assertEquals(
            new \DateTime('2015-04-16T13:48:33.013Z'),
            $this->normalizer->denormalize(
                '2015-04-16T13:48:33.013Z',
                'DateTime',
                null,
                ['channelType' => ChannelType::TYPE]
            )
        );
        $this->assertNull($this->normalizer->denormalize(null, 'DateTime'));
    }

    /**
     * @expectedException \Symfony\Component\Serializer\Exception\RuntimeException
     * @expectedExceptionMessage Invalid datetime "qwerty".
     */
    public function testDenormalizeException()
    {
        $this->normalizer->denormalize('qwerty', 'DateTime', null);
    }
}
