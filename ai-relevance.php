<?php
if (!defined('ABSPATH')) exit;

function ira_get_related_posts_html($post_id) {
    $settings = get_option('ira_settings');
    $count    = isset($settings['count']) ? intval($settings['count']) : 3;
    $use_ai   = !empty($settings['ai']);
    $heading  = !empty($settings['heading']) ? $settings['heading'] : 'Read Next';

    $current_post = get_post($post_id);
    if (!$current_post) return '';

    $current_categories = wp_get_post_categories($post_id);

    $args = array(
        'post_type'      => 'post',
        'post__not_in'   => array($post_id),
        'posts_per_page' => 15,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
        'category__in'   => $current_categories,
        'orderby'        => 'date',
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        unset($args['category__in']);
        $query = new WP_Query($args);
    }

    if (!$query->have_posts()) return '';

    $scored_posts = array();
    $current_text = strtolower(wp_strip_all_tags($current_post->post_content));

    foreach ($query->posts as $p) {
        $score = 0;

        if ($use_ai) {
            $compare_text = strtolower(wp_strip_all_tags($p->post_content));
            $score = ira_similarity_score($current_text, $compare_text);
        } else {
            $loop_post_cats = wp_get_post_categories($p->ID);
            $common_cats    = array_intersect($current_categories, $loop_post_cats);
            $score          = count($common_cats) * 10;
        }

        $scored_posts[] = array(
            'title' => get_the_title($p->ID),
            'link'  => get_permalink($p->ID),
            'score' => $score
        );
    }

    usort($scored_posts, function ($a, $b) {
        return $b['score'] - $a['score'];
    });

    $scored_posts = array_slice($scored_posts, 0, $count);

    ob_start(); ?>
    <div class="ira-inline-related">
        <h4><?php echo esc_html($heading); ?></h4>
        <ul class="ira-list">
            <?php foreach ($scored_posts as $post_item): ?>
                <li class="ira-item">
                    <a href="<?php echo esc_url($post_item['link']); ?>">
                        <?php echo esc_html($post_item['title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function ira_similarity_score($text1, $text2) {
    $words1 = array_count_values(str_word_count($text1, 1));
    $words2 = array_count_values(str_word_count($text2, 1));
    $common = array_intersect_key($words1, $words2);
    $score  = 0;
    foreach ($common as $word => $count) {
        $score += min($words1[$word], $words2[$word]);
    }
    return $score;
}