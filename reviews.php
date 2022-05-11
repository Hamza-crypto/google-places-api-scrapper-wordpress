<?php

require_once('../wp-load.php');
define('WP_USE_THEMES', false);

require_once __DIR__ . "/vendor/autoload.php"; // change path as needed

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\HttpClient\HttpClient;

$log = new Logger('reviews');
$log->pushHandler(new StreamHandler('reviews.log', 'info'));

$args = [
    'post_type' => 'stores',
    'posts_per_page' => -1,
];

$loop = new WP_Query($args);

while ($loop->have_posts()) : $loop->the_post();
    $post_id = get_the_ID();

    $last_run = get_post_meta($post_id, 'last_run', true);
    if( empty($last_run) || $last_run < strtotime('-1 day', time())){
        $place_id = get_post_meta($post_id, 'wordpress_store_locator_place_id', true);
        fetch_reviews($post_id, $place_id, $log);
        $log->info('Run for ID ', [$post_id]);
    }
endwhile;

function fetch_reviews($post_id, $place_id, $log)
{
    $fields = '&fields=place_id,rating,reviews,reviews_data';
    $url = sprintf('%s%s%s%s', 'https://api.app.outscraper.com/maps/reviews-v3?query=', $place_id, '&reviewsLimit=1000&async=false&sort=newest', $fields);

    $client = HttpClient::create();
    $response = $client->request('GET', $url, [
        'headers' => [
            'Accept' => 'application/json',
            'X-API-KEY' => 'API_KEY_HERE',
        ],
    ]);
    $response = json_decode($response->getContent());

    dump($response);
    if ($response->status != 'Success') {
        $log->info('error', [$response]);
    }
    $data = $response->data[0];
    $reviews_data = $data->reviews_data;

    $rating_count = get_post_meta($post_id, 'wordpress_store_locator_rating_value', true);
    $last_google_review_id_from_db = get_post_meta($post_id, 'last_google_review_id', true);

    if (!$rating_count) $rating_count = 0;

    for ($i = 0; $i < sizeof($reviews_data); $i++) {
        $review = $reviews_data[$i];

        if ($review->review_id == $last_google_review_id_from_db) {
            break;
        }
        $commentdata = [
            'comment_post_ID' => $post_id,
            'comment_content' => $review->review_text,
            'comment_author' => $review->author_title,
            'comment_author_email' => 'ryan@visualizedigital.com',
        ];
        $comment_id = wp_insert_comment($commentdata);
        $rating_count += $review->review_rating;
        add_comment_meta($comment_id, 'store_locator_rating', $review->review_rating);
    }

    //store last_google_id
    update_post_meta($post_id, 'last_google_review_id', $reviews_data[0]->review_id);

    $total_rating = get_comment_count($post_id)['total_comments'] * 5;

    if ($total_rating) {
        $average_rating = round((($rating_count / $total_rating) / 2) * 10, 1);
        update_post_meta($post_id, 'wordpress_store_locator_rating_value', $rating_count);
        update_post_meta($post_id, 'wordpress_store_locator_average_rating', $average_rating);

    }

    update_post_meta($post_id, 'reviewer', 'google');
    update_post_meta($post_id, 'last_run', time());

    dump(get_post_permalink(), $post_id);
}

/**
 * 1) Store last google_id in db
 * 2) Fetch api in sorted timestamp
 * 3) Get last google_id from DB
 * 4) Traverse reviews array and store all reviews
 * 4) Until google_id of review matches last_google id
 */