<?php

use WPML\API\Sanitize;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\Setup\Option;
use WPML\TM\API\Translators;
use WPML\TM\ATE\Review\ApproveTranslations;
use WPML\TM\ATE\Review\Cancel;
use WPML\TM\ATE\Review\ReviewStatus;
use WPML\FP\Str;
use WPML\API\PostTypes;
use WPML\TM\Editor\Editor;
use WPML\TM\API\Jobs;
use WPML\TM\Menu\TranslationQueue\PostTypeFilters;
use function WPML\FP\pipe;

class WPML_Translations_Queue {

	/** @var  SitePress $sitepress */
	private $sitepress;

	private $must_render_the_editor = false;

	/** @var WPML_Translation_Editor_UI */
	private $translation_editor;

	/**
	 * @var Editor
	 */
	private $editor;

	/**
	 * @param SitePress $sitepress
	 * @param Editor $editor
	 */
	public function __construct( SitePress $sitepress, Editor $editor ) {
		$this->sitepress = $sitepress;
		$this->editor    = $editor;
	}

	public function init_hooks() {
		add_action( 'current_screen', array( $this, 'load' ) );
	}

	public function load() {
		if ( $this->must_open_the_editor() ) {
			$response = $this->editor->open( $_GET );

			if ( in_array( Obj::prop( 'editor', $response ), [ \WPML_TM_Editors::ATE, \WPML_TM_Editors::WP ] ) ) {
				wp_safe_redirect( Obj::prop('url', $response), 302, 'WPML' );
				return;
			} elseif (Relation::propEq( 'editor', WPML_TM_Editors::WPML, $response )) {
				$this->openClassicTranslationEditor( Obj::prop('jobObject', $response) );
            }
		}
	}

	private function openClassicTranslationEditor( $job_object ) {
		global $wpdb;
		$this->must_render_the_editor = true;
		$this->translation_editor     = new WPML_Translation_Editor_UI(
			$wpdb,
			$this->sitepress,
			wpml_load_core_tm(),
			$job_object,
			new WPML_TM_Job_Action_Factory( wpml_tm_load_job_factory() ),
			new WPML_TM_Job_Layout( $wpdb, $this->sitepress->get_wp_api() )
		);
	}

	public function display() {
		if ( $this->must_render_the_editor ) {
			$this->translation_editor->render();

			return;
		}

		?>
		<div class="wrap">
			<h2><?php echo __( 'Translations queue', 'wpml-translation-management' ); ?></h2>

			<div class="js-wpml-abort-review-dialog"></div>
			<div id='wpml-remote-jobs-container'></div>
		</div>
		<?php
	}

	/**
	 * @return bool
	 */
	private function must_open_the_editor() {
		return Obj::prop( 'job_id', $_GET ) > 0 || Obj::prop( 'trid', $_GET ) > 0;
	}

	/**
     * @todo this method should be removed but we have to check firts the logic in NextTranslationLink
	 * @return array
	 */
	public static function get_cookie_filters() {
		$filters = [];

		if ( isset( $_COOKIE['wp-translation_ujobs_filter'] ) ) {
			parse_str( $_COOKIE['wp-translation_ujobs_filter'], $filters );

			$filters = filter_var_array(
				$filters,
				[
					'type'   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
					'from'   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
					'to'     => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
					'status' => FILTER_SANITIZE_NUMBER_INT,
				]
			);

			$isTypeValid = Logic::anyPass(
                [
                    Str::startsWith( 'package_' ),
                    Str::includes( 'st-batch_strings' ),
                    pipe( Str::replace( 'post_', '' ), Lst::includes( Fns::__, PostTypes::getTranslatable() ) ),
                ]
            );

			$activeLanguageCodes = Obj::keys( Languages::getActive() );

			if (
				$filters['from'] && ! Lst::includes( $filters['from'], $activeLanguageCodes ) ||
				$filters['to'] && ! Lst::includes( $filters['to'], $activeLanguageCodes ) ||
				( $filters['type'] && ! $isTypeValid( $filters['type'] ) )
			) {
				$filters = [];
			}
		}

		return $filters;
	}
}
