<?php

function add_to_header()
{
    wp_enqueue_style('video-js-style', 'https://unpkg.com/video.js@7/dist/video-js.min.css');
    wp_enqueue_style('video-js-city-style', 'https://unpkg.com/@videojs/themes@1/dist/city/index.css');
    wp_enqueue_script('video-js-script', "https://vjs.zencdn.net/7.11.4/video.min.js");
    wp_enqueue_script('vimeo-script', "https://player.vimeo.com/api/player.js");

    wp_enqueue_script('slick-script', "https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js");
    wp_enqueue_style('slick-style', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.css');
    wp_enqueue_script('play-vidyard-script', "https://play.vidyard.com/embed/v4.js");
    wp_enqueue_script('single-tv-script', "/wp-content/themes/smartmag-child-for-pnima/scripts/single-tv-script.js");
    wp_enqueue_style('archive-tv-style', "/wp-content/themes/smartmag-child-for-pnima/css/archive-tv-style.css");
    wp_enqueue_style('single-tv-style', "/wp-content/themes/smartmag-child-for-pnima/css/single-tv-style.css");
  
}


add_action("wp_head", "add_to_header", 1);

get_header();

require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/smartmag-child-for-pnima/partials/popup-single.php';



$video_arr = get_field("video_file");
$poster = null;
$poster_url = null;
$video_url = null;
if ($video_arr) {
    $video_url =  $video_arr["url"];
}

$poster = get_field("webp_img");
if (!$poster)
    $poster = get_field("poster_image_array");
$poster_url =  $poster["url"];
if (!$video_url) {
    $video_url = get_field("video_url");
}

$open_free = get_field("open_free");
?>
<section class="single-video-section">
    <div class="single-video<?php if ($open_free) echo ' free'; ?>">
        <div class="overlay"></div>
        <video id="main_video"   class="video-js vjs-default-skin" controls preload="none" poster="<?php echo $poster_url ?>" data-setup='{"fluid": true}'>
            <source src="<?php echo ($video_url); ?>" type="video/mp4">
        </video>
    </div>

    <?php

    $terms = get_the_terms($post->ID, 'series_category');
    $series = null;
    foreach ($terms as $term) {
        $series =  $term->term_taxonomy_id;
    }

    $s_args = array(
        'post_type' => 'tv',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'series_category',
                'field' => 'id',
                'terms' => $series
            )
        ),
    );
    $related_items = new WP_Query($s_args);

    $current_post = get_post($post->ID);
    $more_videos = count($related_items->posts);
    $first = true;
    $next_video = null;
    $cur_index = 0;
    if (count($related_items->posts) > 1) :
        echo '<section class="more-in-series">';
        echo '<h2>פרקים נוספים בסדרה</h2>';
        echo '<div class="more-videos-in-series">';


        $all_posts  = array();
        $prev_arr = array();
        $next_arr = array();
        $prev = true;
        foreach (($related_items->posts) as $item_index => $item) {
            array_push($all_posts, $item);
            if ($item == $current_post) {
                $cur_index = $item_index;
                $prev = false;
            } else if ($prev) {
                array_push($prev_arr, $item);
            } else {
                array_push($next_arr, $item);
            }
        }

        $all_posts  = array_merge($next_arr,   $prev_arr);

        foreach ($all_posts as $item) {
            if ($first) {
                $next_video = get_post_permalink($item);
                $first = false;
            }
            $open_video = $item->open_free;
            $image = $item->webp_img;
            if (!$image)
                $image = $item->poster_image_array;
    ?>
            <div class="more-videos-item">
                <a href="<?php echo get_post_permalink($item); ?>">
                    <?php
                    if ($open_video)
                        echo '<div class="open-free">פתוח לצפיה בחינם</div>';
                    ?>

                    <img class="poster-img lazy" data-src="<?php echo wp_get_attachment_image_src($image, '640')[0]; ?>" src="<?php echo wp_get_attachment_image_src($image, '640')[0]; ?>" alt="">
                    <label class="series-details"><?php echo ($item->video_name); ?></label>
                </a>

            </div>
        <?php
        }
        ?>
    <?php
        echo '</div>';
        echo '</section>';
    endif;
    wp_reset_postdata();
    ?>

</section>
<?php

add_action(
    'get_footer',
    function () use ($next_video, $poster) {
?>
    <script>
        addNextVideoButton("<?php echo $next_video ?>");
        gradiantVideo('<?php echo $poster["url"]; ?>');
    </script>
<?php
    }
);
?>