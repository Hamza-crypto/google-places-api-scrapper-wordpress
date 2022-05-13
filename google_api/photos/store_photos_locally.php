<?php

/**
 * This script fetches photo_references from DB (which we earlier fetched from
 * google API by providing place_id)
 *
 * Fetches image_urls from google API by proving reference id
 * Downloads all the images locally and saves their urls in DB
 */

require_once('../../wp-load.php');
include_once(ABSPATH . '/wp-admin/includes/image.php');
define('WP_USE_THEMES', false);

require_once __DIR__ . "/../../outscrapper/vendor/autoload.php"; // change path as needed

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

    $last_run = get_post_meta($post_id, 'images_saved_last_run', true);

    if( empty($last_run) || $last_run < strtotime('-31 day', time())) {

        $google_photo_references = get_post_meta($post_id, 'google_photo_references', true);

        $new_img_ids = [];
        foreach ($google_photo_references as $google_photo_reference) {
            $new_img_ids[] = store_image($google_photo_reference);
            sleep(1);
        }

        // Assign first image url to hero_image filed
        $hero_image_url = wp_get_attachment_image_src($new_img_ids[0], 'large')[0];
        update_post_meta($post_id, 'wordpress_store_locator_hero_image_url', $hero_image_url);
        update_post_meta($post_id, 'all_images', $new_img_ids); // All images for later use
        update_post_meta($post_id, 'images_saved_last_run', time());

        dump($post_id, $new_img_ids);
    }

endwhile;
$t = time();
echo "Images saved successfully " . date("Y-m-d H:m", $t);

function store_image($google_photo_reference)
{
    $url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=800&photo_reference=$google_photo_reference&key=GOOGLE-PLACE-API-KEY";

    $client = HttpClient::create();
    $response = $client->request('GET', $url);
    $imageurl = $response->getInfo()['url'];

    //$imageurl = 'https://lh3.googleusercontent.com/places/AAcXr8oUHSuJm_8XYZd46gXhL8b-M7nTYGwHb5xCUL53GtPhwofMeQo6jqYeZP6y7sQd8WlmAy8ZfcyEeAczNga2bMD8i-hdKiqvqL0=s1600-w200';

    $imagetype = end(explode('/', getimagesize($imageurl)['mime']));
    $uniq_name = date('dmY') . '' . (int)microtime(true);
    $filename = $uniq_name . '.' . $imagetype;

    $uploaddir = wp_upload_dir();
    $uploadfile = $uploaddir['path'] . '/' . $filename;
    $contents = file_get_contents($imageurl);
    $savefile = fopen($uploadfile, 'w');
    fwrite($savefile, $contents);
    fclose($savefile);

    $wp_filetype = wp_check_filetype(basename($filename), null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => $filename,
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $uploadfile);
    $imagenew = get_post($attach_id);
    $fullsizepath = get_attached_file($imagenew->ID);
    $attach_data = wp_generate_attachment_metadata($attach_id, $fullsizepath);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}