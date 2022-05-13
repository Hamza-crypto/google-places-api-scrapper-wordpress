<?php
require_once('../../wp-load.php');
define('WP_USE_THEMES', false);

$meta_key = 'wordpress_store_locator_place_id';
$args = [
    'post_type' => 'stores',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => $meta_key,
            'compare' => 'NOT EXISTS',
        ],
    ]
    ];

$loop = new WP_Query($args);

$key = "candidates";
while ($loop->have_posts()) : $loop->the_post();
    $post_id = get_the_ID();
    $title = urlencode(get_the_title());
    $url = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json?fields=name,place_id&input=$title&inputtype=textquery&key=GOOGLE-PLACE-API-KEY";
    $response = wp_remote_get($url);
    $jsonObj = json_decode($response['body']);
    $candidates = $jsonObj->$key;
    $place_id = $candidates[0]->place_id;
    $place_id = $candidates[0]->place_id;
    update_post_meta($post_id, $meta_key, $place_id);
    populate_hours($post_id, $place_id);
    echo sprintf("%d - %s<br/>", $post_id, $place_id);
endwhile;

$t=time();
echo "Hours populated successfully " . date("Y-m-d H:m",$t);

function populate_hours($post_id, $place_id)
{
    $url = "https://maps.googleapis.com/maps/api/place/details/json?fields=opening_hours&place_id=$place_id&key=GOOGLE-PLACE-API-KEY";
    $response = wp_remote_get($url);
    $jsonObj = json_decode($response['body']);
    $result = $jsonObj->result->opening_hours->weekday_text;

    $monday = explode("–", explode("ay:", $result[0])[1]);
    update_post_meta($post_id, 'wordpress_store_locator_Monday_open', $monday[0]);
    update_post_meta($post_id, 'wordpress_store_locator_Monday_close', $monday[1]);

    $tuesday = explode("–", explode("ay:", $result[1])[1]);
    update_post_meta($post_id, 'wordpress_store_locator_Tuesday_open', $tuesday[0]);
    update_post_meta($post_id, 'wordpress_store_locator_Tuesday_close', $tuesday[1]);

    $wednesday = explode("–", explode("ay:", $result[2])[1]);
    update_post_meta($post_id, 'wordpress_store_locator_Wednesday_open', $wednesday[0]);
    update_post_meta($post_id, 'wordpress_store_locator_Wednesday_close', $wednesday[1]);

    $thursday = explode("–", explode("ay:", $result[3])[1]);
    update_post_meta($post_id, 'wordpress_store_locator_Thursday_open', $thursday[0]);
    update_post_meta($post_id, 'wordpress_store_locator_Thursday_close', $thursday[1]);

    $friday = explode("–", explode("ay:", $result[4])[1]);
    update_post_meta($post_id, 'wordpress_store_locator_Friday_open', $friday[0]);
    update_post_meta($post_id, 'wordpress_store_locator_Friday_close', $friday[1]);

    $saturday = explode("–", explode("ay:", $result[5])[1]);
    update_post_meta($post_id, 'wordpress_store_locator_Saturday_open', $saturday[0]);
    update_post_meta($post_id, 'wordpress_store_locator_Saturday_close', $saturday[1]);

    $sunday = explode("–", explode("ay:", $result[6])[1]);
    update_post_meta($post_id, 'wordpress_store_locator_Sunday_open', $sunday[0]);
    update_post_meta($post_id, 'wordpress_store_locator_Sunday_close', $sunday[1]);
}





