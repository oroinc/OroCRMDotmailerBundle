<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class MarketingListConnectionType  extends AbstractType
{
    const NAME = 'orocrm_dotmailer_marketing_list_connection';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'channel',
                'orocrm_dotmailer_integration_select',
                [
                    'label' => 'orocrm.dotmailer.emailcampaign.integration.label',
                    'required' => true
                ]
            )
            ->add(
                'addressBook',
                'orocrm_dotmailer_address_book_list_select',
                [
                    'label' => 'orocrm.dotmailer.subscriberslist.entity_label',
                    'required' => true,
                    'channel_field' => 'channel',
                    'constraints' => [new NotBlank()]
                ]
            );

        $marketingList = $options['marketingList'];
        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $formEvent) use($marketingList){
            $originalData = $formEvent->getData();
            if (!is_array($originalData) ||  empty($originalData['addressBook'])) {
                return;
            }
            /** @var AddressBook $addressBook */
            $addressBook = $originalData['addressBook'];
            $addressBook->setMarketingList($marketingList);
            $formEvent->getForm()
                ->setData($addressBook);
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['marketingList']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
