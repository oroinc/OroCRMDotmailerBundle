<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_2;

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
        if (!$table->hasIndex('orocrm_dm_activity_email_idx')) {
            $table->addIndex(['email'], 'orocrm_dm_activity_email_idx', []);
        }
        if (!$table->hasIndex('orocrm_dm_activity_dt_sent_idx')) {
            $table->addIndex(['date_sent'], 'orocrm_dm_activity_dt_sent_idx', []);
        }

        $table = $schema->getTable('orocrm_dm_campaign_summary');
        if (!$table->hasIndex('orocrm_dm_camp_sum_dt_sent_idx')) {
            $table->addIndex(['date_sent'], 'orocrm_dm_camp_sum_dt_sent_idx', []);
        }
    }
}
