<?php

/**
 * This script fetches photo_references from google API based on google place_ids (which we earlier fetched from
 * google API by providing location titles)
 * And stores in DB for later use
 */
require_once('../../wp-load.php');
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
    $last_run = get_post_meta($post_id, 'photo_reference_fetched_last_run', true);

    if( empty($last_run) || $last_run < strtotime('-31 day', time())) {
        $place_id = get_post_meta($post_id, $meta_key, true);
        dump($post_id, $place_id);

        save_google_photos_reference($post_id, $place_id, $log);
        update_post_meta($post_id, 'photo_reference_fetched_last_run',  time());
    }
endwhile;
$t=time();
echo "Photo_references saved successfully " . date("Y-m-d H:m",$t);

function save_google_photos_reference($post_id, $place_id, $log)
{
    $url = "https://maps.googleapis.com/maps/api/place/details/json?fields=photos&place_id=$place_id&key=GOOGLE-PLACE-API-KEY";
    $response = wp_remote_get($url);

    $log->info('error', [$response]);

    if((json_decode($response['body']))->status != 'OK'){
        dd(json_decode($response['body']));
    }
    $photos = json_decode($response['body'])->result->photos;

//    $photos = [
//        (object)[ 'photo_reference' => 'Aap_uEA5EidR1eCQo759WR_AK7WTbkmATwmsizGS8Ksthc6uu_ziW3p353N2kmV1Dsxr4JoopB6hDwG-3P_3z3YTqL_GlvZFFAyF5J_Ppl0gml2TcrmMkhJieUUtlEqQIuStpO-8fOxXkewkpIjPmgouCGB5i6B2-98d93JHjbnAoUVGsb7T'],
//        (object)[ 'photo_reference' => 'Aap_uEAtWaKrq7bEVJyeAPd0k08zX8fgpzFbmb_BNtM4VuLABFejMD0yCZWclj17OxHDdgN-TK9bTeUI_utGIvTXE9wfBF9EoVKq3SSHL6-7YFium49-rrV_M2TM7gD1Lk8SQIhORegx9cWfXRe0A883f8i9uROBnxMhpw7rpjJmS01YdfMW'],
//        (object)[ 'photo_reference' => 'Aap_uEA95P34ntLVQ2jDSfHgk9IXQW7WM35udKcVYTXSFETNhCpQc8CsniBprb5gSRkvWlAV5adYuGQ1aHEbKhFJEkE6C2CmwGPKt3hAV7UyjSQOTwWRwizZI7yWVK7FHd0_94ejjvaTQbk2BiI6X08npEyOFyTonzmJFuNUyypcBqOj_kA4'],
//        (object)[ 'photo_reference' => 'Aap_uEAg6i52c6VQIJt46fSTGOHAZw4XTEKOUPbFedIL89Mc1WFWMoNHtJLy3rk5wAk7T8HPF49oN8iDlUlsyXANWygzWAb_K1ZjFP53uQYES4W6k2IrvHZvqDTytgaWRl3fjV5mpRl4hszhL7ELecbur9V4B4Ijc-9L1O0GxRCXIfZH3io7'],
//        (object)[ 'photo_reference' => 'Aap_uEAvMqHJhhbAOZ63m_jL97RU5dGTO7fX1dDfBm695ynkD7VcpUFrxXfJ_QmMJrWdugBaBeIDEPN3VijwypCpdFNSRZqqtNPFwb2NVRQBPBV9OW6TG3lFcXjbH5MK_JbRy8nMG39u0Qck8F5Zkjy5YoFlRRti8xrGZcrdF_5yXAgs3D2I'],
//        (object)[ 'photo_reference' => 'Aap_uEB25fpmJuwJsDm1n_Gnr4YTgALJkZO5b_MkXkTkBxOAXiJW4DPzHqPK97Nz4rJIt3KxwSfWV8nYm1n9CaTng0kdmZapAhb_qi7d15Jpsg_PwtGu0xqiHMpCxZr28kk4GEsX5eMtOXYmi3t8EAigusazdbUt6djcYXFpbGpplSZqU5_n'],
//        (object)[ 'photo_reference' => 'Aap_uEAbNzbMWP5j0XKQS1JwkYtrPIkVREcmdSwv2ycc4di_ykRmwJoMSHWW1j74N3Mwem-PxgVhY4va6jB3R1ARHbMZWYfYG9rFBf7M3QjQt2Dkje06hquyzyfiLi_mgatl3sMPUtrcaZDvNBOJM2iT0qTFhU0UWK8SvQx1F6NONncAB9Un'],
//    ];
    dump($photos);

    $photos_array = [];
    foreach ($photos as $photo) {
        $photos_array[] = $photo->photo_reference;
    }

    update_post_meta($post_id, 'google_photo_references', $photos_array);
}