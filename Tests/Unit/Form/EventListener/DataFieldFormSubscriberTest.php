<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroCRM\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub;
use OroCRM\Bundle\DotmailerBundle\Tests\Unit\Stub\EnumValueStub;
use OroCRM\Bundle\DotmailerBundle\Form\EventListener\DataFieldFormSubscriber;

class DataFieldFormSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DataFieldFormSubscriber
     */
    protected $subscriber;

    protected function setUp()
    {
        $this->subscriber = new DataFieldFormSubscriber();
    }

    public function testPreSetWithEmtpyData()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        $this->assertEquals($events[FormEvents::PRE_SET_DATA], 'preSet');
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->never())->method('add');
        $form->expects($this->never())->method('remove');

        $event = new FormEvent($form, null);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithDateFieldType()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->addAssertForDateType($form);
        $field = new DataFieldStub();
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_DATE));
        $event = new FormEvent($form, $field);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithBooleanFieldType()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->addAssertForBooleanType($form);
        $field = new DataFieldStub();
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_BOOLEAN));
        $event = new FormEvent($form, $field);
        $this->subscriber->preSet($event);
    }

    public function testPreSubmitWithEmtpyData()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $events);
        $this->assertEquals($events[FormEvents::PRE_SUBMIT], 'preSubmit');
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->never())->method('add');
        $form->expects($this->never())->method('remove');

        $event = new FormEvent($form, []);
        $this->subscriber->preSubmit($event);
    }

    public function testPreSubmitWithDateFieldType()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->addAssertForDateType($form);
        $data = [];
        $data['type'] = DataFieldStub::FIELD_TYPE_DATE;
        $event = new FormEvent($form, $data);
        $this->subscriber->preSubmit($event);
    }

    public function testPreSubmitWithBooleanFieldType()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->addAssertForBooleanType($form);
        $data = [];
        $data['type'] = DataFieldStub::FIELD_TYPE_BOOLEAN;
        $event = new FormEvent($form, $data);
        $this->subscriber->preSubmit($event);
    }

    protected function addAssertForDateType($form)
    {
        $form->expects($this->once())->method('add')
            ->with(
                'defaultValue',
                'oro_date',
                [
                    'label' => 'orocrm.dotmailer.datafield.default_value.label',
                    'required' => false
                ]
            );
        $form->expects($this->once())->method('remove')->with('defaultValue');
    }

    protected function addAssertForBooleanType($form)
    {
        $form->expects($this->once())->method('add')
            ->with(
                'defaultValue',
                'choice',
                [
                    'label' => 'orocrm.dotmailer.datafield.default_value.label',
                    'required' => false,
                    'choices' => [
                        'Yes' => 'Yes',
                        'No' => 'No'
                    ]
                ]
            );
        $form->expects($this->once())->method('remove')->with('defaultValue');
    }
}
