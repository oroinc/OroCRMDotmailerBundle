<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport;

use DotMailer\Api\DataTypes\ApiAccount;
use DotMailer\Api\DataTypes\ApiCampaignContactClickList;
use DotMailer\Api\DataTypes\ApiCampaignContactOpenList;
use DotMailer\Api\Rest\IClient;

/**
 * Adds missing API methods
 * Extend DotMailer\Api\Resources\Resources class from romanpitak/dotmailer-api-v2-php-client bundle is not possible
 * because of final keyword.
 */
class AdditionalResource
{
    /** @var IClient */
    protected $restClient;

    public function __construct(IClient $restClient)
    {
        $this->restClient = $restClient;
    }

    /**
     * @param string $url
     * @param string $method
     * @param string $data
     * @return string
     */
    protected function execute($url, $method = 'GET', $data = null)
    {
        return $this->restClient->execute([$url, $method, $data], []);
    }

    /**
     * @return ApiAccount
     */
    public function getAccountInfo()
    {
        return new ApiAccount($this->execute('account-info'));
    }

    /**
     * @param string $campaignId
     * @param string $dateTime
     * @param int $select
     * @param int $skip
     * @return ApiCampaignContactClickList
     */
    public function getCampaignClicksSinceDateByDate($campaignId, $dateTime, $select = 1000, $skip = 0)
    {
        $url = sprintf("campaigns/%s/clicks/since-date/%s?select=%s&skip=%s", $campaignId, $dateTime, $select, $skip);

        return new ApiCampaignContactClickList($this->execute($url));
    }

    /**
     * @param string $campaignId
     * @param string $dateTime
     * @param int $select
     * @param int $skip
     * @return ApiCampaignContactOpenList
     */
    public function getCampaignOpensSinceDateByDate($campaignId, $dateTime, $select = 1000, $skip = 0)
    {
        $url = sprintf("campaigns/%s/opens/since-date/%s?select=%s&skip=%s", $campaignId, $dateTime, $select, $skip);

        return new ApiCampaignContactOpenList($this->execute($url));
    }
}
