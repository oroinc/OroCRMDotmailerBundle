<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event can be used to add additional fields for tracking. For example, if a virtual field is used in the mapping
 * and automatic force synchronization is not desirable.
 * Changes on these fields will trigger synchronization of related entity's data fields into dotmailer contact
 */
class MappingTrackedFieldsEvent extends Event
{
    const NAME = 'oro_dotmailer.on_build_mapping_tracked_fields';

    /**
     * @var array
     * [
     *   entityClass => [
     *      fieldName => [
     *          [
     *              channel_id - mapping's channel id
     *              parent_entity - entity used in the mapping
     *              field_path - full tracked field path used in the mapping. For example,
     *                           "primaryAddr+Oro\Bundle\ContactBundle\Entity\ContactAddress::street"
     *          ],
     *          ...
     *      ],
     *      anotherFieldName => [
     *          ...
     *      ]
     *   ],
     *   anotherEntityClass => [
     *      ...
     *   ]
     * ]
     */
    protected $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }
}
