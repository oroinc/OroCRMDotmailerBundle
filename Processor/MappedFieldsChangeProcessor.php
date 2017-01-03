<?php

namespace OroCRM\Bundle\DotmailerBundle\Processor;

use Psr\Log\LoggerInterface;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use OroCRM\Bundle\DotmailerBundle\QueryDesigner\ParentEntityFindQueryConverter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class MappedFieldsChangeProcessor
{
    const DEFAULT_BATCH = 100;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var ParentEntityFindQueryConverter */
    protected $queryConverter;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ParentEntityFindQueryConverter $queryConverter
     */
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
        $logs = $repository->findBy([], null, self::DEFAULT_BATCH);
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
