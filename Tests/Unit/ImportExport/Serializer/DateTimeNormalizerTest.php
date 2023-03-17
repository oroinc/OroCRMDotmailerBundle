<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\DotmailerBundle\ImportExport\Serializer\DateTimeNormalizer;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Symfony\Component\Serializer\Exception\RuntimeException;

class DateTimeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private DateTimeNormalizer $normalizer;

    protected function setUp(): void
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
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'stdClass'));
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'DateTime'));
        $this->assertFalse($this->normalizer->supportsDenormalization('2013-12-31', 'DateTime'));
        $this->assertTrue($this->normalizer->supportsDenormalization('2013-12-31', 'DateTime', null, $context));
    }

    public function testNormalize()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Do not support normalization.');

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

    public function testDenormalizeException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid datetime "qwerty".');

        $this->normalizer->denormalize('qwerty', 'DateTime', null);
    }
}
