<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_Database_Migrations_Fields extends NF_Abstracts_Migration
{
    public function __construct()
    {
        parent::__construct(
            'nf3_fields',
            'nf_migration_create_table_fields'
        );
    }

    public function run()
    {
        $query = "CREATE TABLE IF NOT EXISTS $this->table_name (
            `id` int NOT NULL AUTO_INCREMENT,
            `label` longtext,
            `key` longtext,
            `type` longtext,
            `parent_id` int NOT NULL,
            `created_at` TIMESTAMP,
            `updated_at` DATETIME,
            UNIQUE KEY (`id`)
        ) $this->charset_collate;";

        dbDelta( $query );
    }

}