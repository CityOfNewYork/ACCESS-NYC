<?php

function pmxe_wp_loaded() {

	@ini_set("max_input_time", PMXE_Plugin::getInstance()->getOption('max_input_time'));

	if ( ! empty($_GET['action']) && ! empty($_GET['export_id']) && (!empty($_GET['export_hash']) || !empty($_GET['security_token'])))
	{
        pmxe_set_max_execution_time();

		if(empty($_GET['export_hash'])) {
			$securityToken = $_GET['security_token'];
		} else {
			$securityToken = $_GET['export_hash'];
		}

		$cron_job_key = PMXE_Plugin::getInstance()->getOption('cron_job_key');

		if ( $securityToken == substr(md5($cron_job_key . $_GET['export_id']), 0, 16) )
		{
			$export = new PMXE_Export_Record();

			$export->getById($_GET['export_id']);

			if ( ! $export->isEmpty())
			{
				switch ($_GET['action'])
				{
					case 'get_data':

						if ( ! empty($export->options['current_filepath']) and @file_exists($export->options['current_filepath']))
						{
							$filepath = $export->options['current_filepath'];
						}
						else
						{
							$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

							if ( ! $is_secure_import)
							{
								$filepath = get_attached_file($export->attch_id);
							}
							else
							{
								$filepath = wp_all_export_get_absolute_path($export->options['filepath']);
							}
						}

						if ( ! empty($_GET['part']) and is_numeric($_GET['part'])) $filepath = str_replace(basename($filepath), str_replace('.' . $export->options['export_to'], '', basename($filepath)) . '-' . $_GET['part'] . '.' . $export->options['export_to'], $filepath);

						break;

					case 'get_bundle':

						$filepath = wp_all_export_get_absolute_path($export->options['bundlepath']);

						break;
				}

				if (file_exists($filepath))
				{
					$uploads  = wp_upload_dir();
					$fileurl = $uploads['baseurl'] . str_replace($uploads['basedir'], '', str_replace(basename($filepath), rawurlencode(basename($filepath)), $filepath));

					if($export['options']['export_to'] == XmlExportEngine::EXPORT_TYPE_XML && $export['options']['xml_template_type'] == XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS) {

						// If we are doing a google merchants export, send the file as a download.
						header("Content-type: text/plain");
						header("Content-Disposition: attachment; filename=".basename($filepath));
						readfile($filepath);

						die;
					}

                    if(apply_filters('wp_all_export_no_cache', false)) {

                        // If we are doing a google merchants export, send the file as a download.
                        header("Content-type: " . mime_content_type($filepath));
                        header("Content-Disposition: attachment; filename=" . basename($filepath));
                        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                        header("Cache-Control: post-check=0, pre-check=0", false);
                        header("Pragma: no-cache");

                        readfile($filepath);

                        die;
                    }

					$fileurl = str_replace( "\\", "/", $fileurl );

					wp_redirect($fileurl);
				}
				else
				{
					wp_send_json(array(
						'status'     => 403,
						'message'    => __('File doesn\'t exist', 'wp_all_export_plugin')
					));
				}
			}
		}
		else
		{
			wp_send_json(array(
				'status'     => 403,
				'message'    => __('Export hash is not valid.', 'wp_all_export_plugin')
			));
		}
	}

    if(isset($_GET['action']) && $_GET['action'] == 'wpae_public_api') {

        pmxe_set_max_execution_time();

        $router = new \Wpae\Http\Router();
        $router->route($_GET['q'], false);
    }
}

if(!function_exists('pmxe_set_max_execution_time')) {
    function pmxe_set_max_execution_time()
    {
        @ini_set("max_input_time", PMXE_Plugin::getInstance()->getOption('max_input_time'));

        $maxExecutionTime = PMXE_Plugin::getInstance()->getOption('max_execution_time');
        if ($maxExecutionTime == -1) {
            $maxExecutionTime = 0;
        }

        @ini_set("max_execution_time", $maxExecutionTime);
    }
}