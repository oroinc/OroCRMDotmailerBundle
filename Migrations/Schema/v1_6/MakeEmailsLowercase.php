<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updates orocrm_dm_contact.email field to lowercase and adds constraint for this
 */
class MakeEmailsLowercase implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery("DELETE FROM orocrm_dm_contact WHERE origin_id IS NULL;");

        $queries->addPostQuery("UPDATE orocrm_dm_contact SET email = LOWER(email);");
    }
}
