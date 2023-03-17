<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldFormSubscriber;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\EnumValueStub;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

class DataFieldFormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private DataFieldFormSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new DataFieldFormSubscriber();
    }

    public function testPreSetWithEmptyData()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        $this->assertEquals('preSet', $events[FormEvents::PRE_SET_DATA]);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->never())
            ->method('add');
        $form->expects($this->never())
            ->method('remove');

        $event = new FormEvent($form, null);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithDateFieldType()
    {
        $form = $this->createMock(FormInterface::class);
        $this->addAssertForDateType($form);
        $field = new DataFieldStub();
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_DATE));
        $event = new FormEvent($form, $field);
        $this->subscriber->preSet($event);
    }

    public function testPreSetWithBooleanFieldType()
    {
        $form = $this->createMock(FormInterface::class);
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
        $this->assertEquals('preSubmit', $events[FormEvents::PRE_SUBMIT]);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->never())
            ->method('add');
        $form->expects($this->never())
            ->method('remove');

        $event = new FormEvent($form, []);
        $this->subscriber->preSubmit($event);
    }

    public function testPreSubmitWithDateFieldType()
    {
        $form = $this->createMock(FormInterface::class);
        $this->addAssertForDateType($form);
        $data = [];
        $data['type'] = DataFieldStub::FIELD_TYPE_DATE;
        $event = new FormEvent($form, $data);
        $this->subscriber->preSubmit($event);
    }

    public function testPreSubmitWithBooleanFieldType()
    {
        $form = $this->createMock(FormInterface::class);
        $this->addAssertForBooleanType($form);
        $data = [];
        $data['type'] = DataFieldStub::FIELD_TYPE_BOOLEAN;
        $event = new FormEvent($form, $data);
        $this->subscriber->preSubmit($event);
    }

    private function addAssertForDateType(FormInterface|\PHPUnit\Framework\MockObject\MockObject $form): void
    {
        $form->expects($this->once())
            ->method('add')
            ->with(
                'defaultValue',
                OroDateTimeType::class,
                [
                    'label' => 'oro.dotmailer.datafield.default_value.label',
                    'required' => false
                ]
            );
        $form->expects($this->once())
            ->method('remove')
            ->with('defaultValue');
    }

    private function addAssertForBooleanType(FormInterface|\PHPUnit\Framework\MockObject\MockObject $form): void
    {
        $form->expects($this->once())
            ->method('add')
            ->with(
                'defaultValue',
                ChoiceType::class,
                [
                    'label' => 'oro.dotmailer.datafield.default_value.label',
                    'required' => false,
                    'choices' => [
                        'Yes' => 'Yes',
                        'No' => 'No'
                    ]
                ]
            );
        $form->expects($this->once())
            ->method('remove')
            ->with('defaultValue');
    }
}
