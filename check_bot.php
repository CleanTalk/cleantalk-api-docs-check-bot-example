<?php

require_once(dirname(__FILE__) . '/lib/Cleantalk.php');
require_once(dirname(__FILE__) . '/lib/CleantalkRequest.php');
require_once(dirname(__FILE__) . '/lib/CleantalkResponse.php');
require_once(dirname(__FILE__) . '/lib/CleantalkHelper.php');
require_once(dirname(__FILE__) . '/CheckBot/CheckBot.php');
require_once(dirname(__FILE__) . '/CheckBot/CheckBotConfig.php');

if ( empty($_POST) ) {
    return;
} else {
    $post = $_POST;
}

handle_search_form($post);

/**
 * Main search from handler.
 * @param $post
 * @return void
 * @throws Exception
 */
function handle_search_form($post)
{
    if ( empty($post['search_field']) ) {
        return;
    }

    // set new CheckBotConfig
    $config = new CheckBotConfig(
        array(
            /*
             * Your CleanTalk access key. This example get the key from the system environment, you can use your onw way or insert key string directly.
             */
            'access_key' => getenv("CLEANTALK_TEST_API_KEY"),
            /*
             * Set this to true if you trust CleanTalk decision and do not want to perform additional custom checks
             */
            'trust_cleantalk_decision' => false,
            /*
             * Message for blocked visitor
             */
            'common_block_message' => 'Visitor blocked. It seems to be a bot.',

            /**
             * Attention! Settings below affected only if the property "trust_cleantalk_decision" is set to false.
             */

            /*
             * Custom check - set maximum bot probability percentage. For example, 0.5 is 50%. If CleanTalk response
             * with bot_expectation 0.53 - visitor will be blocked, if 0.47 - passed.
             */
            'bot_expectation' => 0.5,
            /*
             * Custom checks - set how to block a visitor whose IP address detected by CleanTalk service in the period.
             * For example, if CleanTalk response contains ip_frequency_24hour = 1000, and the config property ip_frequency_24hour = 500, visitor will be blocked.
             */
            'ip_frequency_24hour' => 50,
            'ip_frequency_1hour' => 15,
            'ip_frequency_10min' => 5,
            /*
             * Set to true if you want to see the log, false otherwise.
             */
            'do_log' => true
        )
    );
    $bot_checker = new CheckBot($post, $config);

    //call visitor check
    $is_bot = $bot_checker->check()->getVerdict();
    if ( $is_bot ) {
        die ($bot_checker->getBlockMessage());
    }

    //implement your search form handler here replacing echo
    echo('You searched for this: ' . $post['search_field']);
}
