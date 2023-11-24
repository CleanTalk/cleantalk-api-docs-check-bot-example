<?php

require_once (dirname(__FILE__) . '/lib/Cleantalk.php');
require_once (dirname(__FILE__) . '/lib/CleantalkRequest.php');
require_once (dirname(__FILE__) . '/lib/CleantalkResponse.php');
require_once (dirname(__FILE__) . '/lib/CleantalkHelper.php');
require_once (dirname(__FILE__) . '/UseCheckBot.php');

if (empty($_POST)) {
    return;
} else {
    $post = $_POST;
}

handle_search_form($post);

/**
 * Main search from handler.
 * @param $post
 * @return void
 */
function handle_search_form($post)
{
    if (empty($post['search_field'])){
        return;
    }

    $bot_checker = new UseCheckBot($post);

    //call visitor check
    $is_bot = $bot_checker->check()->getVerdict();
    if ($is_bot) {
        die ($bot_checker->getBlockMessage());
    }

    //implement your search form handler here replacing echo
    echo ('You searched for this: ' . $post['search_field']);
}
