<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_Database_Migrations_Actions extends NF_Abstracts_Migration
{
    public function __construct()
    {
        parent::__construct(
            'nf3_actions',
            'nf_migration_create_table_actions'
        );
    }

    public function run()
    {
        $query = "CREATE TABLE IF NOT EXISTS $this->table_name (
            `id` int NOT NULL AUTO_INCREMENT,
            `title` longtext,
            `key` longtext,
            `type` longtext,
            `active` boolean DEFAULT TRUE,
            `parent_id` int NOT NULL,
            `created_at` TIMESTAMP,
            `updated_at` DATETIME,
            UNIQUE KEY (`id`)
        ) $this->charset_collate;";

        dbDelta( $query );
    }

}