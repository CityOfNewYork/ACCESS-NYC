<?php

use WPML\Element\API\Languages;
use WPML\FP\Fns;
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
use WPML\TM\Menu\TranslationQueue\JobsRepository;
use function WPML\FP\pipe;

class WPML_Translations_Queue {

	/** @var  SitePress $sitepress */
	private $sitepress;

	/* @var WPML_UI_Screen_Options_Pagination */
	private $screen_options;

	/** @var WPML_Admin_Table_Sort $table_sort */
	private $table_sort;

	private $must_render_the_editor = false;

	/** @var WPML_Translation_Editor_UI */
	private $translation_editor;

	/**
	 * @var Editor
	 */
	private $editor;

	/** @var JobsRepository  */
	private $jobs_repository;

	/**
	 * @param SitePress                      $sitepress
	 * @param WPML_UI_Screen_Options_Factory $screen_options_factory
	 * @param Editor                      $editor
	 */
	public function __construct(
		$sitepress,
		$screen_options_factory,
		Editor $editor
	) {
		$this->sitepress      = $sitepress;
		$this->screen_options = $screen_options_factory->create_pagination(
			'tm_translations_queue_per_page',
			ICL_TM_DOCS_PER_PAGE
		);
		$this->table_sort     = $screen_options_factory->create_admin_table_sort();
		$this->editor     = $editor;

		$this->jobs_repository = new JobsRepository( wpml_tm_get_jobs_repository( true, false ) );
	}

	public function init_hooks() {
		add_action( 'current_screen', array( $this, 'load' ) );
	}

	public function load() {
		if ( $this->must_open_the_editor() ) {
			$response = $this->editor->open( $_GET );

			if ( Relation::propEq( 'editor', WPML_TM_Editors::ATE, $response ) ) {
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

	/**
	 * @param array $icl_translation_filter
	 *
	 * @throws \InvalidArgumentException
	 */
	public function display( array $icl_translation_filter = array() ) {
		if ( $this->must_render_the_editor ) {
			$this->translation_editor->render();

			return;
		}

		/* @var TranslationManagement $iclTranslationManagement */
		global $iclTranslationManagement;

		/* @var WPML_Translation_Job_Factory $wpml_translation_job_factory */
		$wpml_translation_job_factory = wpml_tm_load_job_factory();

		$translation_jobs = array();
		$job_types        = array();
		$langs_from       = array();
		$lang_from        = array();
		$langs_to         = array();
		$lang_to          = array();
		$job_id           = null;

		if ( ! empty( $_GET['resigned'] ) ) {
			$iclTranslationManagement->add_message(
				array(
					'type' => 'updated',
					'text' => __(
						"You've resigned from this job.",
						'wpml-translation-management'
					),
				)
			);
		}

		$action = false;
		$actionCommands = [
			'approve' => [ ApproveTranslations::class, 'run' ],
			'cancel'  => [ Cancel::class, 'run' ],
		];

		if ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) {
			$action = isset( $_POST['doaction'] ) ? $_POST['action'] : $_POST['action2'];

			$command        = Obj::propOr( Fns::identity(), $action, $actionCommands );
			$command( Obj::keys( Obj::propOr( [], 'job', $_POST ) ) );
		}

		$cookie_filters = self::get_cookie_filters();

		if ( $cookie_filters ) {
			$icl_translation_filter = $cookie_filters;
		}

		if ( isset( $_GET['status'] ) ) {
			$icl_translation_filter['status'] = (int) Obj::prop( 'status', $_GET );
		}

		$current_translator                 = Translators::getCurrent();
		$current_translator->language_pairs = Fns::map(
			pipe( Fns::map( pipe( Lst::makePair( 1 ), Lst::reverse() ) ), Lst::fromPairs() ),
			$current_translator->language_pairs
		);

		$can_translate     = $current_translator && $current_translator->ID > 0 && $current_translator->language_pairs;
		$post_link_factory = new WPML_TM_Post_Link_Factory( $this->sitepress );
		if ( $can_translate ) {
			$icl_translation_filter['translator_id']      = $current_translator->ID;
			$icl_translation_filter['include_unassigned'] = true;

			$element_type_prefix = isset( $_GET['element_type'] ) ? $_GET['element_type'] : 'post';
			if ( isset( $_GET['updated'] ) && $_GET['updated'] ) {
				$tm_post_link_updated = $post_link_factory->view_link( $_GET['updated'] );
				if ( $iclTranslationManagement->is_external_type( $element_type_prefix ) ) {
					$tm_post_link_updated = apply_filters(
						'wpml_external_item_link',
						$tm_post_link_updated,
						$_GET['updated'],
						false
					);
				}
				$user_message = __( 'Translation updated: ', 'wpml-translation-management' ) . $tm_post_link_updated;
				$iclTranslationManagement->add_message(
					array(
						'type' => 'updated',
						'text' => $user_message,
					)
				);
			} elseif ( isset( $_GET['added'] ) && $_GET['added'] ) {
				$tm_post_link_added = $post_link_factory->view_link( $_GET['added'] );
				if ( $iclTranslationManagement->is_external_type( $element_type_prefix ) ) {
					$tm_post_link_added = apply_filters(
						'wpml_external_item_link',
						$tm_post_link_added,
						$_GET['added'],
						false
					);
				}
				$user_message = __( 'Translation added: ', 'wpml-translation-management' ) . $tm_post_link_added;
				$iclTranslationManagement->add_message(
					array(
						'type' => 'updated',
						'text' => $user_message,
					)
				);
			} elseif ( isset( $_GET['job-cancelled'] ) ) {
				$user_message = __( 'Translation has been removed by admin', 'wpml-translation-management' );
				$iclTranslationManagement->add_message(
					array(
						'type' => 'error',
						'text' => $user_message,
					)
				);
			}

			if ( isset( $_GET['title'] ) && $_GET['title'] ) {
				$icl_translation_filter['title'] = filter_var( $_GET['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			}

			if ( ! empty( $current_translator->language_pairs ) ) {
				$_langs_to = array();
				if ( 1 < count( $current_translator->language_pairs ) ) {
					foreach ( $current_translator->language_pairs as $lang => $to ) {
						$langs_from[] = $this->sitepress->get_language_details( $lang );
						$_langs_to    = array_merge( (array) $_langs_to, array_keys( $to ) );
					}
					$_langs_to = array_unique( $_langs_to );
				} else {
					$_langs_to                      = array_keys( current( $current_translator->language_pairs ) );
					$lang_from                      = $this->sitepress->get_language_details( key( $current_translator->language_pairs ) );
					$icl_translation_filter['from'] = $lang_from['code'];
				}

				if ( 1 < count( $_langs_to ) ) {
					foreach ( $_langs_to as $lang ) {
						$langs_to[] = $this->sitepress->get_language_details( $lang );
					}
				} else {
					$lang_to                      = $this->sitepress->get_language_details( current( $_langs_to ) );
					$icl_translation_filter['to'] = $lang_to['code'];

                }

				$job_types = $wpml_translation_job_factory->get_translation_job_types_filter(
					[],
					[ 'translator_id' => $current_translator->ID, 'include_unassigned' => true, ]
				);

				if ( isset( $_GET['orderby'] ) ) {
					$icl_translation_filter['order_by'] = filter_var( $_GET['orderby'], FILTER_SANITIZE_STRING );
				}

				if ( isset( $_GET['order'] ) ) {
					$icl_translation_filter['order'] = filter_var( $_GET['order'], FILTER_SANITIZE_STRING );
				}

				if(
						Obj::propOr( '',  'to', $icl_translation_filter) === ''
						&& Obj::propOr( '', 'from', $icl_translation_filter ) === ''
				) {
					$icl_translation_filter['language_pairs'] = $current_translator->language_pairs;
				}
			}
		}
		?>
		<div class="wrap">
			<h2><?php echo __( 'Translations queue', 'wpml-translation-management' ); ?></h2>

			<div class="js-wpml-abort-review-dialog"></div>

			<?php if ( empty( $current_translator->language_pairs ) ) : ?>
				<div class="error below-h2">
					<p><?php _e( 'No translation languages configured for this user.', 'wpml-translation-management' ); ?></p>
				</div>
			<?php endif; ?>
			<?php do_action( 'icl_tm_messages' ); ?>

			<?php if ( ! empty( $current_translator->language_pairs ) ) : ?>

				<div class="alignright">
					<form method="post"
						  name="translation-jobs-filter"
						  id="tm-queue-filter"
						  action="admin.php?page=<?php echo WPML_TM_FOLDER; ?>/menu/translations-queue.php">
						<input type="hidden" name="icl_tm_action" value="ujobs_filter"/>
						<table class="">
							<tbody>
							<tr valign="top">
								<td>
									<select name="filter[type]">
										<option value=""><?php _e( 'All types', 'wpml-translation-management' ); ?></option>
										<?php foreach ( $job_types as $job_type => $job_type_name ) : ?>
											<option value="<?php echo $job_type; ?>"
												<?php
												if ( ! empty( $icl_translation_filter['type'] )
												     && $icl_translation_filter['type']
												        === $job_type ) :

													?>
													selected="selected"<?php endif; ?>><?php echo $job_type_name; ?></option>
										<?php endforeach; ?>
									</select>&nbsp;
									<label>
										<strong><?php _e( 'From', 'wpml-translation-management' ); ?></strong>
										<?php
										if ( 1 < count( $current_translator->language_pairs ) ) {

											$from_select = new WPML_Simple_Language_Selector( $this->sitepress );
											echo $from_select->render(
												array(
													'name'               => 'filter[from]',
													'please_select_text' => __(
														'Any language',
														'wpml-translation-management'
													),
													'style'              => '',
													'languages'          => $langs_from,
													'selected'           => isset( $icl_translation_filter['from'] )
														? $icl_translation_filter['from'] : '',
												)
											);
										} else {
											?>
											<input type="hidden"
												   name="filter[from]"
												   value="<?php echo esc_attr( $lang_from['code'] ); ?>"/>
											<?php
											echo $this->sitepress->get_flag_img( $lang_from['code'] )
											     . ' '
											     . $lang_from['display_name'];
											?>
										<?php } ?>
									</label>&nbsp;
									<label>
										<strong><?php _e( 'To', 'wpml-translation-management' ); ?></strong>
										<?php
										if ( 1 < @count( $langs_to ) ) {
											$to_select = new WPML_Simple_Language_Selector( $this->sitepress );
											echo $to_select->render(
												array(
													'name'               => 'filter[to]',
													'please_select_text' => __(
														'Any language',
														'wpml-translation-management'
													),
													'style'              => '',
													'languages'          => $langs_to,
													'selected'           => isset( $icl_translation_filter['to'] )
														? $icl_translation_filter['to'] : '',
												)
											);
										} else {
											?>
											<input type="hidden" name="filter[to]"
												   value="<?php echo esc_attr( $lang_to['code'] ); ?>"/>
											<?php
											echo $this->sitepress->get_flag_img( $lang_to['code'] ) . ' ' . $lang_to['display_name'];
										}

										$translation_filter_status = null;
										if ( array_key_exists( 'status', $icl_translation_filter ) ) {
											$translation_filter_status = (int) $icl_translation_filter['status'];
										}

										?>
									</label>
									&nbsp;
									<select name="filter[status]">
										<option value=""><?php _e( 'All statuses', 'wpml-translation-management' ) ?></option>
										<option value="<?php echo ICL_TM_COMPLETE; ?>"
											<?php
											if ( $translation_filter_status === ICL_TM_COMPLETE ) :

												?>
												selected="selected"<?php endif; ?>><?php echo TranslationManagement::status2text( ICL_TM_COMPLETE ); ?></option>
										<option value="<?php echo ICL_TM_IN_PROGRESS; ?>"
											<?php
											if ( $translation_filter_status
											     === ICL_TM_IN_PROGRESS ) :

												?>
												selected="selected"<?php endif; ?>><?php echo TranslationManagement::status2text( ICL_TM_IN_PROGRESS ); ?></option>
										<option value="<?php echo ICL_TM_WAITING_FOR_TRANSLATOR; ?>"
											<?php
											if ( $translation_filter_status === ICL_TM_WAITING_FOR_TRANSLATOR ) :

												?>
												selected="selected"<?php endif; ?>><?php _e( 'Available to translate', 'wpml-translation-management' ) ?></option>
										<option value="<?php echo ICL_TM_NEEDS_REVIEW; ?>"
											<?php
											if ( $translation_filter_status === ICL_TM_NEEDS_REVIEW ) :

												?>
												selected="selected"<?php endif; ?>><?php _e( 'Pending review', 'wpml-translation-management' ) ?></option>
									</select>
									&nbsp;
									<input class="button-secondary"
										   type="submit"
										   value="<?php _e( 'Filter', 'wpml-translation-management' ); ?>"/>
								</td>
							</tr>
							</tbody>
						</table>
					</form>
				</div>
				<?php
				$actions = [
					'approve' => __( 'Approve Translation Reviews', 'wpml-translation-management' ),
				];
				if ( Option::getReviewMode() === Option::HOLD_FOR_REVIEW ) {
					$actions['cancel']  = __( 'Cancel Translation Reviews', 'wpml-translation-management' );
				}
				$actions = apply_filters( 'wpml_translation_queue_actions', $actions );
				?>
				<form method="post" name="translation-jobs-action" action="admin.php?page=<?php echo WPML_TM_FOLDER; ?>/menu/translations-queue.php">

				<?php

				$translation_jobs = $this->jobs_repository->getJobs(
					$icl_translation_filter,
					Obj::propOr( 1, 'paged', $_GET ),
					$this->screen_options->get_items_per_page()
				);

				do_action( 'wpml_xliff_select_actions', $actions, 'action', $translation_jobs, $action );

				$translation_queue_pagination = new WPML_UI_Pagination( $this->jobs_repository->getCount( $icl_translation_filter ), $this->screen_options->get_items_per_page() );

				?>
				<?php // pagination - end ?>

				<?php
				$blog_translators = wpml_tm_load_blog_translators();
				$tm_api           = new WPML_TM_API( $blog_translators, $iclTranslationManagement );

				$translation_queue_jobs_model = new WPML_Translations_Queue_Jobs_Model(
					$this->sitepress,
					$iclTranslationManagement,
					$tm_api,
					\WPML\TM\Jobs\Utils\ElementLinkFactory::create(),
					$translation_jobs
				);
				$translation_jobs             = $translation_queue_jobs_model->get();

				$this->show_table( $translation_jobs, count( $actions ) > 0, $job_id );
				?>

				<div id="tm-queue-pagination" class="tablenav">
					<?php $translation_queue_pagination->show(); ?>

					<?php
					do_action( 'wpml_xliff_select_actions', $actions, 'action2', $translation_jobs, $action );

					?>
				</div>
				<?php // pagination - end ?>

					<input type="hidden" name="delete-drafts" value="0"/>
				</form>

				<?php do_action( 'wpml_translation_queue_after_display', $translation_jobs ); ?>

			<?php endif; ?>
		</div>

		<?php
		// Check for any bulk actions
		if ( $action && "-1" !== $action && ! Lst::includes( $action, Obj::keys( $actionCommands ) ) ) {
			do_action( 'wpml_translation_queue_do_actions_export_xliff', $_POST, $action );
		}
	}

	/**
	 * @param $translation_jobs
	 * @param $has_actions
	 * @param $open_job
	 */
	public function show_table( $translation_jobs, $has_actions, $open_job ) {
		?>
	<table class="widefat striped icl-translation-jobs" id="icl-translation-jobs" cellspacing="0"
		   data-string-complete="<?php esc_attr_e( 'Complete', 'wpml-translation-management' ); ?>"
		   data-string-edit="<?php esc_attr_e( 'Edit', 'wpml-translation-management' ); ?>"
	>
		<?php foreach ( array( 'thead', 'tfoot' ) as $element_type ) { ?>
			<<?php echo $element_type; ?>>
			<tr>
				<?php if ( $has_actions ) { ?>
					<td class="manage-column column-cb check-column js-check-all" scope="col">
						<input title="<?php echo esc_attr( $translation_jobs['strings']['check_all'] ); ?>"
							   type="checkbox"/>
					</td>
				<?php } ?>
				<th scope="col" class="cloumn-job_id <?php echo $this->table_sort->get_column_classes( 'job_id' ); ?>">
					<a href="<?php echo $this->table_sort->get_column_url( 'job_id' ); ?>">
						<span><?php echo esc_html( $translation_jobs['strings']['job_id'] ); ?></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col"
					class="column-title"><?php echo esc_html( $translation_jobs['strings']['title'] ); ?></th>
				<th scope="col"
					class="column-type"><?php echo esc_html( $translation_jobs['strings']['type'] ); ?></th>
				<th scope="col"
					class="column-language"><?php echo esc_html( $translation_jobs['strings']['language'] ); ?></th>
				<th scope="col"
					class="column-status"><?php echo esc_html( $translation_jobs['strings']['status'] ); ?></th>
				<th scope="col"
					class="column-deadline <?php echo $this->table_sort->get_column_classes( 'deadline' ); ?>">
					<a href="<?php echo $this->table_sort->get_column_url( 'deadline' ); ?>">
						<span><?php echo esc_html( $translation_jobs['strings']['deadline'] ); ?></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col"
					class="column-actions"></th>
			</tr>
			</<?php echo $element_type; ?>>
		<?php } ?>

		<tbody>
		<?php if ( empty( $translation_jobs['jobs'] ) ) { ?>
			<tr>
				<td colspan="7"
					align="center"><?php _e( 'No translation jobs found', 'wpml-translation-management' ); ?></td>
			</tr>
			<?php
		} else {
			foreach ( $translation_jobs['jobs'] as $job ) {
				?>
				<tr<?php echo $this->get_row_css_attribute( $job ); ?>>
					<?php if ( $has_actions ) { ?>
						<td>
							<input type="checkbox" name="job[<?php echo $job->job_id; ?>]" value="1"/>
						</td>
					<?php } ?>
					<td class="column-job_id"><?php echo $job->job_id; ?></td>
					<td class="column-title">
						<?php echo esc_html( $job->post_title ); ?>
						<div class="row-actions">
							<span class="view"><?php echo $job->tm_post_link; ?></span>
						</div>
					</td>
					<td class="column-type" data-colname=""><?php echo esc_html( $job->post_type ); ?></td>
					<td class="column-languages"><?php echo $job->lang_text_with_flags; ?></td>
					<td class="column-status"><span><i
									class="<?php echo esc_attr( $job->icon ); ?>"></i><?php echo esc_html( $job->status_text ); ?></span>
					</td>
					<td class="column-deadline">
						<?php
						if ( $job->deadline_date ) {
							if ( '0000-00-00 00:00:00' === $job->deadline_date ) {
								$deadline_day = __( 'Not set', 'wpml-translation-management' );
							} else {
								$deadline_day = date( 'Y-m-d', strtotime( $job->deadline_date ) );
							}
							echo esc_html( $deadline_day );
						}
						?>
					</td>
					<td class="column-actions">
                        <div data-job="<?php echo htmlspecialchars( json_encode( $job, JSON_HEX_TAG ) ) ?>"></div>
					</td>
				</tr>
				<?php
			}
		}
		?>
		</tbody>
		</table>
		<?php
	}

	/**
	 * @param stdClass $job
	 *
	 * @return string
	 */
	private function get_row_css_attribute( $job ) {
		$classes = [ 'js-wpml-job-row' ];

		$deadline = Obj::prop( 'deadline_date', $job );
		if ( $deadline !== '0000-00-00 00:00:00' && $deadline !== null
		     && ! Relation::propEq( 'status', ICL_TM_COMPLETE, $job )
		     && ! ReviewStatus::doesJobNeedReview( $job )
		) {
			$deadline_day = date( 'Y-m-d', strtotime( $deadline ) );
			$today        = date( 'Y-m-d' );

			if ( $deadline_day < $today ) {
				$classes[] = 'overdue';
			}
		}

		return ' class="' . esc_attr( implode( ' ', $classes ) ) . '" data-job-id="' . $job->job_id . '"';
	}

	/**
	 * @return bool
	 */
	private function must_open_the_editor() {
		return Obj::prop( 'job_id', $_GET ) > 0 || Obj::prop( 'trid', $_GET ) > 0;
	}

	/**
	 * @return array
	 */
	public static function get_cookie_filters() {
		$filters = [];

		if ( isset( $_COOKIE['wp-translation_ujobs_filter'] ) ) {
			parse_str( $_COOKIE['wp-translation_ujobs_filter'], $filters );

			$filters = filter_var_array(
				$filters,
				[
					'type'   => FILTER_SANITIZE_STRING,
					'from'   => FILTER_SANITIZE_STRING,
					'to'     => FILTER_SANITIZE_STRING,
					'status' => FILTER_SANITIZE_NUMBER_INT,
				]
			);

			$activeLanguageCodes = Obj::keys( Languages::getActive() );
			if (
				$filters['from'] && ! Lst::includes( $filters['from'], $activeLanguageCodes ) ||
				$filters['to'] && ! Lst::includes( $filters['to'], $activeLanguageCodes ) ||
				$filters['type'] && ! Str::startsWith( 'package_', $filters['type'] ) && ! Lst::includes( Str::replace( 'post_', '', $filters['type'] ), PostTypes::getTranslatable() )
			) {
				$filters = [];
			}
		}

		return $filters;
	}
}
