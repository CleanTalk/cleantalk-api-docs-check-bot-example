<?php
global $check_bot_config;
$check_bot_config = array(
    'access_key' => "your_cleantalk_antispam_access_key",
    'trust_cleantalk_decision' => true,
    'block_no_js_visitors' => true,
    'common_block_message' => 'Visitor blocked. It seems to be a bot.',
    'bot_expectation' => 0.5,
    'ip_frequency_24hour' => 50,
    'ip_frequency_1hour' => 15,
    'ip_frequency_10min' => 5,
    'do_log' => true
);

