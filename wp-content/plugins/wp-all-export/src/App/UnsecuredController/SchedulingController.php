<?php

namespace Wpae\App\UnsecuredController;


use Wpae\App\Service\Addons\AddonNotFoundException;
use Wpae\App\Service\Addons\AddonService;
use Wpae\Controller\BaseController;
use Wpae\Http\Request;
use Wpae\Scheduling\Export;
use Wpae\Http\JsonResponse;

class SchedulingController extends BaseController
{
    /** Scheduling API Version */
    const VERSION = 1;

    /** @var Export */
    private $scheduledExportService;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->scheduledExportService = new Export();
    }

    public function triggerAction(Request $request)
    {
        if (!$this->isRequestValid()) {
            return new JsonResponse(array('message' => 'Export hash is invalid'), 401);
        }

        $exportId = intval($request->get('export_id'));

        $export = new \PMXE_Export_Record();
        $export->getById($exportId);

        $this->disableExportsThatDontHaveAddon($export);


        if ($export->isEmpty()) {
            return new JsonResponse(array('message' => 'Export not found'), 404);
        }

        if ((int)$export->executing) {
            return new JsonResponse(array("message" => "Export #" . $export->id . " is currently in manually process. Request skipped."), 409);
        }
        if ($export->processing and !$export->triggered) {
            return new JsonResponse(array("message" => "Export #" . $export->id . " currently in process. Request skipped."), 409);

        }
        if (!$export->processing and $export->triggered) {
            return new JsonResponse(array("message" => "Export #" . $export->id . " already triggered. Request skipped."), 409);
        }

        if (!$export->processing and !$export->triggered) {
            $this->scheduledExportService->trigger($export);

            return new JsonResponse(array('message' => "#" . $export->id . " Cron job triggered."));
        }

        return new JsonResponse(array("message" => "Can't process"), 500);
    }

    public function processAction(Request $request)
    {
        if (!$this->isRequestValid()) {
            return new JsonResponse(array('message' => 'Export hash is invalid'), 401);
        }

        $exportId = intval($request->get('export_id'));

        $export = new \PMXE_Export_Record();
        $export->getById($exportId);

        $this->disableExportsThatDontHaveAddon($export);

        if ($export->isEmpty()) {
            return new JsonResponse(array('message' => 'Export not found'), 404);
        }

        $logger = function($m) {
            echo "<p>" . wp_kses_post($m) . "</p>\\n";
        };

        if ($export->processing == 1 and (time() - strtotime($export->registered_on)) > 120) {
            // it means processor crashed, so it will reset processing to false, and terminate. Then next run it will work normally.
            $export->set(array(
                'processing' => 0
            ))->update();
        }

        // start execution imports that is in the cron process
        if (!(int)$export->triggered) {
            if (!empty($export->parent_id) or empty($queue_exports)) {
                return new JsonResponse(array("message" => 'Export #' . $exportId . ' is not triggered. Request skipped.'), 400);
            }
        } elseif ((int)$export->executing) {
            return new JsonResponse(array('message' => 'Export #' . $exportId . ' is currently in manually process. Request skipped.'), 409);
        } elseif ((int)$export->triggered and !(int)$export->processing) {

            try {
                $export->set(array('canceled' => 0))->execute($logger, true);
            } catch (AddonNotFoundException $e) {
                die($e->getMessage());
            }
            if (!(int)$export->triggered and !(int)$export->processing) {
                $this->scheduledExportService->process($export);
                return new JsonResponse(array('Export #' . $exportId . ' complete'), 201);
            } else {
                return new JsonResponse(array('message' => 'Records Processed ' . (int)$export->exported . '.'));
            }

        } else {
            return new JsonResponse(array('message' => 'Export #' . $exportId . ' already processing. Request skipped.'), 409);
        }

        return new JsonResponse(array("message" => "Can't process"), 500);
    }

    public function versionAction()
    {
        return new JsonResponse(array('version' => self::VERSION));
    }

    /**
     * @return bool
     */
    private function isRequestValid()
    {
        $cron_job_key = \PMXE_Plugin::getInstance()->getOption('cron_job_key');
        return
            !empty($cron_job_key) and
            !empty($_GET['export_id']) and
            !empty($_GET['export_key']) and
            $_GET['export_key'] == $cron_job_key;
    }

    /**
     * @param $export
     */
    private function disableExportsThatDontHaveAddon($export)
    {
        $cpt = $export->options['cpt'];
        if (!is_array($cpt)) {
            $cpt = array($cpt);
        }

        $addons = new AddonService();

        if (
            ((in_array('users', $cpt) || in_array('shop_customer', $cpt)) && !$addons->isUserAddonActive())
            ||
            ($export->options['export_type'] == 'advanced' && $export->options['wp_query_selector'] == 'wp_user_query' && !$addons->isUserAddonActive())
        ) {
            die(\__('The User Export Add-On Pro is required to run this export. You can download the add-on here: <a href="http://www.wpallimport.com/portal/" target="_blank">http://www.wpallimport.com/portal/</a>', \PMXE_Plugin::LANGUAGE_DOMAIN));
        }

	    if (
		    (( (in_array('product', $cpt) && \class_exists('WooCommerce') && !$addons->isWooCommerceProductAddonActive()) || (in_array('shop_order', $cpt) && !$addons->isWooCommerceOrderAddonActive()) || in_array('shop_coupon', $cpt) || in_array('shop_review', $cpt) ) && !$addons->isWooCommerceAddonActive())
		    ||
		    ($export->options['export_type'] == 'advanced' && in_array($export->options['exportquery']->query['post_type'], array('shop_coupon')) && !$addons->isWooCommerceAddonActive())
		    ||
		    ($export->options['export_type'] == 'advanced' && in_array($export->options['exportquery']->query['post_type'], array('shop_order')) && !$addons->isWooCommerceAddonActive() && !$addons->isWooCommerceOrderAddonActive())
		    ||
		    ($export->options['export_type'] == 'advanced' && in_array($export->options['exportquery']->query['post_type'], array(array('product', 'product_variation'), )) && !$addons->isWooCommerceAddonActive() && !$addons->isWooCommerceProductAddonActive())
	    ) {
            die(\__('The WooCommerce Export Add-On Pro is required to run this export. You can download the add-on here: <a href="http://www.wpallimport.com/portal/" target="_blank">http://www.wpallimport.com/portal/</a>', \PMXE_Plugin::LANGUAGE_DOMAIN));
        }

        if(in_array('acf', $export->options['cc_type']) && !$addons->isAcfAddonActive()) {
            die(\__('The ACF Export Add-On Pro is required to run this export. You can download the add-on here: <a href="http://www.wpallimport.com/portal/" target="_blank">http://www.wpallimport.com/portal/</a>', \PMXE_Plugin::LANGUAGE_DOMAIN));
        }

    }

}