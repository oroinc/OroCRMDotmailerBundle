<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropDeprecatedColumnsForContact implements Migration
{
    private const COLUMNS_TO_DROP = [
        'first_name',
        'last_name',
        'full_name',
        'gender',
        'postcode',
        'merge_var_values'
    ];

    private const ENTITY_CONFIG_FIELDS_TO_DROP = [
        'firstName',
        'lastName',
        'fullName',
        'gender',
        'postcode',
        'mergeVarValues'
    ];

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('orocrm_dm_contact');

        foreach (self::COLUMNS_TO_DROP as $columnName) {
            $table->dropColumn($columnName);
        }

        foreach (self::ENTITY_CONFIG_FIELDS_TO_DROP as $entityField) {
            $queries->addPostQuery(new RemoveFieldQuery(Contact::class, $entityField));
        }
    }
}
