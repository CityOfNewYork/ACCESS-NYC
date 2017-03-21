<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_MergeTags_Form
 */
final class NF_MergeTags_Form extends NF_Abstracts_MergeTags
{
    protected $id = 'form';

    protected $sub_seq;

    public function __construct()
    {
        parent::__construct();
        $this->title = __( 'Form', 'ninja-forms' );

        $this->merge_tags = Ninja_Forms()->config( 'MergeTagsForm' );

        add_action( 'ninja_forms_save_sub', array( $this, 'setSubSeq' ) );
    }

    /**
     * @return mixed
     */
    public function getSubSeq()
    {
        return $this->sub_seq;
    }

    /**
     * @param mixed $sub_seq
     */
    public function setSubSeq( $sub_id )
    {
        $submission = Ninja_Forms()->form()->sub( $sub_id )->get();
        $this->sub_seq = $submission->get_seq_num();
    }

} // END CLASS NF_MergeTags_Form
