<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

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
}
