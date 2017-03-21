<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Database_Models_Object
 */
final class NF_Database_Models_Object extends NF_Abstracts_Model
{
    protected $_type = 'object';

    protected $_table_name = 'nf3_objects';

    protected $_meta_table_name = 'nf3_object_meta';

    protected $_columns = array(
        'type',
        'created_at'
    );

    public function __construct( $db, $id, $parent_id = '', $parent_type = '' )
    {
        parent::__construct( $db, $id, $parent_id );

        $this->_parent_type = $parent_type;
    }

    public function save()
    {
        if( ! $this->_id ){

            $data = array( 'created_at' => time() );

            $result = $this->_db->insert(
                $this->_table_name,
                $data
            );

            $this->_id = $this->_db->insert_id;
        }

        $this->_save_settings();
    }

} // End NF_Database_Models_Object
