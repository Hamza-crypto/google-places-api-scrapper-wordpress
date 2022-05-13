<?php

/**
 * 1) Store last google_id in db
 * 2) Fetch api in sorted timestamp
 * 3) Get last google_id from DB
 * 4) Traverse reviews array and store all reviews
 * 4) Until google_id of review matches last_google id
 */


require_once('../../wp-load.php');
define('WP_USE_THEMES', false);

require_once __DIR__ . "/../vendor/autoload.php"; // change path as needed

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

$t=time();
echo "Google reviews saved successfully " . date("Y-m-d H:m",$t);


function fetch_reviews($post_id, $place_id, $log)
{
    $fields = '&fields=place_id,rating,reviews,reviews_data';
    $url = sprintf('%s%s%s%s', 'https://api.app.outscraper.com/maps/reviews-v3?query=', $place_id, '&reviewsLimit=1000&async=false&sort=newest', $fields);

    $client = HttpClient::create();
    $response = $client->request('GET', $url, [
        'headers' => [
            'Accept' => 'application/json',
            'X-API-KEY' => 'API-KEY-HERE',
        ],
    ]);

    $response = json_decode($response->getContent());

//    $response = (object)[
//        "id" => "db1559d0-fe04-486d-b0ee-9b820ba6ff49",
//        "status" => "Success",
//        'data' => [
//            (object)[
//                "rating" => 4.6,
//                "reviews" => 234,
//                "reviews_data" => [
//                    (object)[
//                        "review_text" => "Custom string 5 " . time(),
//                        "review_rating" => rand(1, 5),
//                        'review_id' => 'ChdDSUhNMG101hamza0VJQ0FnSUhdsajkhd'
//                    ],
//                    (object)[
//                        "review_text" => "Custom string 4 " . time(),
//                        "review_rating" => rand(1, 5),
//                        'review_id' => 'ChdDSUhNMG100hamza0VJQ0FnSUNXekt1M4'
//                    ],
//                    (object)[
//                        "review_text" => "Custom string 3 " . time(),
//                        "review_rating" => rand(1, 5),
//                        'review_id' => 'ChdDSUhNMG9nS0VJQ0FnSUNXekt1M2pBRR3'
//                    ],
//                    (object)[
//                        "review_text" => "Custom string 2 " . time(),
//                        "review_rating" => rand(1, 5),
//                        'review_id' => 'ChZDSUhNMG9nS0VJQ0FnSUR5a09DQUxBE2'
//                    ],
//                    (object)[
//                        "review_text" => "Custom string 1 " . time(),
//                        "review_rating" => rand(1, 5),
//                        'review_id' => 'ChZDSUhNMG9nS0VJQ0FnSUMyOV9UUkRnE1'
//                    ]
//                ],
//                "place_id" => "ChIJTe8aJxigmoARIGfPGmlmwZ4"
//            ],
//        ],
//    ];

    dump($response);
    if ($response->status != 'Success') {
        $log->info('error', [$response]);
    }
    $data = $response->data[0];
    $reviews_data = $data->reviews_data;

    $currentRatingsValue = get_post_meta($post_id, 'wordpress_store_locator_rating_value', true);
    $last_google_review_id_from_db = get_post_meta($post_id, 'last_google_review_id', true);

    if (!$currentRatingsValue) $currentRatingsValue = 0;

    for ($i = 0; $i < sizeof($reviews_data); $i++) {
        $review = $reviews_data[$i];

        if ($review->review_id == $last_google_review_id_from_db) {
            break;
        }

        $comment_data = [
            'comment_post_ID' => $post_id,
            'comment_content' => $review->review_text,
            'comment_author' => $review->author_title,
            'comment_author_email' => 'ryan@visualizedigital.com',
        ];
        $comment_id = wp_insert_comment($comment_data);
        add_comment_meta($comment_id, 'store_locator_rating', $review->review_rating);

        $currentRatingsValue += $review->review_rating;
    }

    //store last_google_id
    update_post_meta($post_id, 'last_google_review_id', $reviews_data[0]->review_id);

    $reviewsCount = get_comments( [
        'post_id' => $post_id,
        'count' => true
    ] );

    $averageRating = round($currentRatingsValue / $reviewsCount, 1);

    update_post_meta($post_id, 'wordpress_store_locator_rating_value', $currentRatingsValue);
    update_post_meta($post_id, 'wordpress_store_locator_average_rating', $averageRating);

    update_post_meta($post_id, 'reviewer', 'google');
    update_post_meta($post_id, 'last_run', time());

    dump(get_post_permalink(), $post_id);
}

