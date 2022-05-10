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
    'posts_per_page' => 1,
    'meta_query' => [
        [
            'key' => 'reviews_fetched',
            'compare' => 'NOT EXISTS',
        ],
    ]
];

$loop = new WP_Query($args);

while ($loop->have_posts()) : $loop->the_post();
    $post_id = get_the_ID();
    $place_id = get_post_meta($post_id, 'wordpress_store_locator_place_id', true);
    //dump($post_id);
    fetch_reviews($post_id, $place_id, $log);
endwhile;

function fetch_reviews($post_id, $place_id, $log)
{
    $fields = '&fields=place_id,rating,reviews,reviews_data';
    $url = sprintf('%s%s%s%s', 'https://api.app.outscraper.com/maps/reviews-v3?query=', $place_id, '&reviewsLimit=1000&async=false', $fields);

    $client = HttpClient::create();
    $response = $client->request('GET', $url, [
        'headers' => [
            'Accept' => 'application/json',
            'X-API-KEY' => 'API_KEY_HERE',
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
//                        "review_text" => "Custom string " . time(),
//                        "review_rating" => rand(1,5)
//                    ],
//                    (object)[
//                        "review_text" => "Custom string " . time(),
//                        "review_rating" => rand(1,5)
//                    ],
//                    (object)[
//                        "review_text" => "Custom string " . time(),
//                        "review_rating" => rand(1,5)
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

    $rating_count = get_post_meta($post_id, 'wordpress_store_locator_rating_value', true);

    if(!$rating_count) $rating_count = 0;
    foreach ($reviews_data as $review) {
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

    $total_rating = get_comment_count( $post_id )['total_comments'] * 5;

    if ($total_rating){
        $average_rating = round((($rating_count / $total_rating ) / 2) * 10 , 1) ;
        update_post_meta($post_id, 'wordpress_store_locator_rating_value', $rating_count);
        update_post_meta($post_id, 'wordpress_store_locator_average_rating', $average_rating);

    }

    update_post_meta($post_id, 'reviews_fetched', 1);
    update_post_meta($post_id, 'reviewer', 'google');

    dump(get_post_permalink(), $post_id);
}