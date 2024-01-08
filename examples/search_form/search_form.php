<?php

require_once '../../src/autoloader.php';

if ( empty($_POST) ) {
    return;
} else {
    handle_search_form($_POST);
}

/**
 * Main search from handler.
 * @param $post
 * @return void
 */
function handle_search_form($post)
{
    if ( empty($post['search_field']) ) {
        return;
    }

    $bot_checker = new \Cleantalk\CheckBot($post);

    //call visitor check
    $is_bot = $bot_checker->check()->getVerdict();
    if ( $is_bot ) {
        die ($bot_checker->getBlockMessage());
    }

    //implement your search form handler here replacing echo
    echo('You searched for this: ' . $post['search_field']);
}
