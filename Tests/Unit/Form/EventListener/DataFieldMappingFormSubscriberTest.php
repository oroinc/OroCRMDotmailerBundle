<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldMappingFormSubscriber;

class DataFieldMappingFormSubscriberTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DataFieldMappingFormSubscriber
     */
    protected $subscriber;

    protected function setUp()
    {
        $this->subscriber = new DataFieldMappingFormSubscriber();
    }

    public function testPostSetWithEmtpyData()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::POST_SET_DATA, $events);
        $this->assertEquals($events[FormEvents::POST_SET_DATA], 'postSet');
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->never())->method('get');
        $event = new FormEvent($form, null);

        $this->subscriber->postSet($event);
    }

    public function testPostSetWithMappingData()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $mapping = new DataFieldMapping();
        $config = new DataFieldMappingConfig();
        $config->setEntityFields('field');
        $config->setDataField($this->getEntity(
            'Oro\Bundle\DotmailerBundle\Entity\DataField',
            [
                'id' => 1,
                'name' => 'dataFieldName'
            ]
        ));
        $config->setIsTwoWaySync(true);
        $mapping->addConfig($config);
        $expected = json_encode([
            'mapping' => [
                [
                    'entityFields' => 'field',
                    'dataField' => [
                        'value' => 1,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => true
                ]
            ]
        ]);
        $configSourceForm = $this->getMock('Symfony\Component\Form\Test\FormInterface');
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
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $event = new FormEvent($form, null);

        $this->subscriber->preSubmit($event);
        $this->assertNull($event->getData());
    }

    public function testPreSubmitWithMappingSource()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $data = [];
        $data['config_source'] = json_encode([
            'mapping' => [
                [
                    'entityFields' => 'field',
                    'dataField' => [
                        'value' => 1,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 1
                ],
                [
                    'entityFields' => 'anotherField',
                    'dataField' => [
                        'value' => 1,
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => 0
                ],
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
                'dataField' => 1
            ]
        ];
        $eventData = $event->getData();
        $this->assertArrayHasKey('configs', $eventData);
        $this->assertEquals($expected, $eventData['configs']);
    }

    public function testPreSubmitTwoWaySyncUnsetWithSeveralEntityFields()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $data = [];
        $data['config_source'] =  json_encode([
            'mapping' => [
                [
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
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $data = [];
        $data['config_source'] =  json_encode([
            'mapping' => [
                [
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
}
