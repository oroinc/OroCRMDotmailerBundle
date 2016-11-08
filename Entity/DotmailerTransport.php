<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

/**
 * @ORM\Entity()
 */
class DotmailerTransport extends Transport
{
    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_dm_api_username", type="string", length=255, nullable=false)
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_dm_api_password", type="string", length=255, nullable=false)
     */
    protected $password;

    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_dm_api_client_id", type="string", length=255, nullable=false)
     */
    protected $clientId;

    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_dm_api_client_key", type="string", length=255, nullable=false)
     */
    protected $clientKey;

    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_dm_api_custom_domain", type="string", length=255, nullable=false)
     */
    protected $customDomain;

    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_dm_api_refresh_token", type="string", length=255, nullable=false)
     */
    protected $refreshToken;

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
                    'password' => $this->getPassword()
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

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     *
     * @return DotmailerTransport
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }
}
