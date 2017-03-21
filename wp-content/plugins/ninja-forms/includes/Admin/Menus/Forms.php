<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Menus_Forms extends NF_Abstracts_Menu
{
    public $page_title = 'Forms';

    public $menu_slug = 'ninja-forms';

    public $icon_url = 'dashicons-feedback';

    public $position = '35.1337';

    public function __construct()
    {
        parent::__construct();

        if( ! defined( 'DOING_AJAX' ) ) {
            add_action('admin_init', array($this, 'admin_init'));
            add_action( 'admin_init', array( 'NF_Admin_AllFormsTable', 'process_bulk_action' ) );
        }
    }

    public function get_page_title()
    {
        return __( 'Forms', 'ninja-forms' );
    }

    public function admin_init()
    {
        if( isset( $_GET[ 'form_id' ] ) && ! is_numeric( $_GET[ 'form_id' ] ) && 'new' != $_GET[ 'form_id' ] ) {
            if( current_user_can( apply_filters( 'ninja_forms_admin_import_template_capabilities', 'manage_options' ) ) ) {
                $this->import_from_template();
            }
        }

        $this->table = new NF_Admin_AllFormsTable();
    }

    public function display()
    {
        if( isset( $_GET[ 'form_id' ] ) ){


            if( 'new' == $_GET[ 'form_id' ] ) {
                $form_id = 'tmp-' . time();
            } else {
                $form_id = (is_numeric($_GET['form_id'])) ? absint($_GET['form_id']) : '';
            }

            /*
             * FORM BUILDER
             */

            Ninja_Forms::template( 'admin-menu-new-form.html.php' );

            $this->_enqueue_the_things( $form_id );

            delete_user_option( get_current_user_id(), 'nf_form_preview_' . $form_id );

            if( ! isset( $_GET[ 'ajax' ] ) ) {
                $this->_localize_form_data( $form_id );

                $this->_localize_field_type_data();

                $this->_localize_action_type_data();

                $this->_localize_form_settings();

                $this->_localize_merge_tags();
            }
        } else {

            /*
             * ALL FORMS TABLE
             */

            $this->table->prepare_items();

            Ninja_Forms::template( 'admin-menu-all-forms.html.php', array(
                'table' => $this->table,
                'add_new_url' => admin_url( 'admin.php?page=ninja-forms&form_id=new' ),
                'add_new_text' => __( 'Add New Form', 'ninja-forms' )
            ) );
        }
    }

    public function submenu_separators()
    {
        add_submenu_page( 'ninja-forms', '', '', 'read', '', '' );
    }

    private function import_from_template()
    {
        $template = sanitize_title( $_GET['form_id'] );

        $form = Ninja_Forms::template( $template . '.nff', array(), TRUE );

        if( ! $form ) die( 'Template not found' );

        $form = json_decode( html_entity_decode( $form ), true );

        $form_id = Ninja_Forms()->form()->import_form( $form );

        if( ! $form_id ){
            $error_message = ( function_exists( 'json_last_error_msg' ) && json_last_error_msg() ) ? json_last_error_msg() : __( 'Form Template Import Error.', 'ninja-forms' );
            wp_die( $error_message );
        }

        header( "Location: " . admin_url( "admin.php?page=ninja-forms&form_id=$form_id" ) );
        exit();
    }

    private function _enqueue_the_things( $form_id )
    {
        global $wp_locale;

        wp_enqueue_media();

        wp_enqueue_style( 'nf-builder', Ninja_Forms::$url . 'assets/css/builder.css' );
        wp_enqueue_style( 'nf-font-awesome', Ninja_Forms::$url . 'assets/css/font-awesome.min.css' );
        /**
         * CSS Libraries
         */
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'jBox', Ninja_Forms::$url . 'assets/css/jBox.css' );
        wp_enqueue_style( 'summernote', Ninja_Forms::$url . 'assets/css/summernote.css' );
        wp_enqueue_style( 'codemirror', Ninja_Forms::$url . 'assets/css/codemirror.css' );
        wp_enqueue_style( 'codemirror-monokai', Ninja_Forms::$url . 'assets/css/monokai-theme.css' );
        wp_enqueue_style( 'pikaday-responsive', Ninja_Forms::$url . 'assets/css/pikaday-package.css' );

        /**
         * JS Libraries
         */
        wp_enqueue_script( 'wp-util' );
        wp_enqueue_script( 'jquery-autoNumeric', Ninja_Forms::$url . 'assets/js/lib/jquery.autoNumeric.min.js', array( 'jquery', 'backbone' ) );
        wp_enqueue_script( 'jquery-maskedinput', Ninja_Forms::$url . 'assets/js/lib/jquery.maskedinput.min.js', array( 'jquery', 'backbone' ) );
        wp_enqueue_script( 'backbone-marionette', Ninja_Forms::$url . 'assets/js/lib/backbone.marionette.min.js', array( 'jquery', 'backbone' ) );
        wp_enqueue_script( 'backbone-radio', Ninja_Forms::$url . 'assets/js/lib/backbone.radio.min.js', array( 'jquery', 'backbone' ) );
        wp_enqueue_script( 'jquery-perfect-scrollbar', Ninja_Forms::$url . 'assets/js/lib/perfect-scrollbar.jquery.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'jquery-hotkeys-new', Ninja_Forms::$url . 'assets/js/lib/jquery.hotkeys.min.js' );
        wp_enqueue_script( 'jBox', Ninja_Forms::$url . 'assets/js/lib/jBox.min.js' );
        wp_enqueue_script( 'jquery-caret', Ninja_Forms::$url . 'assets/js/lib/jquery.caret.min.js' );
        wp_enqueue_script( 'speakingurl', Ninja_Forms::$url . 'assets/js/lib/speakingurl.js' );
        wp_enqueue_script( 'jquery-slugify', Ninja_Forms::$url . 'assets/js/lib/slugify.min.js', array( 'jquery', 'speakingurl' ) );
        wp_enqueue_script( 'jquery-mobile-events', Ninja_Forms::$url . 'assets/js/lib/jquery.mobile-events.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'jquery-ui-touch-punch', Ninja_Forms::$url . 'assets/js/lib/jquery.ui.touch-punch.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'jquery-classy-wiggle', Ninja_Forms::$url . 'assets/js/lib/jquery.classywiggle.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'moment-with-locale', Ninja_Forms::$url . 'assets/js/lib/moment-with-locales.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'modernizr', Ninja_Forms::$url . 'assets/js/lib/modernizr.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'pikaday', Ninja_Forms::$url . 'assets/js/lib/pikaday.min.js', array( 'moment-with-locale' ) );
        wp_enqueue_script( 'pikaday-responsive', Ninja_Forms::$url . 'assets/js/lib/pikaday-responsive.min.js', array( 'pikaday', 'modernizr' ) );

        wp_enqueue_script( 'bootstrap', Ninja_Forms::$url . 'assets/js/lib/bootstrap.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'codemirror', Ninja_Forms::$url . 'assets/js/lib/codemirror.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'codemirror-xml', Ninja_Forms::$url . 'assets/js/lib/codemirror-xml.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'codemirror-formatting', Ninja_Forms::$url . 'assets/js/lib/codemirror-formatting.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'summernote', Ninja_Forms::$url . 'assets/js/lib/summernote.min.js', array( 'jquery', 'speakingurl' ) );


        wp_enqueue_script( 'nf-builder', Ninja_Forms::$url . 'assets/js/min/builder.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-effects-bounce', 'wp-color-picker' ) );
        wp_localize_script( 'nf-builder', 'nfi18n', Ninja_Forms::config( 'i18nBuilder' ) );

        wp_localize_script( 'nf-builder', 'nfAdmin', array(
            'ajaxNonce'         => wp_create_nonce( 'ninja_forms_builder_nonce' ),
            'requireBaseUrl'    => Ninja_Forms::$url . 'assets/js/',
            'previewurl'        => home_url() . '/?nf_preview_form=',
            'wp_locale'         => $wp_locale->number_format,
            'editFormText'      => __( 'Edit Form', 'ninja-forms' ),
            'mobile'            => ( wp_is_mobile() ) ? 1: 0,
            'currencySymbols'   => array_merge( array( '' => Ninja_Forms()->get_setting( 'currency_symbol' ) ), Ninja_Forms::config( 'CurrencySymbol' ) ),
            'dateFormat'        => Ninja_Forms()->get_setting( 'date_format' )
        ));

        do_action( 'nf_admin_enqueue_scripts' );
    }

    private function _localize_form_data( $form_id )
    {
        $form = Ninja_Forms()->form( $form_id )->get();

        if( ! $form->get_tmp_id() ) {

            if( $form_cache = get_option( 'nf_form_' . $form_id, false ) ) {
                $fields = $form_cache[ 'fields' ];
            } else {
                $fields = ($form_id) ? Ninja_Forms()->form($form_id)->get_fields() : array();
            }
            $actions = ($form_id) ? Ninja_Forms()->form($form_id)->get_actions() : array();
        } else {
            $fields = array();
            $actions = array();
        }

        $fields_settings = array();

        // echo "<pre>";
        // print_r( $fields );
        // echo "</pre>";
        // die();

        if( ! empty( $fields ) ) {

            // TODO: Replace unique field key checks with a refactored model/factory.
//            $unique_field_keys = array();
//            $form_cache = get_option( 'nf_form_' . $form_id, false );
//            $cache_updated = false;

            foreach ($fields as $field) {

                $field_id = ( is_object( $field ) ) ? $field->get_id() : $field[ 'id' ];

                /*
                 * Duplicate field check.
                 * TODO: Replace unique field key checks with a refactored model/factory.
                 */
//                $field_key = $field->get_setting( 'key' );
//                if( in_array( $field_key, $unique_field_keys ) || '' == $field_key ){
//
//                    // Delete the field.
//                    Ninja_Forms()->request( 'delete-field' )->data( array( 'field_id' => $field_id ) )->dispatch();
//
//                    // Remove the field from cache.
//                    if( $form_cache ) {
//                        if( isset( $form_cache[ 'fields' ] ) ){
//                            foreach( $form_cache[ 'fields' ] as $cached_field_key => $cached_field ){
//                                if( ! isset( $cached_field[ 'id' ] ) ) continue;
//                                if( $field_id != $cached_field[ 'id' ] ) continue;
//
//                                // Flag cache to update.
//                                $cache_updated = true;
//
//                                unset( $form_cache[ 'fields' ][ $cached_field_key ] ); // Remove the field.
//                            }
//                        }
//                    }
//
//                    continue; // Skip the duplicate field.
//                }
//                array_push( $unique_field_keys, $field_key ); // Log unique key.
                /* END Duplicate field check. */

                $type = ( is_object( $field ) ) ? $field->get_setting( 'type' ) : $field[ 'settings' ][ 'type' ];

                if( ! isset( Ninja_Forms()->fields[ $type ] ) ){
                    $field = NF_Fields_Unknown::create( $field );
                }

                $settings = ( is_object( $field ) ) ? $field->get_settings() : $field[ 'settings' ];
                $settings[ 'id' ] =  $field_id;


//                foreach ($settings as $key => $setting) {
//                    if (is_numeric($setting)) $settings[$key] = floatval($setting);
//                }

                $fields_settings[] = $settings;
            }

//            if( $cache_updated ) {
//                update_option('nf_form_' . $form_id, $form_cache); // Update form cache without duplicate fields.
//            }
        }

        $actions_settings = array();

        if( ! empty( $actions ) ) {
            foreach ($actions as $action) {

                $type = $action->get_setting( 'type' );

                if( ! isset( Ninja_Forms()->actions[ $type ] ) ) continue;

                $settings = $action->get_settings();
                $settings['id'] = $action->get_id();

                $actions_settings[] = $settings;
            }
        }

        if( $form->get_tmp_id() ){

            $actions_settings = Ninja_Forms()->config( 'FormActionDefaults' );
        }

        $form_data = array();
        $form_data['id'] = $form_id;

        // Use form cache for form settings.
        // TODO: Defer to refactor of factory/model.
        if( isset( $form_cache[ 'settings' ] ) ) {
            $form_data['settings'] = $form_cache[ 'settings' ];
        } else {
            $form_data['settings'] = $form->get_settings();
        }

        $form_data['fields'] = $fields_settings;
        $form_data['actions'] = $actions_settings;

        ?>
        <script>
            var preloadedFormData = <?php echo wp_json_encode( $form_data ); ?>;
            // console.log( preloadedFormData );
        </script>
        <?php
    }

    private function _localize_field_type_data()
    {
        $field_type_sections = array_values( Ninja_Forms()->config( 'FieldTypeSections' ) );
        $field_type_settings = array();

        $master_settings = array();

        $setting_defaults = array();

        foreach( Ninja_Forms()->fields as $field ){

            $name = $field->get_name();
            $settings = $field->get_settings();
            $groups = Ninja_Forms::config( 'SettingsGroups' );

            $unique_settings = $this->_unique_settings( $settings );

            $master_settings = array_merge( $master_settings, $unique_settings );

            $settings_groups = $this->_group_settings( $settings, $groups );

            $settings_defaults = $this->_setting_defaults( $unique_settings );

            $field_type_settings[ $name ] = array(
                'id' =>  $name,
                'nicename' => $field->get_nicename(),
                'alias' => $field->get_aliases(),
                'parentType' => $field->get_parent_type(),
                'section' => $field->get_section(),
                'icon' => $field->get_icon(),
                'type' => $field->get_type(),
                'settingGroups' => $settings_groups,
                'settingDefaults' => $settings_defaults
            );
        }

        $saved_fields = Ninja_Forms()->form()->get_fields( array( 'saved' => 1) );

        foreach( $saved_fields as $saved_field ){

            $settings = $saved_field->get_settings();

            $id     = $saved_field->get_id();
            $type   = $settings[ 'type' ];
            $label  = $settings[ 'label' ];

            $field_type_settings[ $id ] = $field_type_settings[ $type ];
            $field_type_settings[ $id ][ 'id' ] = $id;
            $field_type_settings[ $id ][ 'type' ] = $type;
            $field_type_settings[ $id ][ 'nicename' ] = $label;
            $field_type_settings[ $id ][ 'section' ] = 'saved';

            $defaults = $field_type_settings[ $id ][ 'settingDefaults' ];
            $defaults = array_merge( $defaults, $settings );
            $defaults[ 'saved' ] = TRUE;

            $field_type_settings[ $id ][ 'settingDefaults' ] = $defaults;
        }

        ?>
        <script>
            var fieldTypeData     = <?php echo wp_json_encode( array_values( $field_type_settings ) ); ?>;
            var fieldSettings     = <?php echo wp_json_encode( array_values( $master_settings ) ); ?>;
            var fieldTypeSections = <?php echo wp_json_encode( $field_type_sections ); ?>;
            // console.log( fieldTypeData );
        </script>
        <?php
    }

    private function _localize_action_type_data()
    {
        $action_type_settings = array();

        $master_settings_list = array();

        foreach( Ninja_Forms()->actions as $action ){

            $name = $action->get_name();
            $settings = $action->get_settings();
            $groups = Ninja_Forms::config( 'SettingsGroups' );

            $settings_groups = $this->_group_settings( $settings, $groups );

            $master_settings_list = array_merge( $master_settings_list, $settings );

            $action_type_settings[ $name ] = array(
                'id' => $name,
                'section' => $action->get_section(),
                'nicename' => $action->get_nicename(),
                'image' => $action->get_image(),
                'settingGroups' => $settings_groups,
                'settingDefaults' => $this->_setting_defaults( $master_settings_list )
            );
        }

        $external_actions = $this->_fetch_action_feed();
        $u_id = get_option( 'nf_aff', false );
        if ( !$u_id ) $u_id = apply_filters( 'ninja_forms_affiliate_id', false );
        foreach( $external_actions as $action){

            if( ! isset( $action[ 'name' ] ) || ! $action[ 'name' ] ) continue;

            $name = $action[ 'name' ];
            $nicename = ( isset( $action[ 'nicename' ] ) ) ? $action[ 'nicename' ] : '';
            $image = ( isset( $action[ 'image' ] ) ) ? $action[ 'image' ] : '';
            $link = ( isset( $action[ 'link' ] ) ) ? $action[ 'link' ] : '';

            if ( $u_id ) {
                $last_slash = strripos( $link, '/' );
                $link = substr( $link, 0, $last_slash );
                $link =  urlencode( $link );
                $link = 'http://www.shareasale.com/r.cfm?u=' . $u_id . '&b=812237&m=63061&afftrack=&urllink=' . $link;
            }

            if( isset( $action_type_settings[ $name ] ) ) continue;

            $action_type_settings[ $name ] = array(
                'id' => $name,
                'section' => 'available',
                'nicename' => $nicename,
                'image' => $image,
                'link' => $link,
                'settingGroups' => array(),
                'settingDefaults' => array()
            );
        }

        $action_type_settings = apply_filters( 'ninja_forms_action_type_settings', $action_type_settings );

        ?>
        <script>
            var actionTypeData = <?php echo wp_json_encode( array_values( $action_type_settings ) ); ?>;
            var actionSettings = <?php echo wp_json_encode( array_values( $master_settings_list ) ); ?>;
            // console.log( actionTypeData );
        </script>
        <?php
    }

    protected function _localize_form_settings()
    {
        $form_settings_types = Ninja_Forms::config( 'FormSettingsTypes' );

        $form_settings[ 'display' ] = Ninja_Forms::config( 'FormDisplaySettings' );
        $form_settings[ 'restrictions' ] = Ninja_Forms::config( 'FormRestrictionSettings' );
        $form_settings[ 'calculations' ] = Ninja_Forms::config( 'FormCalculationSettings' );
        $form_settings = apply_filters( 'ninja_forms_localize_forms_settings', $form_settings );

        foreach( $form_settings_types as $group_name => $form_setting_group ){
            if( ! isset( $form_settings[ $group_name ] ) ) $form_settings[ $group_name ] = array();
            $form_settings[ $group_name ] = apply_filters( 'ninja_forms_localize_form_' . $group_name . '_settings', $form_settings[ $group_name ] );
        }

        $groups = Ninja_Forms::config( 'SettingsGroups' );

        $master_settings = array();

        foreach( $form_settings_types as $id => $type ) {

            if( ! isset( $form_settings[ $id ] ) ) $form_settings[ $id ] = '';

            $unique_settings = $this->_unique_settings( $form_settings[ $id ] );
            $master_settings = array_merge( $master_settings, $unique_settings );

            $form_settings_types[ $id ]['settingGroups'] = $this->_group_settings($form_settings[ $id ], $groups);
            $form_settings_types[ $id ]['settingDefaults'] = $this->_setting_defaults($unique_settings);
        }
        ?>
        <script>
        var formSettingTypeData = <?php echo wp_json_encode( array_values( $form_settings_types ) )?>;
        var formSettings = <?php echo wp_json_encode( array_values( $master_settings ) )?>;
        </script>
        <?php
    }

    protected function _localize_merge_tags()
    {
        $merge_tags = array(
            'fields' => array(
                'id'    => 'fields',
                'label' => __( 'Fields', 'ninja-forms' )
            )
        );

        foreach( Ninja_Forms()->merge_tags as $key => $group ){

            $merge_tags[ $key ] = array(
                'id'    => $group->get_id(),
                'label' => $group->get_title(),
                'tags'  => array_values( $group->get_merge_tags() ),
                'default_group' => $group->is_default_group()
            );
        }
        ?>
        <script>
            var mergeTags = <?php echo wp_json_encode( array_values( $merge_tags ) ); ?>;
        </script>
        <?php
    }


    protected function _group_settings( $settings, $groups )
    {
        if( ! is_array( $settings ) ) return $groups;

        foreach( $settings as $setting ){

            $group = ( isset( $setting[ 'group' ] ) ) ? $setting[ 'group' ] : '';

            if( isset( $setting[ 'type'] ) && 'fieldset' == $setting[ 'type' ] ){
                $setting[ 'settings' ] = array_values( $setting[ 'settings' ] );
            }

            $groups[ $group ][ 'settings'][] = $setting;
        }

        foreach( $groups as $id => $group ) {
            if ( empty( $group[ 'settings' ] ) ) {
                unset( $groups[ $id ] );
            }
        }

        unset( $groups[ "" ] );

        usort($groups, array( $this, 'setting_group_priority' ) );

        return $groups;
    }

    protected function _unique_settings( $settings )
    {
        $unique_settings = array();

        if( ! is_array( $settings ) ) return $unique_settings;

        foreach( $settings as $setting ){

            if( 'fieldset' == $setting[ 'type' ] ){

                $unique_settings = array_merge( $unique_settings, $this->_unique_settings( $setting[ 'settings' ] ) );
            } else {

                $name = $setting[ 'name' ];
                $unique_settings[ $name ] = $setting;
            }

        }

        return $unique_settings;
    }

    protected function _setting_defaults( $settings )
    {
        $setting_defaults = array();

        foreach( $settings as $setting ){

            $name = ( isset( $setting[ 'name' ] ) ) ? $setting[ 'name' ] : '';
            $default = ( isset( $setting[ 'value' ] ) ) ? $setting[ 'value' ] : '';
            $setting_defaults[ $name ] = $default;
        }

        return $setting_defaults;
    }

    protected function _fetch_action_feed()
    {
        return Ninja_Forms::config( 'AvailableActions' );
    }

    protected function setting_group_priority( $a, $b )
    {
        $priority[ 0 ] = ( isset( $a[ 'priority' ] ) ) ? $a[ 'priority' ] : 500;
        $priority[ 1 ] = ( isset( $b[ 'priority' ] ) ) ? $b[ 'priority' ] : 500;

        return $priority[ 0 ] - $priority[ 1 ];
    }

    public function get_capability()
    {
        return apply_filters( 'ninja_forms_admin_parent_menu_capabilities', $this->capability );
    }

}
