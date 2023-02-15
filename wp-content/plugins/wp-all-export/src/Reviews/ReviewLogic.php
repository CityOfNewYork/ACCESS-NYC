<?php

namespace Wpae\Reviews;


class ReviewLogic
{
    const MAILTO = 'support@wpallimport.com';
    const SUBJECT = 'New Feedback';

    private $wpdb;

    private $exports = false;

    private $pluginName = '';

    private $pluginReviewLink = '';

    private $modalType;

    private $pluginModalText;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }



    public function shouldShowReviewModal()
    {

    	// Only display on the Manage Exports page.
    	if($_GET['page'] !== 'pmxe-admin-manage' || isset($_GET['id']) ){
    		return false;
	    }

        if($this->hasMoreThan4ModalsDismissed()) {
            return false;
        }

        if(!$this->hasExportsThatMatch()) {
            return false;
        }

        if($this->thereWasAModalInTheLast30Days()) {
            return false;
        }

        $modalToShow = $this->getModalToShow();

        $this->modalType = $modalToShow;

        if($modalToShow == 'orders') {
            $this->pluginName = 'WooCommerce Order Export Add-On';
            $this->pluginReviewLink = 'https://wordpress.org/plugins/order-export-for-woocommerce/#reviews';
            $this->pluginModalText ='How was your experience exporting WooCommerce orders with WP All Export?';
            return true;
        }

        if($modalToShow == 'users') {
            $this->pluginName = 'User Export Add-On';
            $this->pluginReviewLink = 'https://wordpress.org/plugins/export-wp-users-xml-csv/#reviews';
            $this->pluginModalText ='How was your experience exporting users with WP All Export?';
            return true;
        }

        if($modalToShow == 'products') {
            $this->pluginName = 'WooCommerce Product Export Add-On';
            $this->pluginReviewLink = 'https://wordpress.org/plugins/product-export-for-woocommerce/#reviews';
            $this->pluginModalText ='How was your experience exporting WooCommerce products with WP All Export?';
            return true;
        }

        if($modalToShow === 'wpae') {

        	if(defined('PMXE_EDITION') && PMXE_EDITION === 'free') {
		        $this->pluginName       = 'WP All Export';
	        }else{
        		$this->pluginName       = 'WP All Export Pro';
	        }
	        $this->pluginReviewLink = 'https://wordpress.org/plugins/wp-all-export/#reviews';
	        $this->pluginModalText  = 'How was your experience exporting records with WP All Export?';
            return true;
        }


        return false;
    }

    public function dismissNotice()
    {
        if (current_user_can('manage_options')) {
            update_option('wpae_modal_review_dismissed', true, false);
            update_option('wpae_modal_review_dismissed_time', time(), false);

            $dismissedModals = get_option('wpae_modal_review_dismissed_modals', []);

            $dismissModalType = esc_html($_POST['modal_type']);
            
            if(!is_array($dismissedModals)) {
                $dismissedModals = [];
            }

            $dismissedModals[] = $dismissModalType;
            update_option('wpae_modal_review_dismissed_modals', $dismissedModals);

            $dismissedTimes = get_option('wpae_modal_review_dismissed_times', 0);
            $dismissedTimes++;

            update_option('wpae_modal_review_dismissed_times', $dismissedTimes, false);

        }
    }

    public function submitFeedback()
    {

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $this->dismissNotice();

		$proInUse = '';

        // Check if WP All Export Pro is installed
	    if( defined('PMXE_EDITION') && PMXE_EDITION === 'paid' ){
			$proInUse .= 'Installed Pro Plugin: WP All Export Pro <br/><br/>';
	    }

	    // Check if the WooCommerce Export Add-On is installed
	    if( class_exists('PMWE_Plugin') and PMWE_EDITION == "paid" ){
		    $proInUse .= 'Installed Pro Plugin: WooCommerce Export Add-On Pro <br/><br/>';
	    }

		// Check if the User Export Add-On is installed.
	    if ( class_exists('PMUE_Plugin') and PMUE_EDITION == "paid"){
		    $proInUse .= 'Installed Pro Plugin: User Export Add-On Pro <br/><br/>';
	    }

	    // Prettify the reviewed plugin.
	    $plugin = 'Plugin Reviewed: ';
	    switch( $_POST['plugin'] ){
		    case 'wpae':
		    	$plugin .= 'WP All Export';
		    	break;
		    case 'orders':
		    	$plugin .= 'Order Export Add-On';
		    	break;

		    case 'users':
		    	$plugin .= 'User Export Add-On';
		    	break;

		    case 'products':
		    	$plugin .= 'Product Export Add-On';
		    	break;
	    }

        $message = $plugin . " <br/><br/>" . $proInUse . wp_kses_post(stripslashes(wpautop($_POST['message'])));
        wp_mail( self::MAILTO, self::SUBJECT, $message, $headers );
    }


    public function getPluginName() {
        return $this->pluginName;
    }

    public function getReviewLink() {
        return $this->pluginReviewLink;
    }

    public function getModalType() {
        return $this->modalType;
    }

    public function getModalText() {
        return $this->pluginModalText;
    }

    private function getModalToShow()
    {
        $exportCount = [
            'users' => 0,
            'products' => 0,
            'orders' => 0
        ];

        // Only show modal for export types that have been on the site for at least two days.
        $exportOlderThanTwoDays = [
        	'users' => false,
	        'products' => false,
	        'orders' => false
        ];

        $exports = $this->getExports();

        // Go through the exports and find the export count for each export type
        foreach($exports as $export) {
            $options = maybe_unserialize($export->options);

            if ($options) {

                $cpt = $options['cpt'];

                if (!is_array($cpt)) {
                    $cpt = [$cpt];
                }

                // Is user export
                if (in_array('users', $cpt) || in_array('shop_customer', $cpt)) {
                    $exportCount['users']++;
                    if( strtotime($export->created_at) < time() - 2 * 24 * 3600 ){
                    	$exportOlderThanTwoDays['users'] = true;
                    }
                }

                // Is product export
                if (in_array('product', $cpt)) {
                    $exportCount['products']++;
	                if( strtotime($export->created_at) < time() - 2 * 24 * 3600 ){
		                $exportOlderThanTwoDays['products'] = true;
	                }
                }

                // Is order export
                if (in_array('shop_order', $cpt)) {
                    $exportCount['orders']++;
	                if( strtotime($export->created_at) < time() - 2 * 24 * 3600 ){
		                $exportOlderThanTwoDays['orders'] = true;
	                }
                }
            }
        }

        // Get the plugin with most exports
        $max = 0;
        $plugin = false;

        $dismissedModals = get_option('wpae_modal_review_dismissed_modals', []);

        foreach($exportCount as $key => $exports) {
            if($exports > $max && !in_array($key, $dismissedModals) && $exportOlderThanTwoDays[$key]) {
                $plugin = $key;
                $max = $exports;
            }
        }

        if(!$plugin && !in_array('wpae', $dismissedModals)) {
            $plugin = 'wpae';
        }

        return $plugin;
    }


    private function thereWasAModalInTheLast30Days()
    {
        $lastModalDismissed = get_option('wpae_modal_review_dismissed_time');

        if( $lastModalDismissed > time() - 30 * 24 * 3600 ) {

            return true;
        }

        return false;
    }

    private function hasExportsThatMatch(){

        $exportsOlderThan48Hours = $this->wpdb->get_results("SELECT * FROM " . $this->wpdb->prefix . "pmxe_exports WHERE created_at < NOW() - INTERVAL 2 DAY AND created_at <> '0000-00-00 00:00:00' ");

        $exports = $this->getExports();

        return (count($exportsOlderThan48Hours) >= 1 && count($exports) >= 5 );
    }

    /**
     * @return exports[]
     */
    private function getExports()
    {
        if (!$this->exports) {
            $this->exports = $this->wpdb->get_results("SELECT * FROM " . $this->wpdb->prefix . "pmxe_exports");
        }

        return $this->exports;
    }

    private function hasMoreThan4ModalsDismissed()
    {
        $dismissedTimes = get_option('wpae_modal_review_dismissed_times', 0);

        if($dismissedTimes > 4) {
            return true;
        }

        return false;
    }
}