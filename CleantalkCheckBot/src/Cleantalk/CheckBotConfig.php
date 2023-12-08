<?php

namespace Cleantalk;

class CheckBotConfig
{
    private $obligatory_properties = array('access_key',
        'trust_cleantalk_decision',
    );

    public $custom_checks_properties = array('bot_expectation',
        'ip_frequency_24hour',
        'ip_frequency_1hour',
        'ip_frequency_10min',
    );

    public $access_key = '';
    public $trust_cleantalk_decision = true;
    public $common_block_message = 'Visitor blocked. It seems to be a bot.';
    public $do_log = true;
    public $bot_expectation = 0.5;
    public $ip_frequency_24hour = 50;
    public $ip_frequency_1hour = 15;
    public $ip_frequency_10min = 5;

    public function __construct()
    {
    }

    public function loadConfig()
    {
        try {
            require_once 'config.php';
            global $check_bot_config;

            if (empty($check_bot_config) || !$this->isObligatoryParamsPresented($check_bot_config) ) {
                throw new \Exception('CheckBot config: not enough params set. Load defaults.');
            }

            foreach ( $check_bot_config as $param_name => $param ) {
                if ( property_exists(static::class, $param_name) ) {
                    $type = gettype($this->$param_name);
                    $this->$param_name = $param;
                    settype($this->$param_name, $type);
                }
            }
        } catch (\Exception $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }

        return array('success' => true, 'msg' => 'custom config loaded.' . var_export($this, true));

    }

    public function __get($name)
    {
        return property_exists(static::class, $name) ? $this->$name : null;
    }

    private function isObligatoryParamsPresented($params)
    {
        return empty($this->obligatory_properties) ||
            count(array_intersect($this->obligatory_properties, array_keys($params))) === count(
                $this->obligatory_properties
            );
    }
}
