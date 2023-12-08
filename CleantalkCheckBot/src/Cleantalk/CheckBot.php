<?php

namespace Cleantalk;

class CheckBot
{
    /**
     * Configuration obj.
     * @var CheckBotConfig
     */
    private $config;
    /**
     * $_POST
     * @var array
     */
    private $post;
    /**
     * Bot detector JS library event token
     * @var string
     */
    private $event_token;
    /**
     * CheckBot final verdict. True if visitor is bot.
     * @var bool
     */
    private $verdict = false;
    /**
     * The message for blocked visitor.
     * @var string
     */
    private $block_message = '';

    private $request_success = true;

    public function __construct(array $post_data)
    {
        $this->post = $post_data;
        $this->config = new CheckBotConfig();
        $load_config_result = $this->config->loadConfig();
        $this->writeLog($load_config_result['msg']);
    }

    /**
     * Get Bot-Detector event token form POST data.
     * @return string
     */
    private function getEventToken()
    {
        $event_token = isset($this->post['ct_bot_detector_event_token'])
            ? $this->post['ct_bot_detector_event_token']
            : '';
        if ( $event_token && is_string($event_token) && strlen($event_token) === 64 ) {
            return $event_token;
        }
        return '';
    }

    private function setEventToken($event_token)
    {
        $this->event_token = $event_token;
    }

    /**
     * Call check_bot CleanTalk API method. Return false on failure, CleantalkResponse obj on succes.
     * @return CleantalkResponse|false
     */
    private function checkBotApiCall()
    {

        $ct_request = new CleantalkRequest();
        $ct_request->event_token = $this->event_token;
        $ct_request->auth_key = $this->config->access_key;

        if ( empty($ct_request->auth_key) ) {
            throw new \Exception('access key is empty. Check skipped.');
        }

        if ( empty($ct_request->event_token) ) {
            throw new \Exception('event token not found. Check skipped.');
        }

        $ct = new Cleantalk();
        $ct->server_url = $ct_request::CLEANTALK_API_URL;
        $ct_result = $ct->checkBot($ct_request);
        $this->writeLog('raw result: ' . var_export($ct_result, true));

        return $ct_result;
    }

    private function validateApiResponse(CleantalkResponse $api_call_response)
    {
        if (!empty($api_call_response->errstr)) {
            throw new \Exception('failed. Check method call parameters. Error: ' . $api_call_response->errstr);
        }
    }

    public function getVerdict()
    {
        return $this->verdict;
    }

    public function getBlockMessage()
    {
        return $this->block_message;
    }

    /**
     * Makes decision if visitor is bot using CleanTalk libraries, exactly check_bot method.
     * @return CheckBot
     */
    public function check()
    {
        $process_result_log = 'request skipped.';
        try {
            //get event token
            $this->setEventToken($this->getEventToken());
            //call CleanTalk API
            $api_call_response = $this->checkBotApiCall();
            //validate response
            $this->validateApiResponse($api_call_response);

        } catch (\Exception $e) {
            $this->request_success = false;
            $this->verdict = false;
            $process_result_log = $e->getMessage();
        }

        if ($this->request_success) {
            $this->block_message = !empty($this->config->common_block_message)
                ? $this->config->common_block_message
                : $api_call_response->comment;

            //block if CleanTalk decision is enough for you
            if ( $this->config->trust_cleantalk_decision ) {
                $this->verdict = isset($api_call_response->allow) && $api_call_response->allow != 1;
                $process_result_log = $this->verdict === true
                    ? 'visitor blocked on CleanTalk decision.'
                    : 'visitor passed on CleanTalk decision.';
            }

            //run custom checks for response properties
            foreach ( $this->config->custom_checks_properties as $property ) {
                if ( $api_call_response->$property > $this->config->$property ) {
                    $this->verdict = true;
                    $process_result_log = 'visitor blocked by custom setting: ' . $property . ' > ' . $this->config->$property;
                    break;
                }
            }

            if ($this->verdict === false) {
                $process_result_log = 'all checks passed';
            }
        }
        $this->writeLog($process_result_log);
        return $this;
    }

    /**
     * Writes log in PHP error log.
     * @param $msg
     * @return void
     */
    private function writeLog($msg)
    {
        $log_msg_tmpl = 'CleanTalk CheckBot: ';

        if ( $this->config->do_log && is_string($msg) && !empty($msg)) {
            $token_suffix = $this->event_token ? ', event_token:' . $this->event_token : '';
            error_log($log_msg_tmpl . $msg . $token_suffix);
        }
    }
}
