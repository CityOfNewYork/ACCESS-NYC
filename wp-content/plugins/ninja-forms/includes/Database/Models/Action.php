<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Database_Models_Action
 */
final class NF_Database_Models_Action extends NF_Abstracts_Model
{
    private $form_id = '';

    protected $_type = 'action';

    protected $_table_name = 'nf3_actions';

    protected $_meta_table_name = 'nf3_action_meta';

    protected $_columns = array(
        'title',
        'key',
        'type',
        'active',
        'created_at'
    );

    public function __construct( $db, $id, $parent_id = '' )
    {
        parent::__construct( $db, $id, $parent_id );
    }

} // End NF_Database_Models_Action
