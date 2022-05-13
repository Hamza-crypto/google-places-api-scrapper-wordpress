<?php

/**
 * This script fetches everything from DB to verify that everything exists in db well
 *
 */
require_once('../../wp-load.php');
define('WP_USE_THEMES', false);


require_once __DIR__ . "/../vendor/autoload.php"; // change path as needed

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\HttpClient\HttpClient;

$log = new Logger('photos');
$log->pushHandler(new StreamHandler('photos.log', 'info'));

$meta_key = 'wordpress_store_locator_place_id';
$args = [
    'post_type' => 'stores',
    'posts_per_page' => -1,
];

$loop = new WP_Query($args);

while ($loop->have_posts()) : $loop->the_post();
    $post_id = get_the_ID();
    $place_id = get_post_meta($post_id, 'wordpress_store_locator_place_id', true);

    dump($post_id);
    if (!$place_id) {
        dump('place_id does not exists');
    }

    $wednesday_hours = get_post_meta($post_id, 'wordpress_store_locator_Wednesday_open', true);
    if (!$wednesday_hours) {
        dump('Open hours does not exists');
    }

    $photo_reference = get_post_meta($post_id, 'photo_reference_fetched_last_run', true);
    if (!$photo_reference) {
        dump('Photo_reference does not exists');
    }

    $images_saved = get_post_meta($post_id, 'images_saved_last_run', true);
    if (!$images_saved) {
        dump('Images does not exists');
    }

endwhile;
echo "Script completed successfully " . time();
