<?php

use WPML\Collect\Support\Collection;
use WPML\Convert\Ids;
use WPML\FP\Either;
use \WPML\FP\Obj;
use \WPML\ST\AdminTexts\TranslateNestedIds;
use function WPML\Container\make;
use function \WPML\FP\partial;
use function \WPML\FP\invoke;
use function \WPML\FP\flip;

class WPML_Admin_Texts extends WPML_Admin_Text_Functionality {
	const DOMAIN_NAME_PREFIX = 'admin_texts_';

	/** @var array $cache - A cache for each option translation */
	private $cache = [];

	/** @var array $cache - A cache for each option translation */
	private $cache_with_ids = [];

	/** @var array|null $option_names - The option names from Admin texts settings */
	private $option_names;

	/** @var array|null $option_names_with_ids - The option names from Admin texts settings holding translatable IDs */
	private $option_names_with_ids;

	/** @var  TranslationManagement $tm_instance */
	private $tm_instance;

	/** @var  WPML_String_Translation $st_instance */
	private $st_instance;

	/** @var \WPML\ST\AdminTexts\TranslateNestedIds $translate_nested_ids */
	private $translate_nested_ids;

	/** @var bool $lock */
	private $lock = false;

	/** @var array - A cache for each option value in the original language to allow restore after it was translated. */
	private $cache_option_values_in_def_lang_by_id = [];

	/**
	 * @param TranslationManagement   $tm_instance
	 * @param WPML_String_Translation $st_instance
	 */
	public function __construct( &$tm_instance, &$st_instance ) {
		add_action( 'plugins_loaded', [ $this, 'icl_st_set_admin_options_filters' ], 10 );
		add_action( 'plugins_loaded', [ $this, 'set_admin_options_ids_filters' ], 10 );
		add_filter( 'wpml_unfiltered_admin_string', flip( [ $this, 'get_option_without_filtering' ] ), 10, 2 );
		add_action( 'wpml_st_force_translate_admin_options', [ $this, 'force_translate_admin_options' ] );
		$this->tm_instance = &$tm_instance;
		$this->st_instance = &$st_instance;
		global $sitepress;
		$this->translate_nested_ids = new TranslateNestedIds( $sitepress );
	}

	/**
	 * @param mixed $value
	 *
	 * @return array|mixed|object
	 */
	private static function object_to_array( $value ) {
		return is_object( $value ) ? object_to_array( $value ) : $value;
	}

	public function icl_register_admin_options( $array, $key = '', $option = array() ) {
		$option = self::object_to_array( $option );
		foreach ( $array as $k => $v ) {
			$option = $key === '' ? array( $k => maybe_unserialize( $this->get_option_without_filtering( $k ) ) ) : $option;
			if ( is_array( $v ) ) {
				$this->icl_register_admin_options( $v, $key . '[' . $k . ']', $option[ $k ] );
			} else {
				$context  = $this->get_context( $key, $k );
				$opt_keys = self::findKeys( (string) $key );

				if ( $v === '' ) {
					icl_unregister_string( $context, $key . $k );
				} elseif ( isset( $option[ $k ] ) && ( $key === '' || $opt_keys ) ) {
					icl_register_string( $context, $key . $k, $option[ $k ], true );
					$vals     = array( $k => 1 );
					$opt_keys = array_reverse( $opt_keys );
					foreach ( $opt_keys as $opt ) {
						$vals = array( $opt => $vals );
					}

					// As per wpmldev-934 :
					// Now we use array_replace_recursive instead of array_merge_recursive when updating the _icl_admin_option_names, so this change is useful for the following cases :
					// 1- array_replace_recursive will preserve the numerical key of the options array when they're added to the already existing _icl_admin_option_names values, so the issue introduced in wpmldev-934 shouldn't happen again
					// 2- if array key in $vals already exists in _icl_admin_option_names the new value will be used instead of the old one, and this couldn't happen if we're using array_merge_recursive
					update_option(
						self::TRANSLATABLE_NAMES_SETTING,
						array_replace_recursive( (array) get_option( self::TRANSLATABLE_NAMES_SETTING ), $vals ),
						'no'
					);
					$this->clearOptionNames();
				}
			}
		}
	}

	public function getModelForRender() {
		return $this->getModel( $this->getOptions() );
	}

	/**
	 * @param Collection $options
	 *
	 * @return Collection
	 */
	public function getModel( Collection $options ) {
		$stringNamesPerContext = $this->getStringNamesPerContext();

		$isRegistered = function ( $context, $name ) use ( $stringNamesPerContext ) {
			return $stringNamesPerContext->has( $context ) &&
				   $stringNamesPerContext->get( $context )->contains( $name );
		};

		$getItems = partial( [ $this, 'getItemModel' ], $isRegistered );

		return $options->map( $getItems )
					   ->filter()
					   ->values()
					   ->reduce( [ $this, 'flattenModelItems' ], wpml_collect() );
	}


	/**
	 * @param Collection $flattened
	 * @param array      $item
	 *
	 * @return Collection
	 */
	public function flattenModelItems( Collection $flattened, array $item ) {
		if ( empty( $item ) ) {
			return $flattened;
		}

		if ( isset( $item['items'] ) ) {
			return $flattened->merge(
				$item['items']->reduce( [ $this, 'flattenModelItems' ], wpml_collect() )
			);
		}

		return $flattened->push( $item );
	}

	/**
	 * @param  callable $isRegistered  - string -> string -> bool.
	 * @param  mixed    $value
	 * @param  string   $name
	 * @param  string   $key
	 * @param  array    $stack
	 *
	 * @return array
	 */
	public function getItemModel( callable $isRegistered, $value, $name, $key = '', $stack = [] ) {
		$sub_key = $this->getSubKey( $key, $name );

		$result = [];

		if ( $this->isMultiValue( $value ) ) {
			$stack[] = $value;

			$getSubItem      = function ( $v, $key ) use ( $isRegistered, $sub_key, $stack ) {
				return $this->getItemModel( $isRegistered, $v, $key, $sub_key, $stack );
			};
			$result['items'] = wpml_collect( $value )
				->reject( $this->isOnStack( $stack ) )
				->map( $getSubItem );
		} elseif ( is_string( $value ) || is_numeric( $value ) ) {
			$context    = $this->get_context( $key, $name );
			$stringName = $this->getDBStringName( $key, $name );

			$result['value']           = $value;
			$result['fixed']           = $this->is_sub_key_fixed( $sub_key );
			$result['name']            = $sub_key;
			$result['registered']      = $isRegistered( $context, $stringName );
			$result['hasTranslations'] = ! $result['fixed'] && $result['registered']
										 && icl_st_string_has_translations( $context, $stringName );
		}

		return $result;

	}

	private function isOnStack( array $stack ) {
		return function ( $item ) use ( $stack ) {
			return \wpml_collect( $stack )->first(
				function ( $currentItem ) use ( $item ) {
					return $currentItem === $item;
				}
			) !== null;
		};
	}

	private function is_sub_key_fixed( $sub_key ) {
		$fixed = false;
		$keys  = self::findKeys( $sub_key );

		if ( $keys ) {
			$fixed          = true;
			$fixed_settings = $this->tm_instance->admin_texts_to_translate;
			foreach ( $keys as $m ) {
				$fixed = isset( $fixed_settings[ $m ] );

				if ( $fixed ) {
					$fixed_settings = $fixed_settings[ $m ];
				} else {
					break;
				}
			}
		}

		return $fixed;
	}

	private function get_context( $option_key, $option_name ) {
		$keys = self::findKeys( (string) $option_key );

		return self::DOMAIN_NAME_PREFIX . ( $keys ? reset( $keys ) : $option_name );
	}

	public function getOptions() {
		return wpml_collect( wp_load_alloptions() )
			->reject( flip( [ $this, 'is_blacklisted' ] ) )
			->map( 'maybe_unserialize' );
	}

	/**
	 * Do not translate options in:
	 * - The Customizer, it is built for options and is language-agnostic.
	 * - The backend when not doing AJAX, the value might be used inside a settings form input.
	 *
	 * Options translations can be shortcircuited with the wpml_skip_admin_options_filters action,
	 * for example when AJAX-saving theme or plugin settings.
	 *
	 * @param string $optionKey
	 *
	 * @return bool
	 */
	private function shouldFilterOption( $optionKey ) {
		global $wp_customize;
		if ( $wp_customize instanceof \WP_Customize_Manager ) {
			return false;
		}

		/**
		 * Can shortcircuit the translation of options on demand.
		 *
		 * @since 3.3.3
		 *
		 * @param bool   $shouldSkip (default: false)
		 * @param string $optionKey
		 *
		 * @return bool
		 */
		if ( apply_filters( 'wpml_skip_admin_options_filters', false, $optionKey ) ) {
			return false;
		}

		if ( ! is_admin() ) {
			return true;
		}

		if ( wpml_is_ajax() ) {
			return true;
		}

		return false;
	}

	public function icl_st_set_admin_options_filters() {
		$option_names = $this->getOptionNames();

		foreach ( $option_names as $option_key => $option ) {
			if ( $this->is_blacklisted( $option_key ) ) {
				unset( $option_names[ $option_key ] );
				update_option( self::TRANSLATABLE_NAMES_SETTING, $option_names, 'no' );
				continue;
			}
			if ( 'theme' === $option_key || 'plugin' === $option_key ) { // theme and plugin are an obsolete format before 3.2.
				continue;
			}
			if ( $this->shouldFilterOption( $option_key ) ) {
				$this->add_filter_for( $option_key );
			}
			add_action( 'update_option_' . $option_key, array( $this, 'on_update_original_value' ), 10, 3 );
		}
	}

	public function set_admin_options_ids_filters() {
		$option_names = $this->getOptionNamesWithIds();

		foreach ( $option_names as $option_key => $option_settings ) {
			if ( $this->is_blacklisted( $option_key ) ) {
				unset( $option_names[ $option_key ] );
				update_option( self::TRANSLATABLE_ID_NAMES_SETTING, $option_names, 'no' );
				continue;
			}
			if ( 'theme' === $option_key || 'plugin' === $option_key ) { // theme and plugin are an obsolete format before 3.2.
				continue;
			}
			if ( $this->shouldFilterOption( $option_key ) ) {
				$this->add_ids_filter_for( $option_key );
			}
		}
	}

	/**
	 * @param array $options
	 */
	public function force_translate_admin_options( $options ) {
		wpml_collect( $options )->each( [ $this, 'add_filter_for' ] );
	}

	/**
	 * @param string $option
	 */
	public function add_filter_for( $option ) {
		add_filter( 'option_' . $option, [ $this, 'icl_st_translate_admin_string' ] );
	}

	/**
	 * @param string $option
	 */
	public function add_ids_filter_for( $option ) {
		add_filter( 'option_' . $option, [ $this, 'translate_ids_in_admin_string' ], 11, 2 );
	}

	public function icl_st_translate_admin_string( $option_value, $key = '', $name = '', $root_level = true ) {
		if ( $root_level && $this->lock ) {
			return $option_value;
		}

		if ( $root_level && is_array( $option_value ) ) {
			foreach ( $option_value as $id => $value ) {
				$this->cache_option_values_in_def_lang_by_id[ $id ] = $value;
			}
		}

		$this->lock = true;

		$lang        = $this->st_instance->get_current_string_language( $name );
		$option_name = substr( current_filter(), 7 );
		$name        = $name === '' ? $option_name : $name;
		$blog_id     = get_current_blog_id();

		if ( isset( $this->cache[ $blog_id ][ $lang ][ $name ] ) ) {
			$this->lock = false;

			return $this->cache[ $blog_id ][ $lang ][ $name ];
		}

		$is_serialized = is_serialized( $option_value );
		$option_value  = $is_serialized ? unserialize( $option_value ) : $option_value; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

		if ( is_array( $option_value ) || is_object( $option_value ) ) {
			$option_value = $this->translate_multiple( $option_value, $key, $name );
		} else {
			$option_value = $this->translate_single( $option_value, $key, $name, $option_name );
		}

		$option_value = $is_serialized ? serialize( $option_value ) : $option_value; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

		if ( $root_level ) {
			$this->lock                                = false;
			$this->cache[ $blog_id ][ $lang ][ $name ] = $option_value;
		}

		return $option_value;
	}

	/**
	 * @param mixed  $option_value
	 * @param string $option_name
	 *
	 * @return mixed
	 */
	public function translate_ids_in_admin_string( $option_value, $option_name ) {
		$option_names = $this->getOptionNamesWithIds();
		if ( ! array_key_exists( $option_name, $option_names ) ) {
			return $option_value;
		}

		$blog_id = get_current_blog_id();

		if ( isset( $this->cache_with_ids[ $blog_id ][ $option_name ] ) ) {
			return $this->cache_with_ids[ $blog_id ][ $option_name ];
		}

		$is_serialized = is_serialized( $option_value );
		$option_value  = $is_serialized ? unserialize( $option_value ) : $option_value;  // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		$option_paths  = $option_names[ $option_name ];

		foreach ( $option_paths as $path_and_object_data ) {
			$type         = Obj::propOr( TranslateNestedIds::TYPE_POST_IDS, 'type', $path_and_object_data );
			$slug         = Obj::propOr( Ids::ANY_POST, 'slug', $path_and_object_data );
			$path         = explode( '>', Obj::propOr( '', 'path', $path_and_object_data ) );
			$option_value = $this->translate_nested_ids->convertByPath( $option_value, $path, $type, $slug );
		}

		$option_value = $is_serialized ? serialize( $option_value ) : $option_value; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

		$this->cache_with_ids[ $blog_id ][ $option_name ] = $option_value;

		return $option_value;
	}

	/**
	 * @param string $key - string like '[key1][key2]'.
	 * @param string $name
	 *
	 * @return bool
	 */
	private function isAdminText( $key, $name ) {

		return null !== Either::of( $this->getSubKey( $key, $name ) )
							  ->map( [ self::class, 'getKeysParts' ] )
							  ->tryCatch( invoke( 'reduce' )->with( flip( Obj::prop() ), $this->getOptionNames() ) )
							  ->getOrElse( null );
	}

	/**
	 * Signature: getKeys :: string [key1][key2][name] => Collection [key1, key2, name].
	 *
	 * @param string $option
	 *
	 * @return Collection
	 */
	public static function getKeysParts( $option ) {
		return wpml_collect( self::findKeys( $option ) );
	}

	/**
	 * @param string $string
	 *
	 * @return array
	 */
	private static function findKeys( $string ) {
		return array_filter( explode( '][', preg_replace( '/^\[(.*)\]$/', '$1', $string ) ), 'strlen' );
	}

	public function clear_cache_for_option( $option_name ) {
		$blog_id = get_current_blog_id();
		if ( isset( $this->cache[ $blog_id ] ) ) {
			foreach ( $this->cache[ $blog_id ] as $lang_code => &$cache_data ) {
				if ( array_key_exists( $option_name, $cache_data ) ) {
					unset( $cache_data[ $option_name ] );
				}
			}
		}
	}

	/**
	 * @param string|array $old_value
	 * @param string|array $value
	 * @param string       $option_name
	 * @param string       $name
	 * @param string       $sub_key
	 */
	public function on_update_original_value( $old_value, $value, $option_name, $name = '', $sub_key = '' ) {
		// We receive translated $old_value here after add_filter_for execution so need to restore $old_value in the default language.
		if ( '' === $sub_key ) {
			if ( is_array( $old_value ) && is_array( $value ) && count( $old_value ) === count( $value ) ) {
				foreach ( $value as $option_id => $option_value ) {
					if ( ! array_key_exists( $option_id, $this->cache_option_values_in_def_lang_by_id ) ) {
						continue;
					}

					foreach ( $old_value as $old_option_id => &$old_option_value ) {
						if ( $old_option_id === $option_id ) {
							$old_option_value = $this->cache_option_values_in_def_lang_by_id[ $option_id ];
						}
					}
				}
			}
		}

		$name = $name ? $name : $option_name;

		$value     = self::object_to_array( $value );
		$old_value = self::object_to_array( $old_value );

		if ( is_array( $value ) ) {
			foreach ( array_keys( $value ) as $key ) {
				$this->on_update_original_value(
					isset( $old_value[ $key ] ) ? $old_value[ $key ] : '',
					$value[ $key ],
					$option_name,
					$key,
					$this->getSubKey( $sub_key, $name )
				);
			}
		} else {
			if ( $this->isAdminText( $sub_key, $name ) ) {
				icl_st_update_string_actions( self::DOMAIN_NAME_PREFIX . $option_name, $this->getDBStringName( $sub_key, $name ), $old_value, $value );
			}
		}

		if ( $sub_key === '' ) {
			$this->clear_cache_for_option( $option_name );
		}
	}

	public function migrate_original_values() {
		$migrate = function ( $option_name ) {
			$option_value = maybe_unserialize( $this->get_option_without_filtering( $option_name ) );
			$this->on_update_original_value( '', $option_value, $option_name );
		};
		wpml_collect( $this->getOptionNames() )
			->keys()
			->filter()
			->each( $migrate );
	}

	/**
	 * Returns a function to lazy load the migration
	 *
	 * @return Closure
	 */
	public static function get_migrator() {
		return function () {
			wpml_st_load_admin_texts()->migrate_original_values();
		};
	}

	/**
	 * @param mixed  $option_value
	 * @param string $key
	 * @param string $name
	 *
	 * @return array|mixed
	 */
	private function translate_multiple( $option_value, $key, $name ) {
		$subKey = $this->getSubKey( $key, $name );

		foreach ( $option_value as $k => &$value ) {
			$value = $this->icl_st_translate_admin_string(
				$value,
				$subKey,
				$k,
				false
			);
		}

		return $option_value;
	}

	/**
	 * @param string $option_value
	 * @param string $key
	 * @param string $name
	 * @param string $option_name
	 *
	 * @return string
	 */
	private function translate_single( $option_value, $key, $name, $option_name ) {
		if ( $option_value !== '' && $this->isAdminText( $key, $name ) ) {
			$option_value = icl_translate( self::DOMAIN_NAME_PREFIX . $option_name, $key . $name, $option_value );
		}

		return $option_value;
	}

	/**
	 * @return array
	 */
	private function getOptionNames() {
		if ( null === $this->option_names ) {
			$this->option_names = get_option( self::TRANSLATABLE_NAMES_SETTING );
			if ( ! is_array( $this->option_names ) ) {
				$this->option_names = [];
			}
		}

		return $this->option_names;
	}

	private function clearOptionNames() {
		$this->option_names = null;
	}

	/**
	 * @return array
	 */
	private function getOptionNamesWithIds() {
		if ( null === $this->option_names_with_ids ) {
			$this->option_names_with_ids = get_option( self::TRANSLATABLE_ID_NAMES_SETTING, [] );
			if ( ! is_array( $this->option_names_with_ids ) ) {
				$this->option_names_with_ids = [];
			}
		}

		return $this->option_names_with_ids;
	}

	/**
	 * Signature: getSubKeys :: string [key1][key2] -> string name => string [key1][key2][name]
	 *
	 * @param string $key - [key1][key2].
	 * @param string $name
	 *
	 * @return string
	 */
	private function getSubKey( $key, $name ) {
		return $key . '[' . $name . ']';
	}

	/**
	 * Signature: getSubKeys :: string [key1][key2] -> string name => string [key1][key2]name
	 *
	 * @param string $key
	 * @param string $name
	 *
	 * @return string
	 */
	private function getDBStringName( $key, $name ) {
		return $key . $name;
	}

	/**
	 * @return Collection
	 * @throws \WPML\Auryn\InjectionException - Throws an exception in case of errors.
	 */
	private function getStringNamesPerContext() {
		$strings = make( WPML_ST_DB_Mappers_Strings::class )
			->get_all_by_context( self::DOMAIN_NAME_PREFIX . '%' );

		return wpml_collect( $strings )
			->groupBy( 'context' )
			->map( invoke( 'pluck' )->with( 'name' ) );
	}

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	private function isMultiValue( $value ) {
		return is_array( $value ) ||
			   ( is_object( $value ) && '__PHP_Incomplete_Class' !== get_class( $value ) );
	}
}
