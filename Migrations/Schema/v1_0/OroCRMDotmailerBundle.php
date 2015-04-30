<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroCRMDotmailerBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->updateOroIntegrationTransportTable($schema);
        $this->createOroCRMDotmailerCampaignTable($schema);
        $this->createOroCRMDotmailerAddressBookTable($schema);
        $this->createOroCRMDotmailerContactTable($schema);
        $this->createOroCRMDotmailerActivityTable($schema);
        $this->createOroCRMDotmailerCampaignToABTable($schema);
        $this->createOroCRMDotmailerContactToABTable($schema);

        /** Add Foreign Keys */
        $this->addOroCRMDotmailerCampaignForeignKeys($schema);
        $this->addOroCRMDotmailerAddressBookForeignKeys($schema);
        $this->addOroCRMDotmailerContactForeignKeys($schema);
        $this->addOroCRMDotmailerActivityForeignKeys($schema);
        $this->addOroCRMDotmailerCampaignToABForeignKeys($schema);
        $this->addOroCRMDotmailerContactToABForeignKeys($schema);
    }

    /**
     * Update oro_integration_transport table
     *
     * @param Schema $schema
     */
    protected function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('orocrm_dm_api_username', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_password', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * Create orocrm_dm_campaign table
     *
     * @param Schema $schema
     */
    protected function createOroCRMDotmailerCampaignTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_campaign');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('subject', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('from_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('from_address', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('html_content', 'text', ['notnull' => false]);
        $table->addColumn('plain_text_content', 'text', ['notnull' => false]);
        $table->addColumn('reply_to_address', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_split_test', 'boolean', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['owner_id'], 'IDX_3D36193A7E3C61F9', []);
        $table->addIndex(['channel_id'], 'IDX_3D36193A72F5A1AA', []);
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'orocrm_dm_campaign_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orocrm_dm_address_book table
     *
     * @param Schema $schema
     */
    protected function createOroCRMDotmailerAddressBookTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_address_book');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('marketing_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('contact_count', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'IDX_9A9DD33F7E3C61F9', []);
        $table->addIndex(['channel_id'], 'IDX_9A9DD33F72F5A1AA', []);
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'orocrm_dm_address_book_unq');
        $table->addUniqueIndex(['marketing_list_id'], 'UNIQ_9A9DD33F96434D04');
    }

    /**
     * Create orocrm_dm_contact table
     *
     * @param Schema $schema
     */
    protected function createOroCRMDotmailerContactTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_contact');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('full_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('gender', 'string', ['notnull' => false, 'length' => 6]);
        $table->addColumn('postcode', 'string', ['notnull' => false, 'length' => 12]);
        $table->addColumn('merge_var_values', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['owner_id'], 'IDX_6D7FB88E7E3C61F9', []);
        $table->addIndex(['channel_id'], 'IDX_6D7FB88E72F5A1AA', []);
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'orocrm_dm_contact_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orocrm_dm_activity table
     *
     * @param Schema $schema
     */
    protected function createOroCRMDotmailerActivityTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_activity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('campaign_id', 'integer', []);
        $table->addColumn('contact_id', 'integer', []);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('num_opens', 'integer', ['notnull' => false]);
        $table->addColumn('num_page_views', 'integer', ['notnull' => false]);
        $table->addColumn('num_clicks', 'integer', ['notnull' => false]);
        $table->addColumn('num_forwards', 'integer', ['notnull' => false]);
        $table->addColumn('num_estimated_forwards', 'integer', ['notnull' => false]);
        $table->addColumn('num_replies', 'integer', ['notnull' => false]);
        $table->addColumn('date_sent', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('date_first_opened', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('date_last_opened', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('first_open_ip', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('unsubscribed', 'boolean', ['notnull' => false]);
        $table->addColumn('soft_bounced', 'boolean', ['notnull' => false]);
        $table->addColumn('hard_bounced', 'boolean', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['channel_id'], 'IDX_8E5702BD72F5A1AA', []);
        $table->addIndex(['owner_id'], 'IDX_8E5702BD7E3C61F9', []);
        $table->addIndex(['campaign_id'], 'IDX_8E5702BDF639F774', []);
        $table->addIndex(['contact_id'], 'IDX_8E5702BDE7A1254A', []);
        $table->addUniqueIndex(['campaign_id', 'contact_id', 'channel_id'], 'orocrm_dm_activity_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orocrm_dm_campaign_to_ab table
     *
     * @param Schema $schema
     */
    protected function createOroCRMDotmailerCampaignToABTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_campaign_to_ab');
        $table->addColumn('campaign_id', 'integer', []);
        $table->addColumn('address_book_id', 'integer', []);
        $table->addIndex(['address_book_id'], 'IDX_AA5589424D474419', []);
        $table->addIndex(['campaign_id'], 'IDX_AA558942F639F774', []);
        $table->setPrimaryKey(['campaign_id', 'address_book_id']);
    }

    /**
     * Create orocrm_dm_contact_to_ab table
     *
     * @param Schema $schema
     */
    protected function createOroCRMDotmailerContactToABTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_contact_to_ab');
        $table->addColumn('contact_id', 'integer', []);
        $table->addColumn('address_book_id', 'integer', []);
        $table->addIndex(['address_book_id'], 'IDX_ECE957004D474419', []);
        $table->addIndex(['contact_id'], 'IDX_ECE95700E7A1254A', []);
        $table->setPrimaryKey(['contact_id', 'address_book_id']);
    }

    /**
     * Add orocrm_dm_campaign foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCRMDotmailerCampaignForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_campaign');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orocrm_dm_address_book foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCRMDotmailerAddressBookForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_marketing_list'),
            ['marketing_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orocrm_dm_contact foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCRMDotmailerContactForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_contact');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orocrm_dm_activity foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCRMDotmailerActivityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_activity');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_campaign'),
            ['campaign_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_contact'),
            ['contact_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orocrm_dm_campaign_to_ab foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCRMDotmailerCampaignToABForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_campaign_to_ab');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_address_book'),
            ['address_book_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_campaign'),
            ['campaign_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orocrm_dm_contact_to_ab foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCRMDotmailerContactToABForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_contact_to_ab');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_address_book'),
            ['address_book_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_contact'),
            ['contact_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}