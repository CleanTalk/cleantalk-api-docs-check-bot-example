<?php

use Cleantalk\Cleantalk;
use Cleantalk\CleantalkRequest;
use Cleantalk\CleantalkResponse;

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

    public function __construct(array $post_data, CheckBotConfig $config)
    {
        $this->post = $post_data;
        $this->config = $config;
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
            $this->writeLog('CleanTalk check_bot: access key is empty. Check skipped.');
            return false;
        }

        if ( empty($ct_request->event_token) ) {
            $this->writeLog('CleanTalk check_bot: event token not found. Check skipped.');
            return false;
        }

        $ct = new Cleantalk();
        $ct->server_url = $ct_request::CLEANTALK_API_URL;
        $ct_result = $ct->checkBot($ct_request);
        $this->writeLog('CleanTalk check_bot result: ' . var_export($ct_result, true));

        return $ct_result;
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
        //get event token
        $this->setEventToken($this->getEventToken());
        //call CleanTalk API
        $check_bot_result = $this->checkBotApiCall();

        //handle response
        if ( false === $check_bot_result ) {
            $this->verdict = false;
            return $this;
        }

        if (!empty($check_bot_result->errstr)) {
            $this->writeLog('CleanTalk check_bot failed. Check method call parameters. Error: ' . $check_bot_result->errstr);
            $this->verdict = false;
            return $this;
        }

        $this->block_message = !empty($this->config->common_block_message)
            ? $this->config->common_block_message
            : $check_bot_result->comment;

        //block if CleanTalk decision is enough for you
        if ( $this->config->trust_cleantalk_decision ) {
            $this->verdict = isset($check_bot_result->allow) && $check_bot_result->allow != 1;
            $this->writeLog('CleanTalk check_bot: visitor blocked on CleanTalk decision.');
            return $this;
        }

        //run custom checks for response properties
        foreach ( $this->config->custom_checks_properties as $property ) {
            if ( $check_bot_result->$property > $this->config->$property ) {
                $this->verdict = true;
                $this->writeLog('CleanTalk check_bot: visitor blocked by custom setting: ' . $property . ' > ' . $this->config->$property);
                return $this;
            }
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
        if ( $this->config->do_log && is_string($msg) && !empty($msg)) {
            error_log($msg);
        }
    }
}
