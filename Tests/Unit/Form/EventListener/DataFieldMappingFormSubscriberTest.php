<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldMappingFormSubscriber;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DataFieldMappingFormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DataFieldMappingFormSubscriber
     */
    protected $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new DataFieldMappingFormSubscriber();
    }

    public function testPostSetWithEmtpyData()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::POST_SET_DATA, $events);
        $this->assertEquals($events[FormEvents::POST_SET_DATA], 'postSet');
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->never())->method('get');
        $event = new FormEvent($form, null);

        $this->subscriber->postSet($event);
    }

    public function testPostSetWithMappingData()
    {
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
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
        ]);
        $configSourceForm = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $configSourceForm->expects($this->once())->method('setData')->with($expected);
        $form->expects($this->once())->method('get')->with('config_source')
            ->will($this->returnValue($configSourceForm));
        $event = new FormEvent($form, $mapping);

        $this->subscriber->postSet($event);
    }

    public function testPreSubmitWithEmtpyData()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $events);
        $this->assertEquals($events[FormEvents::PRE_SUBMIT], 'preSubmit');
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $event = new FormEvent($form, null);

        $this->subscriber->preSubmit($event);
        $this->assertNull($event->getData());
    }

    public function testPreSubmitForExistingMapping()
    {
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
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
        ]);
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
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
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
        ]);
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
        $form->expects($this->once())->method('getData')->will($this->returnValue($mapping));
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
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
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
        ]);
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
        $form->expects($this->once())->method('getData')->will($this->returnValue($mapping));
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
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
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
        ]);
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
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
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
        ]);
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

    /**
     * @param $data
     * @return DataFieldMappingConfig
     */
    protected function getMappingConfigEntityMock($data)
    {
        return $this->getEntity(
            'Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig',
            [
                'id' => $data['id'],
                'entityFields' => $data['entityFields'],
                'dataField' => $this->getEntity(
                    'Oro\Bundle\DotmailerBundle\Entity\DataField',
                    [
                        'id' => $data['dataFieldId'],
                        'name' => isset($data['dataFieldName']) ? $data['dataFieldName'] : ''
                    ]
                ),
                'isTwoWaySync' => $data['isTwoWaySync']
            ]
        );
    }
}
