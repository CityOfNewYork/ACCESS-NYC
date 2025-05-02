<?php
namespace WPML\StringTranslation\UserInterface\RestApi;

use WPML\Rest\Adaptor;
use WPML\StringTranslation\Application\StringCore\Service\StringsService;
use WPML\StringTranslation\Application\StringHtml\Service\HtmlStringsService;
use WPML\StringTranslation\Application\StringGettext\Service\GettextStringsService;
use WPML\StringTranslation\Application\StringGettext\Repository\QueueRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\FrontendQueueRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\Utilities\Lock;
use function WPML\Container\make;

class ProcessStringsQueueApiController extends AbstractController {

	/** @var StringsService */
	private $stringsService;

	/** @var HtmlStringsService */
	private $htmlStringsService;

	/** @var GettextStringsService */
	private $gettextStringsService;

	/** @var QueueRepositoryInterface */
	private $queueRepository;

	/** @var FrontendQueueRepositoryInterface */
	private $frontendQueueRepository;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	public function __construct(
		Adaptor                          $adaptor,
		StringsService                   $stringsService,
		HtmlStringsService               $htmlStringsService,
		GettextStringsService            $gettextStringsService,
		QueueRepositoryInterface         $queueRepository,
		FrontendQueueRepositoryInterface $frontendQueueRepository,
		SettingsRepositoryInterface      $settingsRepository
	) {
		parent::__construct( $adaptor );
		$this->stringsService          = $stringsService;
		$this->htmlStringsService      = $htmlStringsService;
		$this->gettextStringsService   = $gettextStringsService;
		$this->queueRepository         = $queueRepository;
		$this->frontendQueueRepository = $frontendQueueRepository;
		$this->settingsRepository      = $settingsRepository;
	}

	/**
	 * @return array
	 */
	function get_routes() {
		return [
			[
				'route' => 'strings/processstringsqueue',
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'post' ],
				]
			],
		];
	}

	/**
	 * @return array
	 */
	public function post( \WP_REST_Request $request ) {
		if ( ! $this->gettextStringsService->isAutoregisterEnabled() ) {
			return [
				'wasProcessed' => false,
			];
		}

		$lock    = make( Lock::class, [ ':name' => 'processstringsqueue' ] );
		$hasLock = $lock->create( 60 );

		if ( ! $hasLock ) {
			return [
				'wasProcessed' => false,
			];
		}

		$hasPendingGettextStrings = array_sum(
			array_map(
				'count',
				$this->queueRepository->loadPendingStrings()
			)
		) > 0;
		$hasPendingHtmlStrings = array_sum(
			array_map(
				function( $gettextStringsByUrl ) {
					return count( $gettextStringsByUrl->getStrings() );
				},
				$this->frontendQueueRepository->get()
			)
		);

		if ( $hasPendingGettextStrings ) {
			$this->stringsService->maybeProcessQueue();
		}
		if ( $hasPendingHtmlStrings ) {
			$this->htmlStringsService->maybeProcessFrontendGettextStringsQueue();
		}

		$wasProcessed = $hasPendingGettextStrings || $hasPendingHtmlStrings;
		if ( $this->settingsRepository->wereNewTranslationsLoaded() ) {
			$wasProcessed = true;
			$this->settingsRepository->unsetNewTranslationsWereLoadedSetting();
		}
		$lock->release();

		return [
			'wasProcessed' => $wasProcessed,
		];
	}
}