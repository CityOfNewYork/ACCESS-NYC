<?php

class WPML_ST_String_Update {
	private $wpdb;

	/**
	 * WPML_ST_String_Update constructor.
	 *
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Updates an original string without changing its id or its translations
	 *
	 * @param string     $domain
	 * @param string     $name
	 * @param string     $old_value
	 * @param string     $new_value
	 * @param bool|false $force_complete , @see \WPML_ST_String_Update::handle_status_change
	 *
	 * @return int|null
	 */
	public function update_string( $domain, $name, $old_value, $new_value, $force_complete = false ) {
		if ( $new_value != $old_value ) {
			/** @var object{id: int, value: string, status: string, name: string} $string */
			$string = $this->get_initial_string( $name, $domain, $old_value, $new_value );
			$this->wpdb->update(
				$this->wpdb->prefix . 'icl_strings',
				array( 'value' => $new_value ),
				array( 'id' => $string->id )
			);
			$is_widget = $domain === WPML_ST_WIDGET_STRING_DOMAIN;
			if ( $is_widget && $new_value ) {
				$this->update_widget_name( $string->name, $old_value, $new_value );
			}
			$this->handle_status_change( $string, $force_complete || $is_widget );

			/**
			 * This action is fired when a string original value is modified.
			 *
			 * @since 3.0.0
			 *
			 * @param string     $domain
			 * @param string     $name
			 * @param string     $old_value
			 * @param string     $new_value
			 * @param bool|false $force_complete
			 * @param object     $string
			 */
			do_action( 'wpml_st_update_string', $domain, $name, $old_value, $new_value, $force_complete, $string );
		}

		return isset( $string ) && isset( $string->id ) ? $string->id : null;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	function sanitize_string( $string ) {
		return html_entity_decode( $string, ENT_QUOTES );
	}

	/**
	 * Handles string status changes resulting from the string update
	 *
	 * @param object{id: int, value: string, status: string, name: string} $string
	 * @param bool                                                         $force_complete if true, all translations
	 *                               will be marked as complete even though  a string's original value has been updated,
	 *                               currently this applies to blogname and tagline strings
	 */
	private function handle_status_change( $string, $force_complete ) {
		if ( $string->status == ICL_TM_COMPLETE || $string->status == ICL_STRING_TRANSLATION_PARTIAL ) {
			$new_status = $force_complete ? ICL_TM_COMPLETE : ICL_TM_NEEDS_UPDATE;
			foreach (
				array(
					'icl_string_translations' => 'string_id',
					'icl_strings'             => 'id',
				) as $table_name => $id_col
			) {
				$this->wpdb->update(
					$this->wpdb->prefix . $table_name,
					array( 'status' => $new_status ),
					array( $id_col => $string->id )
				);
			}
		}
	}

	/**
	 * @param string $name
	 * @param string $context
	 * @param string $old_value
	 * @param string $new_value
	 *
	 * @return object{id: int, value: string, status: string, name: string}
	 */
	private function get_initial_string( $name, $context, $old_value, $new_value ) {
		$string = $this->read_string_from_db( $name, $context );
		if ( ! $string ) {
			if ( $context !== WPML_ST_WIDGET_STRING_DOMAIN ) {
				icl_register_string( $context, $name, $new_value );
			} else {
				list( $res, $name ) = $this->update_widget_name( $name, $old_value, $new_value );
				if ( ! $res ) {
					icl_register_string( $context, $name, $new_value );
				}
			}
		}
		$string = $this->read_string_from_db( $name, $context );

		return $string;
	}

	/**
	 * Reads a strings id,value,status and name directly from the database without any caching.
	 *
	 * @param string $name
	 * @param string $context
	 *
	 * @return object{id: int, value: string, status: string, name: string}|null
	 */
	private function read_string_from_db( $name, $context ) {
		/** @var string $sql */
		$sql = $this->wpdb->prepare(
			" 
				SELECT id, value, status, name
				FROM {$this->wpdb->prefix}icl_strings
				WHERE context = %s
					AND name = %s
				LIMIT 1",
			$context,
			$name
		);

		return $this->wpdb->get_row( $sql );
	}

	/**
	 * Updates a widgets string name if it's value got changed, since widget string's name and value are coupled.
	 * Changes in value necessitate changes in the name. @see \icl_sw_filters_widget_title and \icl_sw_filters_widget_body
	 *
	 * @param string $name
	 * @param string $old_value
	 * @param string $new_value
	 *
	 * @return array
	 */
	private function update_widget_name( $name, $old_value, $new_value ) {
		$res = 0;
		if ( 0 === strpos( $name, 'widget title - ' ) ) {
			$name     = 'widget title - ' . md5( $new_value );
			$old_name = 'widget title - ' . md5( $old_value );

			if ( $this->read_string_from_db( $name, WPML_ST_WIDGET_STRING_DOMAIN ) ) {
				$old_string = $this->read_string_from_db( $old_name, WPML_ST_WIDGET_STRING_DOMAIN );
				if ( $old_string ) {
					$this->delete_old_widget_title_string_if_new_already_exists( $old_string );
				}
			} else {
				$res = $this->write_widget_update_to_db( WPML_ST_WIDGET_STRING_DOMAIN, $old_name, $name );
			}
		} elseif ( 0 === strpos( $name, 'widget body - ' ) ) {
			$name = 'widget body - ' . md5( $new_value );
			$res  = $this->write_widget_update_to_db(
				WPML_ST_WIDGET_STRING_DOMAIN,
				'widget body - ' . md5( $old_value ),
				$name
			);
		}

		return array( $res, $name );
	}

	/**
	 * Writes updates to a widget strings name to the icl_strings table.
	 *
	 * @param string $context
	 * @param string $old_name
	 * @param string $new_name
	 *
	 * @return false|int false on error, 1 on successful update and 0 if no update took place
	 */
	private function write_widget_update_to_db( $context, $old_name, $new_name ) {

		return $this->wpdb->update(
			$this->wpdb->prefix . 'icl_strings',
			array(
				'name'                    => $new_name,
				'domain_name_context_md5' => md5( WPML_ST_WIDGET_STRING_DOMAIN . $new_name ),
			),
			array(
				'context' => $context,
				'name'    => $old_name,
			)
		);
	}

	private function delete_old_widget_title_string_if_new_already_exists( $string ) {
		$this->wpdb->delete( $this->wpdb->prefix . 'icl_string_translations', array( 'string_id' => $string->id ) );
		$this->wpdb->delete( $this->wpdb->prefix . 'icl_strings', array( 'id' => $string->id ) );
	}
}
