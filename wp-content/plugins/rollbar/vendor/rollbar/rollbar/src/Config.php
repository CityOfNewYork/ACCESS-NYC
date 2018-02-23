<?php namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;

if (!defined('ROLLBAR_INCLUDED_ERRNO_BITMASK')) {
    define(
        'ROLLBAR_INCLUDED_ERRNO_BITMASK',
        E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR
    );
}

class Config
{
    private $accessToken;
    /**
     * @var DataBuilder
     */
    private $dataBuilder;
    private $configArray;
    
    /**
     * @var LevelFactory
     */
    private $levelFactory;
    
    /**
     * @var Utilities
     */
    private $utilities;
    
    /**
     * @var TransformerInterface
     */
    private $transformer;
    /**
     * @var FilterInterface
     */
    private $filter;
    private $minimumLevel;
    /**
     * @var ResponseHandlerInterface
     */
    private $responseHandler;
    /**
     * @var \Rollbar\Senders\SenderInterface
     */
    private $sender;
    private $reportSuppressed;
    /**
     * @var Scrubber
     */
    private $scrubber;

    private $batched = false;
    private $batch_size = 50;

    private $custom = array();
    /**
     * @var callable
     */
    private $checkIgnore;
    private $error_sample_rates = array();
    private $exception_sample_rates = array();
    private $mt_randmax;

    private $included_errno = ROLLBAR_INCLUDED_ERRNO_BITMASK;
    private $use_error_reporting = false;
    
    /**
     * @var boolean Should debug_backtrace() data be sent with string messages
     * sent through RollbarLogger::log()
     */
    private $sendMessageTrace = false;

    public function __construct(array $configArray)
    {
        $this->levelFactory = new LevelFactory();
        $this->utilities = new Utilities();
        
        $this->updateConfig($configArray);

        if (isset($configArray['error_sample_rates'])) {
            $this->error_sample_rates = $configArray['error_sample_rates'];
        }
        
        if (isset($configArray['exception_sample_rates'])) {
            $this->exception_sample_rates = $configArray['exception_sample_rates'];
        }

        $levels = array(E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING,
            E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR);
        // PHP 5.3.0
        if (defined('E_DEPRECATED')) {
            $levels = array_merge($levels, array(E_DEPRECATED, E_USER_DEPRECATED));
        }
        $curr = 1;
        for ($i = 0, $num = count($levels); $i < $num; $i++) {
            $level = $levels[$i];
            if (!isset($this->error_sample_rates[$level])) {
                $this->error_sample_rates[$level] = $curr;
            }
        }
        $this->mt_randmax = mt_getrandmax();
    }

    public function configure($config)
    {
        $this->updateConfig($this->extend($config));
    }

    public function extend($config)
    {
        return array_replace_recursive(array(), $this->configArray, $config);
    }

    public function getConfigArray()
    {
        return $this->configArray;
    }

    protected function updateConfig($config)
    {
        $this->configArray = $config;

        $this->setAccessToken($config);
        $this->setDataBuilder($config);
        $this->setTransformer($config);
        $this->setMinimumLevel($config);
        $this->setReportSuppressed($config);
        $this->setFilters($config);
        $this->setSender($config);
        $this->setScrubber($config);
        $this->setBatched($config);
        $this->setBatchSize($config);
        $this->setCustom($config);
        $this->setResponseHandler($config);
        $this->setCheckIgnoreFunction($config);
        $this->setSendMessageTrace($config);

        if (isset($config['included_errno'])) {
            $this->included_errno = $config['included_errno'];
        }

        if (isset($config['use_error_reporting'])) {
            $this->use_error_reporting = $config['use_error_reporting'];
        }
    }

    private function setAccessToken($config)
    {
        if (isset($_ENV['ROLLBAR_ACCESS_TOKEN']) && !isset($config['access_token'])) {
            $config['access_token'] = $_ENV['ROLLBAR_ACCESS_TOKEN'];
        }
        $this->utilities->validateString($config['access_token'], "config['access_token']", 32, false);
        $this->accessToken = $config['access_token'];
    }

    private function setDataBuilder($config)
    {
        if (!isset($config['levelFactory'])) {
            $config['levelFactory'] = $this->levelFactory;
        }
        
        if (!isset($config['utilities'])) {
            $config['utilities'] = $this->utilities;
        }
        
        $exp = "Rollbar\DataBuilderInterface";
        $def = "Rollbar\DataBuilder";
        $this->setupWithOptions($config, "dataBuilder", $exp, $def, true);
    }

    private function setTransformer($config)
    {
        $expected = "Rollbar\TransformerInterface";
        $this->setupWithOptions($config, "transformer", $expected);
    }

    private function setMinimumLevel($config)
    {
        $this->minimumLevel = 0;
        if (empty($config['minimumLevel'])) {
            $this->minimumLevel = 0;
        } elseif ($config['minimumLevel'] instanceof Level) {
            $this->minimumLevel = $config['minimumLevel']->toInt();
        } elseif (is_string($config['minimumLevel'])) {
            $level = $this->levelFactory->fromName($config['minimumLevel']);
            if ($level !== null) {
                $this->minimumLevel = $level->toInt();
            }
        } elseif (is_int($config['minimumLevel'])) {
            $this->minimumLevel = $config['minimumLevel'];
        }
    }

    private function setReportSuppressed($config)
    {
        $this->reportSuppressed = isset($config['reportSuppressed']) && $config['reportSuppressed'];
        if (!isset($this->reportSuppressed)) {
            $this->reportSuppressed = isset($config['report_suppressed']) && $config['report_suppressed'];
        }
    }

    private function setFilters($config)
    {
        $this->setupWithOptions($config, "filter", "Rollbar\FilterInterface");
    }

    private function setSender($config)
    {
        $expected = "Rollbar\Senders\SenderInterface";
        $default = "Rollbar\Senders\CurlSender";

        $this->setTransportOptions($config);
        $default = $this->setAgentSenderOptions($config, $default);
        $default = $this->setFluentSenderOptions($config, $default);

        $this->setupWithOptions($config, "sender", $expected, $default);
    }

    private function setScrubber($config)
    {
        $exp = "Rollbar\ScrubberInterface";
        $def = "Rollbar\Scrubber";
        $this->setupWithOptions($config, "scrubber", $exp, $def, true);
    }

    private function setBatched($config)
    {
        if (array_key_exists('batched', $config)) {
            $this->batched = $config['batched'];
        }
    }

    private function setBatchSize($config)
    {
        if (array_key_exists('batch_size', $config)) {
            $this->batch_size = $config['batch_size'];
        }
    }

    public function setCustom($config)
    {
        $this->dataBuilder->setCustom($config);
    }
    
    public function addCustom($key, $data)
    {
        $this->dataBuilder->addCustom($key, $data);
    }
    
    public function removeCustom($key)
    {
        $this->dataBuilder->removeCustom($key);
    }
    
    public function getCustom()
    {
        return $this->dataBuilder->getCustom();
    }

    private function setTransportOptions(&$config)
    {
        if (array_key_exists('base_api_url', $config)) {
            $config['senderOptions']['endpoint'] = $config['base_api_url'] . 'item/';
        }

        if (array_key_exists('endpoint', $config)) {
            $config['senderOptions']['endpoint'] = $config['endpoint'] . 'item/';
        }

        if (array_key_exists('timeout', $config)) {
            $config['senderOptions']['timeout'] = $config['timeout'];
        }

        if (array_key_exists('proxy', $config)) {
            $config['senderOptions']['proxy'] = $config['proxy'];
        }
    }

    private function setAgentSenderOptions(&$config, $default)
    {
        if (!array_key_exists('handler', $config) || $config['handler'] != 'agent') {
            return $default;
        }
        $default = "Rollbar\Senders\AgentSender";
        if (array_key_exists('agent_log_location', $config)) {
            $config['senderOptions'] = array(
                'agentLogLocation' => $config['agent_log_location']
            );
        }
        return $default;
    }

    private function setFluentSenderOptions(&$config, $default)
    {
        if (!isset($config['handler']) || $config['handler'] != 'fluent') {
            return $default;
        }
        $default = "Rollbar\Senders\FluentSender";

        if (isset($config['fluent_host'])) {
            $config['senderOptions']['fluentHost'] = $config['fluent_host'];
        }

        if (isset($config['fluent_port'])) {
            $config['senderOptions']['fluentPort'] = $config['fluent_port'];
        }

        if (isset($config['fluent_tag'])) {
            $config['senderOptions']['fluentTag'] = $config['fluent_tag'];
        }

        return $default;
    }

    private function setResponseHandler($config)
    {
        $this->setupWithOptions($config, "responseHandler", "Rollbar\ResponseHandlerInterface");
    }

    private function setCheckIgnoreFunction($config)
    {
        if (!isset($config['checkIgnore'])) {
            return;
        }

        $this->checkIgnore = $config['checkIgnore'];
    }

    private function setSendMessageTrace($config)
    {
        if (!isset($config['send_message_trace'])) {
            return;
        }

        $this->sendMessageTrace = $config['send_message_trace'];
    }

    /**
     * Allows setting up configuration options that might be specified by class
     * name. Any interface used with `setupWithOptions` should be constructed
     * with a single parameter: an associative array with the config options.
     * It is assumed that it will be in the configuration as a sibling to the
     * key the class is named in. The options should have the same key as the
     * classname, but with 'Options' appended. E.g:
     * ```array(
     *   "sender" => "MySender",
     *   "senderOptions" => array(
     *     "speed" => 11,
     *     "protocol" => "First Contact"
     *   )
     * );```
     * Will be initialized as if you'd used:
     * `new MySender(array("speed"=>11,"protocol"=>"First Contact"));`
     * You can also just pass an instance in directly. (In which case options
     * are ignored)
     * @param $config
     * @param $keyName
     * @param $expectedType
     * @param mixed $defaultClass
     * @param bool $passWholeConfig
     */
    protected function setupWithOptions(
        $config,
        $keyName,
        $expectedType,
        $defaultClass = null,
        $passWholeConfig = false
    ) {

        $$keyName = isset($config[$keyName]) ? $config[$keyName] : null;

        if (is_null($defaultClass) && is_null($$keyName)) {
            return;
        }

        if (is_null($$keyName)) {
            $$keyName = $defaultClass;
        }
        if (is_string($$keyName)) {
            if ($passWholeConfig) {
                $options = $config;
            } else {
                $options = isset($config[$keyName . "Options"]) ?
                            $config[$keyName . "Options"] :
                            array();
            }
            $this->$keyName = new $$keyName($options);
        } else {
            $this->$keyName = $$keyName;
        }

        if (!$this->$keyName instanceof $expectedType) {
            throw new \InvalidArgumentException(
                "$keyName must be a $expectedType"
            );
        }
    }

    public function getRollbarData($level, $toLog, $context)
    {
        return $this->dataBuilder->makeData($level, $toLog, $context);
    }

    public function getDataBuilder()
    {
        return $this->dataBuilder;
    }
    
    public function getLevelFactory()
    {
        return $this->levelFactory;
    }
    
    public function getSender()
    {
        return $this->sender;
    }

    public function getScrubber()
    {
        return $this->scrubber;
    }

    public function getBatched()
    {
        return $this->batched;
    }

    public function getBatchSize()
    {
        return $this->batch_size;
    }

    /**
     * @param Payload $payload
     * @param Level $level
     * @param \Exception | \Throwable $toLog
     * @param array $context
     * @return Payload
     */
    public function transform($payload, $level, $toLog, $context)
    {
        if (count($this->custom) > 0) {
            $data = $payload->getData();
            $custom = $data->getCustom();
            $custom = array_merge(array(), $this->custom, (array)$custom);
            $data->setCustom($custom);
            $payload->setData($data);
        }
        if (is_null($this->transformer)) {
            return $payload;
        }
        return $this->transformer->transform($payload, $level, $toLog, $context);
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getSendMessageTrace()
    {
        return $this->sendMessageTrace;
    }

    public function checkIgnored($payload, $accessToken, $toLog, $isUncaught)
    {
        if (isset($this->checkIgnore)) {
            try {
                if (call_user_func($this->checkIgnore, $isUncaught, $toLog, $payload)) {
                    return true;
                }
            } catch (Exception $exception) {
                // We should log that we are removing the custom checkIgnore
                $this->checkIgnore = null;
            }
        }
        
        if ($this->payloadLevelTooLow($payload)) {
            return true;
        }

        if (!is_null($this->filter)) {
            return $this->filter->shouldSend($payload, $accessToken);
        }

        return false;
    }

    public function internalCheckIgnored($level, $toLog)
    {
        if ($this->shouldSuppress()) {
            return true;
        }

        if ($this->levelTooLow($this->levelFactory->fromName($level))) {
            return true;
        }

        if ($toLog instanceof ErrorWrapper) {
            return $this->shouldIgnoreErrorWrapper($toLog);
        }
        
        if ($toLog instanceof \Exception) {
            return $this->shouldIgnoreException($toLog);
        }
        
        return false;
    }

    /**
     * Check if the error should be ignored due to `included_errno` config,
     * `use_error_reporting` config or `error_sample_rates` config.
     *
     * @param errno
     *
     * @return bool
     */
    public function shouldIgnoreError($errno)
    {
        if ($this->use_error_reporting && ($errno & error_reporting()) === 0) {
            // ignore due to error_reporting level
            return true;
        }

        if ($this->included_errno != -1 && ($errno & $this->included_errno) != $errno) {
            // ignore
            return true;
        }

        if (isset($this->error_sample_rates[$errno])) {
            // get a float in the range [0, 1)
            // mt_rand() is inclusive, so add 1 to mt_randmax
            $float_rand = mt_rand() / ($this->mt_randmax + 1);
            if ($float_rand > $this->error_sample_rates[$errno]) {
                // skip
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if the error should be ignored due to `included_errno` config,
     * `use_error_reporting` config or `error_sample_rates` config.
     *
     * @param \Rollbar\ErrorWrapper $toLog
     *
     * @return bool
     */
    protected function shouldIgnoreErrorWrapper(ErrorWrapper $toLog)
    {
        return $this->shouldIgnoreError($toLog->errorLevel);
    }
    
    /**
     * Check if the exception should be ignored due to configured exception
     * sample rates.
     *
     * @param \Exception $toLog
     *
     * @return bool
     */
    protected function shouldIgnoreException(\Exception $toLog)
    {
        // get a float in the range [0, 1)
        // mt_rand() is inclusive, so add 1 to mt_randmax
        $floatRand = mt_rand() / ($this->mt_randmax + 1);
        if ($floatRand > $this->exceptionSampleRate($toLog)) {
            // skip
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate what's the chance of logging this exception according to
     * exception sampling.
     *
     * @param \Exception $toLog
     *
     * @return float
     */
    public function exceptionSampleRate(\Exception $toLog)
    {
        $sampleRate = 1.0;
        if (count($this->exception_sample_rates) == 0) {
            return $sampleRate;
        }
        
        $exceptionClasses = array();
        
        $class = get_class($toLog);
        while ($class) {
            $exceptionClasses []= $class;
            $class = get_parent_class($class);
        }
        $exceptionClasses = array_reverse($exceptionClasses);
        
        foreach ($exceptionClasses as $exceptionClass) {
            if (isset($this->exception_sample_rates["$exceptionClass"])) {
                $sampleRate = $this->exception_sample_rates["$exceptionClass"];
            }
        }
        
        return $sampleRate;
    }

    /**
     * @param Payload $payload
     * @return bool
     */
    private function payloadLevelTooLow($payload)
    {
        return $this->levelTooLow($payload->getData()->getLevel());
    }

    /**
     * @param Level $level
     * @return bool
     */
    private function levelTooLow($level)
    {
        return $level->toInt() < $this->minimumLevel;
    }

    private function shouldSuppress()
    {
        return error_reporting() === 0 && !$this->reportSuppressed;
    }

    public function send(&$scrubbedPayload, $accessToken)
    {
        return $this->sender->send($scrubbedPayload, $accessToken);
    }

    public function sendBatch(&$batch, $accessToken)
    {
        return $this->sender->sendBatch($batch, $accessToken);
    }

    public function wait($accessToken, $max = 0)
    {
          $this->sender->wait($accessToken, $max);
    }

    public function handleResponse($payload, $response)
    {
        if (!is_null($this->responseHandler)) {
            $this->responseHandler->handleResponse($payload, $response);
        }
    }
}
