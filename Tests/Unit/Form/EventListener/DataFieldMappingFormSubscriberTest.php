<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldMappingFormSubscriber;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

class DataFieldMappingFormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private DataFieldMappingFormSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new DataFieldMappingFormSubscriber();
    }

    public function testPostSetWithEmtpyData()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::POST_SET_DATA, $events);
        $this->assertEquals('postSet', $events[FormEvents::POST_SET_DATA]);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->never())
            ->method('get');
        $event = new FormEvent($form, null);

        $this->subscriber->postSet($event);
    }

    public function testPostSetWithMappingData()
    {
        $form = $this->createMock(FormInterface::class);
        $mapping = new DataFieldMapping();
        $config = $this->getMappingConfigEntityMock(
            [
                'id' => 1,
                'entityFields' => 'field',
                'dataFieldId' => 1,
                'dataFieldName' => 'dataFieldName',
                'isTwoWaySync' => true
            ]
        );
        $mapping->addConfig($config);
        $expected = json_encode([
            'mapping' => [
                [
                    'id' => 1,
                    'entityFields' => 'field',
                    'dataField' => [
                        'value' => 1,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => true
                ]
            ]
        ], JSON_THROW_ON_ERROR);
        $configSourceForm = $this->createMock(FormInterface::class);
        $configSourceForm->expects($this->once())
            ->method('setData')
            ->with($expected);
        $form->expects($this->once())
            ->method('get')
            ->with('config_source')
            ->willReturn($configSourceForm);
        $event = new FormEvent($form, $mapping);

        $this->subscriber->postSet($event);
    }

    public function testPreSubmitWithEmtpyData()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $events);
        $this->assertEquals('preSubmit', $events[FormEvents::PRE_SUBMIT]);
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, null);

        $this->subscriber->preSubmit($event);
        $this->assertNull($event->getData());
    }

    public function testPreSubmitForExistingMapping()
    {
        $form = $this->createMock(FormInterface::class);
        $data = [];
        $data['config_source'] = json_encode([
            'mapping' => [
                [
                    'id' => 1,
                    'entityFields' => 'field',
                    'dataField' => [
                        'value' => 1,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 1
                ],
                [
                    'id' => 2,
                    'entityFields' => 'anotherField',
                    'dataField' => [
                        'value' => 2,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 0
                ],
                [
                    'entityFields' => 'anotherNewField',
                    'dataField' => [
                        'value' => 3,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 0
                ]
            ]
        ], JSON_THROW_ON_ERROR);
        $event = new FormEvent($form, $data);

        $this->subscriber->preSubmit($event);
        $expected = [
            [
                'entityFields' => 'field',
                'dataField' => 1,
                'isTwoWaySync' => 1
            ],
            [
                'entityFields' => 'anotherField',
                'dataField' => 2
            ],
            [
                'entityFields' => 'anotherNewField',
                'dataField' => 3
            ]
        ];
        $eventData = $event->getData();
        $this->assertArrayHasKey('configs', $eventData);
        $this->assertEquals($expected, $eventData['configs']);
    }

    public function testPreSubmitForExistingMappingWithConfigRemoved()
    {
        $form = $this->createMock(FormInterface::class);
        $data = [];
        $data['config_source'] = json_encode([
            'mapping' => [
                [
                    'id' => 1,
                    'entityFields' => 'field',
                    'dataField' => [
                        'value' => 1,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 1
                ],
                [
                    'entityFields' => 'anotherNewField',
                    'dataField' => [
                        'value' => 3,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 0
                ]
            ]
        ], JSON_THROW_ON_ERROR);
        $mapping = new DataFieldMapping();
        $mapping->addConfig($this->getMappingConfigEntityMock(
            [
                'id' => 1,
                'entityFields' => 'field',
                'dataFieldId' => 1,
                'isTwoWaySync' => true
            ]
        ));
        $mapping->addConfig($this->getMappingConfigEntityMock(
            [
                'id' => 2,
                'entityFields' => 'anotherField',
                'dataFieldId' => 2,
                'isTwoWaySync' => false
            ]
        ));
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($mapping);
        $event = new FormEvent($form, $data);

        $this->subscriber->preSubmit($event);
        $expected = [
            0 => [
                'entityFields' => 'field',
                'dataField' => 1,
                'isTwoWaySync' => 1
            ],
            1 => [
                'entityFields' => 'anotherNewField',
                'dataField' => 3
            ]
        ];
        $eventData = $event->getData();
        $this->assertArrayHasKey('configs', $eventData);
        $this->assertEquals($expected, $eventData['configs']);
    }

    public function testPreSubmitForExistingMappingWithTheSameDataFieldRemovedAndAdded()
    {
        $form = $this->createMock(FormInterface::class);
        $data = [];
        $data['config_source'] = json_encode([
            'mapping' => [
                [
                    'id' => 1,
                    'entityFields' => 'field',
                    'dataField' => [
                        'value' => 1,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 1
                ],
                [
                    'entityFields' => 'anotherNewField',
                    'dataField' => [
                        'value' => 2,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 0
                ]
            ]
        ], JSON_THROW_ON_ERROR);
        $mapping = new DataFieldMapping();
        $mapping->addConfig($this->getMappingConfigEntityMock(
            [
                'id' => 1,
                'entityFields' => 'field',
                'dataFieldId' => 1,
                'isTwoWaySync' => true
            ]
        ));
        $mapping->addConfig($this->getMappingConfigEntityMock(
            [
                'id' => 2,
                'entityFields' => 'anotherField',
                'dataFieldId' => 2,
                'isTwoWaySync' => false
            ]
        ));
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($mapping);
        $event = new FormEvent($form, $data);

        $this->subscriber->preSubmit($event);
        $expected = [
            0 => [
                'entityFields' => 'field',
                'dataField' => 1,
                'isTwoWaySync' => 1
            ],
            1 => [
                'entityFields' => 'anotherNewField',
                'dataField' => 2
            ]
        ];
        $eventData = $event->getData();
        $this->assertArrayHasKey('configs', $eventData);
        $this->assertEquals($expected, $eventData['configs']);
    }

    public function testPreSubmitTwoWaySyncUnsetWithSeveralEntityFields()
    {
        $form = $this->createMock(FormInterface::class);
        $data = [];
        $data['config_source'] =  json_encode([
            'mapping' => [
                [
                    'id' => 1,
                    'entityFields' => 'field,anotherField',
                    'dataField' => [
                        'value' => 1,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 1
                ]
            ]
        ], JSON_THROW_ON_ERROR);
        $event = new FormEvent($form, $data);

        $this->subscriber->preSubmit($event);
        $expected = [
            [
                'entityFields' => 'field,anotherField',
                'dataField' => 1
            ],
        ];
        $eventData = $event->getData();
        $this->assertArrayHasKey('configs', $eventData);
        $this->assertEquals($expected, $eventData['configs']);
    }

    public function testPreSubmitTwoWaySyncUnsetWithRelationEntityFields()
    {
        $form = $this->createMock(FormInterface::class);
        $data = [];
        $data['config_source'] =  json_encode([
            'mapping' => [
                [
                    'id' => 1,
                    'entityFields' => 'relation+relationField',
                    'dataField' => [
                        'value' => 1,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 1
                ]
            ]
        ], JSON_THROW_ON_ERROR);
        $event = new FormEvent($form, $data);

        $this->subscriber->preSubmit($event);
        $expected = [
            [
                'entityFields' => 'relation+relationField',
                'dataField' => 1
            ],
        ];
        $eventData = $event->getData();
        $this->assertArrayHasKey('configs', $eventData);
        $this->assertEquals($expected, $eventData['configs']);
    }

    private function getMappingConfigEntityMock(array $data): DataFieldMappingConfig
    {
        $field = new DataField();
        ReflectionUtil::setId($field, $data['dataFieldId']);
        $field->setName($data['dataFieldName'] ?? '');

        $fieldMappingConfig = new DataFieldMappingConfig();
        ReflectionUtil::setId($fieldMappingConfig, $data['id']);
        $fieldMappingConfig->setEntityFields($data['entityFields']);
        $fieldMappingConfig->setDataField($field);
        $fieldMappingConfig->setIsTwoWaySync($data['isTwoWaySync']);

        return $fieldMappingConfig;
    }
}
