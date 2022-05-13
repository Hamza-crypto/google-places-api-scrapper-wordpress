<?php

/**
 * 1) Feetch all posts
 * 2) Fetch all comment metas
 * 3) Sum up the ratings
 * 4) take avg
 * 5) store
 */

require_once('../../wp-load.php');
define('WP_USE_THEMES', false);

require_once __DIR__ . "/../vendor/autoload.php"; // change path as needed

$args = [
    'post_type' => 'stores',
    'posts_per_page' => -1,
];

$loop = new WP_Query($args);


while ($loop->have_posts()) : $loop->the_post();
    $post_id = get_the_ID();
    $comments = get_comments(['post_id' => $post_id]);

    $currentRatingsValue = 0;
    foreach ($comments as $comment) {
        $comment_rating = (int)get_comment_meta($comment->comment_ID, 'store_locator_rating', true);
        $currentRatingsValue += $comment_rating;
    }

    $reviewsCount = get_comments([
        'post_id' => $post_id,
        'count' => true
    ]);

    if (!$reviewsCount) {
        delete_post_meta($post_id, 'wordpress_store_locator_average_rating');
        delete_post_meta($post_id, 'wordpress_store_locator_rating_value');
        continue;
    }

    $averageRating = round($currentRatingsValue / $reviewsCount, 1);

    update_post_meta($post_id, 'wordpress_store_locator_rating_value', $currentRatingsValue);
    update_post_meta($post_id, 'wordpress_store_locator_average_rating', $averageRating);

    echo sprintf('%d - %d - %d - %f<br/>', $post_id, $currentRatingsValue, $reviewsCount, $averageRating);

endwhile;
echo "All post metas balanced successfully";