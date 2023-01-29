<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Model\DataFieldManager;

/**
 * Listens to DataField Entity pre-remove event and deletes info in the Dotmailer
 */
class DataFieldRemoveListener
{
    /** @var DataFieldManager */
    protected $dataFieldManager;

    public function __construct(DataFieldManager $dataFieldManager)
    {
        $this->dataFieldManager = $dataFieldManager;
    }

    /**
     * Remove origin data field.
     * If origin data field can't be remove, throw exception and don't allow to remove record
     *
     * @throws RuntimeException
     */
    public function preRemove(DataField $entity, LifecycleEventArgs $args)
    {
        if ($entity->isForceRemove()) {
            return;
        }

        //try to delete the field in Dotmailer. Throwing exception if the field can't be removed there.
        try {
            $result = $this->dataFieldManager->removeOriginDataField($entity);
        } catch (RestClientException $e) {
            if ($e->getPrevious()) {
                //for system fields dotmailer returns 404 response with error message
                throw new RuntimeException($e->getPrevious()->getMessage());
            } else {
                //uknown reason
                throw new RuntimeException(
                    'The field cannot be removed.'
                );
            }
        }
        if (!isset($result['result']) || $result['result'] === 'false') {
            throw new RuntimeException('The field cannot be removed. It is in use elsewhere in the system.');
        }
    }
}
