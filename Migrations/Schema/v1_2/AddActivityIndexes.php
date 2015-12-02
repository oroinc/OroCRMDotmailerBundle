<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddActivityIndexes implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_dm_activity');
        $table->addIndex(['email'], 'orocrm_dm_activity_email_idx', []);
        $table->addIndex(['date_sent'], 'orocrm_dm_activity_dt_sent_idx', []);
    }
}
