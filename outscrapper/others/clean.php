<?php

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

//    delete_post_meta( $post_id, 'last_run' );
//    delete_post_meta( $post_id, 'wordpress_store_locator_average_rating' );
//    delete_post_meta( $post_id, 'wordpress_store_locator_rating_value' );
//    delete_post_meta( $post_id, 'last_google_review_id' );

    //Replace meta_keys
//    $images_saved = get_post_meta($post_id, 'images_saved', true);
//    $photo_reference_fetched = get_post_meta($post_id, 'photo_reference_fetched', true);
//
//    update_post_meta($post_id, 'images_saved_last_run', $images_saved);
//    update_post_meta($post_id, 'photo_reference_fetched_last_run', $photo_reference_fetched);
//
//    delete_post_meta( $post_id, 'images_saved' );
//    delete_post_meta( $post_id, 'photo_reference_fetched' );
    echo "$post_id <br/>";

endwhile;
echo "All post metas cleared successfully";