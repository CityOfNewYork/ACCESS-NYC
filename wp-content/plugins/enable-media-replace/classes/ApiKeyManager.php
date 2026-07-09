<?php
namespace EnableMediaReplace;

if (! defined('ABSPATH')) {
    exit;
}

class ApiKeyManager
{
    protected static $instance;

    const OPTION_NAME  = 'emr_shortpixel_api_key';
    const API_ENDPOINT = 'https://api.shortpixel.com/v2/api-status.php';

    protected $apiKey = '';

    protected function __construct()
    {
        $this->apiKey = (string) get_option(self::OPTION_NAME, '');
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new ApiKeyManager();
        }
        return self::$instance;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function hasApiKey()
    {
        return ! empty($this->apiKey);
    }

    public function getMaskedKey()
    {
        if (empty($this->apiKey)) {
            return __('Not set', 'enable-media-replace');
        }
        $len = strlen($this->apiKey);
        return substr($this->apiKey, 0, 4) . str_repeat('*', $len - 8) . substr($this->apiKey, -4);
    }

    /**
     * Validate a new key and save it if valid with Unlimited plan.
     * Returns ['success' => bool, 'message' => string].
     */
    public function saveAndVerify($key)
    {
        $key = sanitize_text_field(trim($key));

        if (empty($key)) {
            return [
                'success' => false,
                'message' => __('Please enter an API Key.', 'enable-media-replace'),
            ];
        }

        if (strlen($key) !== 20) {
            return [
                'success' => false,
                'message' => __('The API Key must be exactly 20 characters.', 'enable-media-replace'),
            ];
        }

        $result = $this->callApi($key, true);

        if (! $result['valid']) {
            return ['success' => false, 'message' => $result['message']];
        }

        if (! $result['unlimited']) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Please <a href="%s" target="_blank" rel="noopener">upgrade</a> to the Unlimited or Unlimited AI plan to unlock unlimited background removal.', 'enable-media-replace'),
                    'https://shortpixel.com/pricing'
                ),
            ];
        }

        $this->apiKey = $key;
        update_option(self::OPTION_NAME, $key);

        return [
            'success' => true,
            'message' => __('API Key saved and verified successfully. You now have unlimited background removal.', 'enable-media-replace'),
        ];
    }

    public function deleteKey()
    {
        $this->apiKey = '';
        delete_option(self::OPTION_NAME);
    }

    /**
     * Check whether the stored key currently has an Unlimited plan.
     * Always calls the API — no caching — so plan changes are caught immediately.
     */
    public function verifyUnlimitedPlan()
    {
        if (empty($this->apiKey)) {
            return false;
        }

        $result = $this->callApi($this->apiKey, false);
        return $result['valid'] && $result['unlimited'];
    }

    private function callApi($key, $validate = false)
    {
        $default = [
            'valid'     => false,
            'unlimited' => false,
            'message'   => __('API Key could not be validated. Please check your internet connection and try again.', 'enable-media-replace'),
        ];

        $body = [
            'key'  => $key,
            'host' => parse_url(get_site_url(), PHP_URL_HOST),
        ];

        if ($validate) {
            $body['DomainCheck'] = get_site_url();
            $body['Info']        = get_bloginfo('version') . '|' . phpversion();
        }

        $args     = ['timeout' => 15, 'body' => $body];
        $response = wp_remote_post(self::API_ENDPOINT, $args);

        if (is_wp_error($response)) {
            $httpUrl  = str_replace('https://', 'http://', self::API_ENDPOINT);
            $response = wp_remote_post($httpUrl, $args);
            if (is_wp_error($response)) {
                return $default;
            }
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return $default;
        }

        $data = json_decode(wp_remote_retrieve_body($response));

        if (empty($data)) {
            return $default;
        }

        if ($data->Status->Code != 2) {
            $default['message'] = __('The API Key is not correct. Please try again.', 'enable-media-replace');
            return $default;
        }

        return [
            'valid'     => true,
            'unlimited' => property_exists($data, 'Unlimited') && $data->Unlimited == 'true',
            'message'   => '',
        ];
    }
}