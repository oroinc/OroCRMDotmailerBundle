<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Oro\Bundle\DotmailerBundle\Provider\EmailProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class EmailProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $contactInformationFieldHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $virtualFieldProvider;

    /**
     * @var EmailProvider
     */
    protected $emailProvider;

    protected function setUp(): void
    {
        $this->contactInformationFieldHelper = $this
            ->getMockBuilder('Oro\Bundle\MarketingListBundle\Model\ContactInformationFieldHelper')
            ->disableOriginalConstructor()->getMock();
        $this->virtualFieldProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface')
            ->getMock();

        $this->emailProvider = new EmailProvider(
            $this->contactInformationFieldHelper,
            $this->virtualFieldProvider
        );
    }

    public function testEntityEmailFieldFlat()
    {
        $contactInfoFields = [
            [
                'name' => 'email',
                'type' => 'string',
                'label' => 'Email',
                'contact_information_type' => 'email'
            ],
            [
                'name' => 'primaryPhone',
                'type' => 'string',
                'label' => 'Primary Phone',
                'contact_information_type' => 'phone'
            ]
        ];
        $this->contactInformationFieldHelper->expects($this->once())->method('getEntityContactInformationFieldsInfo')->
            will($this->returnValue($contactInfoFields));
        $this->virtualFieldProvider->expects($this->once())->method('isVirtualField')
            ->with('entityClass', 'email')
            ->will($this->returnValue(false));

        $result = $this->emailProvider->getEntityEmailField('entityClass');
        $this->assertEquals('email', $result);
    }

    public function testEntityEmailFieldVirtual()
    {
        $contactInfoFields = [
            [
                'name' => 'primaryEmail',
                'type' => 'string',
                'label' => 'Primary Email',
                'contact_information_type' => 'email'
            ],
            [
                'name' => 'primaryPhone',
                'type' => 'string',
                'label' => 'Primary Phone',
                'contact_information_type' => 'phone'
            ]
        ];
        $this->contactInformationFieldHelper->expects($this->once())->method('getEntityContactInformationFieldsInfo')->
            will($this->returnValue($contactInfoFields));
        $this->virtualFieldProvider->expects($this->once())->method('isVirtualField')
            ->with('entityClass', 'primaryEmail')
            ->will($this->returnValue(true));
        $fieldConfig = [
            'select' => [
                'expr' => 'emails.email',
                'return_type' => 'string'
            ],
            'join' => [
                'left' => [
                    [
                        'join' => 'entity.emails',
                        'alias' => 'emails',
                        'conditionType' => 'WITH',
                        'condition' => 'emails.primary = true'
                    ]
                ]
            ]
        ];
        $this->virtualFieldProvider->expects($this->once())->method('getVirtualFieldQuery')
            ->with('entityClass', 'primaryEmail')
            ->will($this->returnValue($fieldConfig));

        $result = $this->emailProvider->getEntityEmailField('entityClass');
        $expected = [
            'entityEmailField' => 'emails',
            'emailField' => 'email'
        ];
        $this->assertEquals($expected, $result);
    }
}
