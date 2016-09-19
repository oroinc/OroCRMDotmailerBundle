<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

class CampaignIterator extends AbstractIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';

    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * {@inheritdoc}
     */
    protected $batchSize = 100;

    /**
     * @var int
     */
    protected $addressBookOriginId;

    /**
     * @param IResources $dotmailerResources
     * @param int        $addressBookOriginId
     */
    public function __construct(IResources $dotmailerResources, $addressBookOriginId)
    {
        $this->dotmailerResources = $dotmailerResources;
        $this->addressBookOriginId = $addressBookOriginId;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        $items = $this->dotmailerResources
            ->GetAddressBookCampaigns(
                $this->addressBookOriginId,
                $take,
                $skip
            );

        $items = $items->toArray();
        foreach ($items as &$item) {
            $item[self::ADDRESS_BOOK_KEY] = $this->addressBookOriginId;
        }

        return $items;
    }
}
