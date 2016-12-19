<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\DotmailerBundle\Entity\OAuth;

class OAuthRepository extends EntityRepository
{
    /**
     * Find OAuth by Channel and User
     *
     * @param Channel $channel
     * @param User $user
     * @return OAuth|null
     */
    public function findByChannelAndUser(Channel $channel, User $user)
    {
        return $this->createQueryBuilder('oauth')
            ->where('oauth.channel = :channel')
            ->andWhere('oauth.user = :user')
            ->setMaxResults(1)
            ->setParameters([
                'channel' => $channel,
                'user' => $user
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
