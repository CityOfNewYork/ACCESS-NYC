<?php


namespace WPML\ST\Upgrade\Command;


use WPML\Element\API\Languages;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\LIB\WP\Option;
use WPML\ST\MO\File\ManagerFactory;
use function WPML\FP\partial;
use function WPML\FP\pipe;

class MigrateMultilingualWidgets implements \IWPML_St_Upgrade_Command {

	public function run() {
		$multiLingualWidgets = Option::getOr( 'widget_text_icl', [] );
		$multiLingualWidgets = array_filter( $multiLingualWidgets, Logic::complement( 'is_scalar' ) );
		if ( ! $multiLingualWidgets ) {
			return true;
		}

		$textWidgets = Option::getOr( 'widget_text', [] );
		if ( $textWidgets ) {
			/** @var array $textWidgetsKeys */
			$textWidgetsKeys = Obj::keys( $textWidgets );
			$theHighestTextWidgetId = max( $textWidgetsKeys );
		} else {
			$theHighestTextWidgetId      = 0;
			$textWidgets['_multiwidget'] = 1;
		}

		$transformWidget = pipe(
			Obj::renameProp( 'icl_language', 'wpml_language' ),
			Obj::over( Obj::lensProp( 'wpml_language' ), Logic::ifElse( Relation::equals( 'multilingual' ), Fns::always( 'all' ), Fns::identity() ) )
		);

		$oldToNewIdMap = [];
		foreach ( $multiLingualWidgets as $id => $widget ) {
			$newId                = ++ $theHighestTextWidgetId;
			$oldToNewIdMap[ $id ] = $newId;

			$textWidgets = Obj::assoc( $newId, $transformWidget( $widget ), $textWidgets );
		}

		Option::update( 'widget_text', $textWidgets );
		Option::delete( 'widget_text_icl' );

		$sidebars = wp_get_sidebars_widgets();
		$sidebars = $this->convertSidebarsConfig( $sidebars, $oldToNewIdMap );
		wp_set_sidebars_widgets( $sidebars );

		$this->convertWidgetsContentStrings();

		return true;
	}

	private function convertSidebarsConfig( $sidebars, array $oldToNewIdMap ) {
		$isMultilingualWidget = Str::startsWith( 'text_icl' );
		$extractIdNumber      = pipe( Str::split( '-' ), Lst::last(), Cast::toInt() );

		$mapWidgetId = Logic::ifElse(
			$isMultilingualWidget,
			pipe( $extractIdNumber, Obj::prop( Fns::__, $oldToNewIdMap ), Str::concat( 'text-' ) ),
			Fns::identity()
		);

		return Fns::map( Fns::map( $mapWidgetId ), $sidebars );
	}

	private function convertWidgetsContentStrings() {
		global $wpdb;

		$wpdb->query("
			UPDATE {$wpdb->prefix}icl_strings
			SET `name` = CONCAT( 'widget body - ', MD5(`value`))
			WHERE `name` LIKE 'widget body - text_icl%'
		");

		$locales = Fns::map( Languages::getWPLocale(), Languages::getSecondaries() );
		Fns::map( partial( [ ManagerFactory::create(), 'add' ], 'Widgets' ), $locales );
	}

	public function run_ajax() {
	}

	public function run_frontend() {
	}

	public static function get_command_id() {
		return __CLASS__;
	}


}