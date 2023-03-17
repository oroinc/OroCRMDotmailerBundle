<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Oro\Bundle\DotmailerBundle\Provider\EmailProvider;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\MarketingListBundle\Model\ContactInformationFieldHelper;

class EmailProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContactInformationFieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $contactInformationFieldHelper;

    /** @var VirtualFieldProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $virtualFieldProvider;

    /** @var EmailProvider */
    private $emailProvider;

    protected function setUp(): void
    {
        $this->contactInformationFieldHelper = $this->createMock(ContactInformationFieldHelper::class);
        $this->virtualFieldProvider = $this->createMock(VirtualFieldProviderInterface::class);

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
        $this->contactInformationFieldHelper->expects($this->once())
            ->method('getEntityContactInformationFieldsInfo')
            ->willReturn($contactInfoFields);
        $this->virtualFieldProvider->expects($this->once())
            ->method('isVirtualField')
            ->with('entityClass', 'email')
            ->willReturn(false);

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
        $this->contactInformationFieldHelper->expects($this->once())
            ->method('getEntityContactInformationFieldsInfo')
            ->willReturn($contactInfoFields);
        $this->virtualFieldProvider->expects($this->once())
            ->method('isVirtualField')
            ->with('entityClass', 'primaryEmail')
            ->willReturn(true);
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
        $this->virtualFieldProvider->expects($this->once())
            ->method('getVirtualFieldQuery')
            ->with('entityClass', 'primaryEmail')
            ->willReturn($fieldConfig);

        $result = $this->emailProvider->getEntityEmailField('entityClass');
        $expected = [
            'entityEmailField' => 'emails',
            'emailField' => 'email'
        ];
        $this->assertEquals($expected, $result);
    }
}
