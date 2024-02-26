<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
* Entity that represents Dotmailer Transport
*
*/
#[ORM\Entity]
class DotmailerTransport extends Transport
{
    #[ORM\Column(name: 'orocrm_dm_api_username', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $username = null;

    #[ORM\Column(name: 'orocrm_dm_api_password', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $password = null;

    #[ORM\Column(name: 'orocrm_dm_api_client_id', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $clientId = null;

    #[ORM\Column(name: 'orocrm_dm_api_client_key', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $clientKey = null;

    #[ORM\Column(name: 'orocrm_dm_api_custom_domain', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $customDomain = null;

    /**
     * @var ParameterBag
     */
    protected $settingsBag;

    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settingsBag) {
            $this->settingsBag = new ParameterBag(
                [
                    'username' => $this->getUsername(),
                    'password' => $this->getPassword(),
                    'clientId' => $this->getClientId(),
                    'clientKey' => $this->getClientKey(),
                    'customDomain' => $this->getCustomDomain()
                ]
            );
        }

        return $this->settingsBag;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return DotmailerTransport
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return DotmailerTransport
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     *
     * @return DotmailerTransport
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientKey()
    {
        return $this->clientKey;
    }

    /**
     * @param string $clientKey
     *
     * @return DotmailerTransport
     */
    public function setClientKey($clientKey)
    {
        $this->clientKey = $clientKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomDomain()
    {
        return $this->customDomain;
    }

    /**
     * @param string $customDomain
     *
     * @return DotmailerTransport
     */
    public function setCustomDomain($customDomain)
    {
        $this->customDomain = $customDomain;

        return $this;
    }
}
