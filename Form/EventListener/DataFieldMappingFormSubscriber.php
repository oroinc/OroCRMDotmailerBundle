<?php

namespace Oro\Bundle\DotmailerBundle\Form\EventListener;

use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adjusts data mapping for two-way sync.
 */
class DataFieldMappingFormSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSet',
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    /**
     * Collect mapping data and update mapping config source element
     */
    public function postSet(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data === null) {
            return;
        }

        /** @var $data DataFieldMapping */
        if ($data->getConfigs()) {
            $configs = $data->getConfigs();
            $mappings = [];
            foreach ($configs as $config) {
                $mapping = [];
                $mapping['id'] = $config->getId();
                $mapping['entityFields'] = $config->getEntityFields();
                $mapping['dataField'] = [
                    'value' => $config->getDataField()->getId(),
                    'name' => $config->getDataField()->getName()
                ];
                $mapping['isTwoWaySync'] = $config->isIsTwoWaySync();
                $mappings[] = $mapping;
            }
            $mappings = ['mapping' => $mappings];
            $form->get('config_source')->setData(json_encode($mappings));
        }
    }

    /**
     * Process submitted mapping data and add to mapping collection form
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (!empty($data['config_source'])) {
            /** @var DataFieldMapping $mapping */
            $mapping = $event->getForm()->getData();
            $previousConfigIds = [];
            $previousConfigDatafieldIds = [];
            if ($mapping) {
                $previousConfigs = $mapping->getConfigs();
                foreach ($previousConfigs as $index => $config) {
                    $previousConfigIds[] = $config->getId();
                    $previousConfigDatafieldIds[] = $config->getDataField()->getId();
                }
            }
            $mappingConfigurations = json_decode($data['config_source'], true);
            if ($mappingConfigurations) {
                $index = -1;
                foreach ($mappingConfigurations['mapping'] as $mappingConfiguration) {
                    if (isset($mappingConfiguration['dataField']['value'])) {
                        $mappingConfiguration['dataField'] = (int) $mappingConfiguration['dataField']['value'];
                    }
                    $mappingConfiguration = $this->processTwoWaySync($mappingConfiguration);

                    if ($previousConfigIds && isset($mappingConfiguration['id'])) {
                        //first look up for position by existing mapping id
                        $index = array_search($mappingConfiguration['id'], $previousConfigIds, true);
                    } elseif ($previousConfigDatafieldIds && $mappingConfiguration['dataField']) {
                        /**
                         * look up for position by datafield id, in case row with datafield was removed and another row
                         * with the same datafield was added, to avoid unique constraint error
                         */
                        $index = array_search($mappingConfiguration['dataField'], $previousConfigDatafieldIds, true) ?:
                            $index + 1;
                    } else {
                        $index++;
                    }
                    unset($mappingConfiguration['id']);
                    $data['configs'][$index] = $mappingConfiguration;
                }
                $event->setData($data);
            }
        }
    }

    /**
     * Check if two way sync can be applied to the mapping, remove it from data if it can't
     *
     * @param array $mappingConfiguration
     *
     * @return array
     */
    protected function processTwoWaySync($mappingConfiguration)
    {
        $unset = false;
        if (!isset($mappingConfiguration['isTwoWaySync']) || !$mappingConfiguration['isTwoWaySync']) {
            $unset = true;
        }
        $entityFields = explode(',', $mappingConfiguration['entityFields']);
        //Two way sync should be disabled if we have more than 1 field chosen
        if (count($entityFields) > 1) {
            $unset = true;
        } else {
            $field = current($entityFields);
            //if relation field is used
            if (strrpos($field, '+') !== false) {
                $unset = true;
            }
        }
        if ($unset) {
            unset($mappingConfiguration['isTwoWaySync']);
        }

        return $mappingConfiguration;
    }
}
