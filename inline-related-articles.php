<?php
/**
 * Plugin Name: Inline Related Articles Pro
 * Description: Insert multiple inline related articles with AI-powered relevance.
 * Version: 2.0.2
 * Author: Ese Amadasun
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

    // 1. Basic checks + prevent injection in feeds or REST API previews
    if (!is_single() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $settings = get_option('ira_settings');
    if (empty($settings['paragraphs'])) {
        return $content;
    }

    // 2. Prevent infinite loops
    static $is_processing = false;
    if ($is_processing) return $content;
    $is_processing = true;

    global $post;
    
    // 3. Clean and parse paragraph positions
    $raw_positions = explode(',', $settings['paragraphs']);
    $insert_positions = array_filter(array_map('intval', $raw_positions));

    // 4. Split content carefully
    $paragraphs = explode('</p>', $content);
    
    // We iterate backwards or use a counter offset to avoid shifting indices 
    // when we add content. However, modifying the array directly works if 
    // we use a temporary storage array.
    foreach ($insert_positions as $pos) {
        // Adjust for 1-based indexing (Users usually think "Paragraph 1" is the first one)
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

    $is_processing = false; // Reset loop guard
    return $content;
}, 20); // Priority 20 ensures we run AFTER standard WP formatting