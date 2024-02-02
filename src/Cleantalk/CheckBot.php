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
    private $ct_comment = '';

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

    /**
     * @param string $event_token
     * @return void
     */
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

        $ct = new Cleantalk();
        $ct->server_url = $ct_request::CLEANTALK_API_URL;
        $ct_result = $ct->checkBot($ct_request);
        $this->writeLog('raw result: ' . var_export($ct_result, true));

        return $ct_result;
    }

    /**
     * @param CleantalkResponse $api_call_response
     * @return void
     * @throws \Exception
     */
    private function validateApiResponse(CleantalkResponse $api_call_response)
    {
        if (!empty($api_call_response->errstr)) {
            throw new \Exception('failed. Check method call parameters. Error: ' . $api_call_response->errstr);
        }
    }

    /**
     * @return bool
     */
    public function getVerdict()
    {
        return $this->verdict;
    }

    /**
     * @return string
     */
    public function getBlockMessage()
    {
        return ( !empty($this->config->common_block_message)
            ? $this->config->common_block_message
            : !empty($this->ct_comment) )
            ? $this->ct_comment
            : '';
    }

    /**
     * Makes decision if visitor is bot using CleanTalk libraries, exactly check_bot method.
     * @return CheckBot
     */
    public function check()
    {
        //Get event token.
        $this->setEventToken($this->getEventToken());

        //If not provided most probably that the visitor has no JS, it is bot-like behavior.
        //If the setting allow_no_js_visitors is set to false, the visitor will be blocked
        if (empty($this->event_token)) {
            $this->verdict = $this->config->block_no_js_visitors;
            $this->writeLog('no event_token found, probably visitor has no JavaScript');
            if ($this->verdict) {
                $this->writeLog('visitor is blocked. No JS execution found.');
            }
        }

        try {
            //Call CleanTalk API
            $api_call_response = $this->checkBotApiCall();
            //Validate response
            $this->validateApiResponse($api_call_response);
            $this->ct_comment = $api_call_response->comment;
        } catch (\Exception $e) {
            $this->request_success = false;
            $this->verdict = false;
            $this->writeLog($e->getMessage());
        }

        if ($this->request_success && !$this->verdict) {
            //block if CleanTalk decision is enough for you
            if ( $this->config->trust_cleantalk_decision ) {
                $this->verdict = isset($api_call_response->allow) && $api_call_response->allow != 1;
                $ct_verdict_log = $this->verdict === true
                    ? 'visitor blocked on CleanTalk decision.'
                    : 'visitor passed on CleanTalk decision.';
                $this->writeLog($ct_verdict_log);
            } else {
                //run custom checks for response properties
                foreach ( $this->config->custom_checks_properties as $property ) {
                    if ( $api_call_response->$property > $this->config->$property ) {
                        $this->verdict = true;
                        $custom_verdict_log = 'visitor blocked by custom setting: ' . $property . ' > ' . $this->config->$property;
                        $this->writeLog($custom_verdict_log);
                        break;
                    }
                }
            }
        }

        if ($this->verdict === false) {
            $this->writeLog('all checks passed');
        }

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
