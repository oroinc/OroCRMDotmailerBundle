<?php

namespace Oro\Bundle\DotmailerBundle\Processor;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use Oro\Bundle\DotmailerBundle\QueryDesigner\ParentEntityFindQueryConverter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Psr\Log\LoggerInterface;

class MappedFieldsChangeProcessor
{
    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var ParentEntityFindQueryConverter */
    protected $queryConverter;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(DoctrineHelper $doctrineHelper, ParentEntityFindQueryConverter $queryConverter)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->queryConverter = $queryConverter;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Process the queue of modified entities fields and update corresponding address book contacts flag in case
     * we need to schedule datafields sync
     */
    public function processFieldChangesQueue()
    {
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(ChangedFieldLog::class);
        $em = $this->doctrineHelper->getEntityManager(ChangedFieldLog::class);
        $logs = new BufferedIdentityQueryResultIterator($repository->getLogsForProcessingQB());
        $abContactRepository = $this->doctrineHelper->getEntityRepositoryForClass(AddressBookContact::class);
        /** @var ChangedFieldLog $log */
        foreach ($logs as $log) {
            $column = [
                'name' => $log->getRelatedFieldPath(),
                'value' => $log->getRelatedId()
            ];
            try {
                $parentEntityQuery = $this->queryConverter->convert($log->getParentEntity(), [$column]);
                $parentEntityId = $parentEntityQuery->getQuery()->getOneOrNullResult();
                if ($parentEntityId) {
                    $parentEntityId = $parentEntityId[ParentEntityFindQueryConverter::PARENT_ENTITY_ID_ALIAS];
                    $addressBookContact = $abContactRepository->findOneBy([
                        'channel'                => $log->getChannelId(),
                        'marketingListItemId'    => $parentEntityId,
                        'marketingListItemClass' => $log->getParentEntity(),
                        'entityUpdated'          => false
                    ]);
                    if ($addressBookContact) {
                        $addressBookContact->setEntityUpdated(true);
                        $em->persist($addressBookContact);
                    }
                }
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->warning(
                        sprintf(
                            'Changes for %s relation field %s with id %s were not processed',
                            $log->getParentEntity(),
                            $log->getRelatedFieldPath(),
                            $log->getRelatedId()
                        ),
                        ['message' => $e->getMessage()]
                    );
                }
            }
            $em->remove($log);
            $em->flush();
        }
    }
}
