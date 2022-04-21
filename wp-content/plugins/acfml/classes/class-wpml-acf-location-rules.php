<?php

use WPML\Element\API\PostTranslations;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class WPML_ACF_Location_Rules {
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * WPML_ACF_Location_Rules constructor.
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * Registers hooks.
	 */
	public function register_hooks() {
		Hooks::onFilter( 'acf/location/rule_match', 11, 3 )->then( spreadArgs( [ $this, 'rule_match' ] ) );
		Hooks::onFilter( 'acf/load_field_group' )->then( spreadArgs( [ $this, 'adjust_post_id_on_edit_screen' ] ) );
	}
	
	/**
	 * @param bool  $match
	 * @param array $rule
	 * @param array $options
	 * @return bool
	 */
	public function rule_match( $match, $rule, $options ) {
		$match = $this->rule_match_post( $match, $rule, $options );
		return $this->rule_match_page_parent( $match, $rule, $options );
	}

	/**
	 * @param bool  $match
	 * @param array $rule
	 * @param array $options
	 * @return bool
	 */
	private function rule_match_post( $match, $rule, $options ) {
		if ( isset( $rule['param'] )
			 && in_array( $rule['param'], get_post_types() )
			 && ! $this->sitepress->is_translated_post_type( 'acf-field-group' )
			 && isset( $options['post_id'] )
		) {
			$match = $this->match_against_operator(
				in_array( (int) $rule['value'], self::get_translation_ids( $options['post_id'] ), true ),
				$rule['operator']
			);
		}

		return $match;
	}
	
	/**
	 * @param bool  $match
	 * @param array $rule
	 * @param array $options
	 * @return bool
	 */
	private function rule_match_page_parent( $match, $rule, $options ) {
		if ( isset( $rule['param'], $rule['value'], $rule['operator'], $options['lang'] )
			 && 'page_parent' === $rule['param']
			 && ! $this->sitepress->is_translated_post_type( 'acf-field-group' )
		) {
			$page_parent = Obj::propOr( wp_get_post_parent_id( get_the_ID() ), 'page_parent', $options );
			$match       = $this->match_against_operator(
				intval( Obj::propOr( $rule['value'], $options['lang'], self::get_translation_ids( $rule['value'] ) ) ) === intval( $page_parent ),
				$rule['operator']
			);
		}
		return $match;
	}
	
	/**
	 * @param bool   $match
	 * @param string $operator
	 *
	 * @return bool
	 */
	private function match_against_operator( $match, $operator ) {
		if ( '!=' === $operator ) {
			$match = ! $match;
		}
		return $match;
	}
	
	/**
	 * @param array $group
	 *
	 * @return array
	 */
	public function adjust_post_id_on_edit_screen( $group ) {
		if ( $this->is_field_group_edit_screen( $group['ID'] ) &&
			$this->group_has_post_rule( $group )
		) {
			$group['location'] = $this->replace_post_ids( $group['location'] );
		}
		return $group;
	}
	
	/**
	 * @param int $groupId
	 *
	 * @return bool
	 */
	private function is_field_group_edit_screen( $groupId ) {
		return
			isset( $_GET['post'], $_GET['action'] ) &&
			(int) $groupId === (int) $_GET['post'] &&
			'edit' === $_GET['action'] &&
			get_post_type( $groupId ) === 'acf-field-group';
	}
	
	/**
	 * @param array $group
	 *
	 * @return bool
	 */
	private function group_has_post_rule( $group ) {
		if ( isset( $group['location'] ) && is_array( $group['location'] ) ) {
			return $this->has_post_rule( $group['location'] );
		}
		return false;
	}
	
	/**
	 * @param array $location
	 *
	 * @return bool
	 */
	private function has_post_rule( $location ) {
		foreach ( $location as $chunk ) {
			if ( isset( $chunk['param'] ) ) {
				if( in_array( $chunk['param'], get_post_types() ) ) {
					return true;
				}
			} elseif( is_array( $chunk ) ) {
				return $this->has_post_rule( $chunk );
			}
		}
		return false;
	}
	
	/**
	 * @param array $location
	 *
	 * @return array
	 */
	private function replace_post_ids( $location ) {
		foreach( $location as $key => $chunk ) {
			if ( isset( $chunk['param'], $chunk['value'] ) && in_array( $chunk['param'], get_post_types() ) ) {
				$location[ $key ]['value'] = $this->replace_id( $location[ $key ]['value'], $chunk['param'] );
			} elseif ( is_array( $chunk ) ) {
				$location[ $key ] = $this->replace_post_ids( $chunk );
			}
		}
		return $location;
	}
	
	/**
	 * @param int    $post_id
	 * @param string $post_type
	 *
	 * @return int
	 */
	private function replace_id( $post_id, $post_type ) {
		return apply_filters( 'wpml_object_id', $post_id, $post_type, true );
	}

	/**
	 * @param int|string $postId
	 *
	 * @return array<string, int> "language code" => "post ID"
	 */
	public static function get_translation_ids( $postId ) {
		return wpml_collect( PostTranslations::get( $postId ) )
			->mapWithKeys( function( $data ) {
				return [ Obj::prop( 'language_code', $data ) => intval( Obj::prop( 'element_id', $data ) ) ];
			} )
			->toArray();
	}
}