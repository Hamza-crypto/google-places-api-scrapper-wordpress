<?php

/**
 * This script fetches photo_references from DB (which we earlier fetched from
 * google API by providing place_id)
 *
 * Fetches image_urls from google API by proving reference id
 * Downloads all the images locally and saves their urls in DB
 */

require_once('../../wp-load.php');

define('WP_USE_THEMES', false);

require_once __DIR__ . "/../../outscrapper/vendor/autoload.php"; // change path as needed

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\HttpClient\HttpClient;

$log = new Logger('photos');
$log->pushHandler(new StreamHandler('photos.log', 'info'));

$args = [
    'post_type' => 'stores',
    'posts_per_page' => -1,
];

$loop = new WP_Query($args);

while ($loop->have_posts()) : $loop->the_post();
    $post_id = get_the_ID();
    dump($post_id);
    $all_images = get_post_meta($post_id, 'all_images', true);
    $imgs = [];
    foreach ($all_images as $image_id) {
        $url_large = wp_get_attachment_image_src($image_id, 'large')[0];
        $url_thumbnail = wp_get_attachment_image_src($image_id)[0];
        $imgs[] = [
            'img_id' => $image_id,
            'large' => $url_large,
            'thumbnail' => $url_thumbnail,
        ];
        update_post_meta($post_id, 'all_images_urls', $imgs);
    }
    dump($imgs);

endwhile;
$t = time();
echo "Image urls created successfully " . date("Y-m-d H:m", $t);
