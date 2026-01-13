<?php
/**
 * Plugin Name: Inline Related Articles
 * Description: Inline Related Articles Pro is a lightweight but powerful editorial plugin that automatically injects contextually relevant articles inside the body of your posts.
 * Version: 2.0.2
 * Author: Ese Amadasun
 * Author URI: https://amadasunese.pythonanywhere.com
 */

if (!defined('ABSPATH')) exit;

define('IRA_PATH', plugin_dir_path(__FILE__));
define('IRA_URL', plugin_dir_url(__FILE__));

// Ensure files exist before requiring to prevent fatal errors during dev
if (file_exists(IRA_PATH . 'admin-settings.php')) require_once IRA_PATH . 'admin-settings.php';
if (file_exists(IRA_PATH . 'ai-relevance.php')) require_once IRA_PATH . 'ai-relevance.php';

/**
 * Enqueue assets
 */
add_action('wp_enqueue_scripts', function () {
    if (is_single()) {
        wp_enqueue_style(
            'ira-styles',
            IRA_URL . 'assets/inline-related.css',
            [],
            '2.0'
        );
    }
});

/**
 * Inject multiple inline blocks
 */
add_filter('the_content', function ($content) {

    // Basic checks + prevent injection in feeds or REST API previews
    if (!is_single() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $settings = get_option('ira_settings');
    if (empty($settings['paragraphs'])) {
        return $content;
    }

    // Prevent infinite loops
    static $is_processing = false;
    if ($is_processing) return $content;
    $is_processing = true;

    global $post;
    
    // Clean and parse paragraph positions
    $raw_positions = explode(',', $settings['paragraphs']);
    $insert_positions = array_filter(array_map('intval', $raw_positions));

    // Split content carefully
    $paragraphs = explode('</p>', $content);
    
    foreach ($insert_positions as $pos) {
        // Adjust for 1-based indexing to 0-based array index
        $index = $pos - 1;

        if (isset($paragraphs[$index])) {
            $related_html = ira_get_related_posts_html($post->ID);
            
            if ($related_html) {
                // Append the block to the specific paragraph index
                $paragraphs[$index] .= $related_html;
            }
        }
    }

    $content = implode('</p>', $paragraphs);

    // Reset loop guard
    $is_processing = false;
    return $content;
}, 20);