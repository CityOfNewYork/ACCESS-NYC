<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_Database_Migrations_Forms extends NF_Abstracts_Migration
{
    public function __construct()
    {
        parent::__construct(
            'nf3_forms',
            'nf_migration_create_table_forms'
        );
    }

    public function run()
    {
        $query = "CREATE TABLE IF NOT EXISTS $this->table_name (
            `id` int NOT NULL AUTO_INCREMENT,
            `title` longtext,
            `key` longtext,
            `created_at` TIMESTAMP,
            `updated_at` DATETIME,
            `views` int(11),
            `subs` int(11),
            UNIQUE KEY (`id`)
        ) $this->charset_collate;";

        dbDelta( $query );
    }

}
