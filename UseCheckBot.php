<?php
use Cleantalk\Cleantalk;
use Cleantalk\CleantalkRequest;
use Cleantalk\CleantalkResponse;

class UseCheckBot {

    private $check_bot_config = array(
        'access_key' => 'ehy7uvabepydaje',
        'enable_check_bot_for_forms' => true,
        'trust_cleantalk_decision' => false,
        'common_block_message' => 'Visitor blocked. It seems to be a bot.',
        'check_bot_custom_block_settings' => array(
            'bot_expectation' => array(
                'value' => 0.5,
                'custom_block_message' => ''
            ),
            'ip_frequency_24hour' => array(
                'value' => 50,
                'custom_block_message' => ''
            ),
            'ip_frequency_1hour' => array(
                'value' => 15,
                'custom_block_message' => ''
            ),
            'ip_frequency_10min' => array(
                'value' => 5,
                'custom_block_message' => ''
            ),
        ),
        'do_log' => true
    );

    private $post;
    private $event_token;
    private $do_log;
    private $verdict = false;
    private $block_message = '';

    public function __construct($post_data) {
        $this->post = $post_data;
        $this->do_log = $this->check_bot_config['do_log'];
        $this->block_message = $this->check_bot_config['common_block_message'];
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
        if ($event_token && is_string($event_token) && strlen($event_token) === 64) {
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
        $ct_request->auth_key = $this->check_bot_config['access_key'];

        if ( empty($ct_request->auth_key) ) {
            $this->writeLog('CleanTalk check_bot: access key is empty.');
            return false;
        }

        if ( empty($ct_request->event_token) ) {
            $this->writeLog('CleanTalk check_bot: event token not found.');
            return false;
        }

        $ct = new Cleantalk();
        $ct->server_url = $ct_request::CLEANTALK_API_URL;
        $ct_result = $ct->checkBot($ct_request);
        $this->writeLog('CleanTalk check_bot result: ' . var_export($ct_result,true));

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
     * @return UseCheckBot
     */
    public function check()
    {
        if (!$this->check_bot_config['enable_check_bot_for_forms']) {
            $this->verdict = false;
            return $this;
        }

        $this->setEventToken($this->getEventToken());
        $check_bot_result = $this->checkBotApiCall();

        if (false === $check_bot_result) {
            $this->writeLog('CleanTalk check_bot failed. Skip check.');
            $this->verdict = false;
            return $this;
        }

        if ($this->check_bot_config['trust_cleantalk_decision']) {
            $this->verdict = isset($check_bot_result->allow) && $check_bot_result->allow != 1;
            $this->block_message = !empty($this->check_bot_config['common_block_message'])
                ? $this->check_bot_config['common_block_message']
                : $check_bot_result->comment;
            $this->writeLog('CleanTalk check_bot: visitor blocked on CleanTalk decision.');
            return $this;
        }

        foreach ($this->check_bot_config['check_bot_custom_block_settings'] as $setting_name => $setting) {
            if ($check_bot_result->$setting_name > $setting['value']) {
                $this->verdict = true;
                $this->block_message = !empty($setting['custom_block_message'])
                    ? $setting['custom_block_message']
                    : $this->block_message;
                $this->writeLog('CleanTalk check_bot: visitor blocked by custom setting: ' . $setting_name . ' > ' . $setting['value']);
                return $this;
            }
        }
        return $this;
    }

    private function writeLog($msg) {
        if ($this->do_log) {
            error_log($msg);
        }
    }
}
