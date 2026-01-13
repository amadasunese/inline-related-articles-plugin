<?php
if (!defined('ABSPATH')) exit;

/**
 * Register the Menu Item
 */
add_action('admin_menu', function () {
    add_options_page(
        'Inline Related Articles',
        'Inline Related Articles',
        'manage_options',
        'inline-related-articles',
        'ira_settings_page'
    );
});

/**
 * Register Settings and Sanitize Input
 */
add_action('admin_init', function () {
    register_setting('ira_settings_group', 'ira_settings', [
        'sanitize_callback' => 'ira_sanitize_settings'
    ]);
});

/**
 * Sanitization Logic
 */
function ira_sanitize_settings($input) {
    $new_input = [];

    // Clean up paragraph string (remove spaces, ensure only numbers and commas)
    if (isset($input['paragraphs'])) {
        $new_input['paragraphs'] = preg_replace('/[^0-9,]/', '', $input['paragraphs']);
    }

    // Ensure count is a valid integer within your range
    if (isset($input['count'])) {
        $count = intval($input['count']);
        $new_input['count'] = ($count >= 1 && $count <= 6) ? $count : 3;
    }

    // Handle Checkbox: If it's not in the input, set it to 0
    $new_input['ai'] = isset($input['ai']) ? 1 : 0;

    return $new_input;
}

function ira_settings_page() {
    // Fetch settings with defaults to avoid "undefined index" notices
    $defaults = [
        'paragraphs' => '3,7',
        'count'      => 3,
        'ai'         => 0
    ];
    $settings = wp_parse_args(get_option('ira_settings', []), $defaults);
    ?>
    <div class="wrap">
        <h1>Inline Related Articles Settings</h1>

        <form method="post" action="options.php">
            <?php 
                settings_fields('ira_settings_group'); 
                do_settings_sections('ira_settings_group');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ira_paragraphs">Paragraph Positions</label></th>
                    <td>
                        <input type="text" id="ira_paragraphs" name="ira_settings[paragraphs]"
                               value="<?php echo esc_attr($settings['paragraphs']); ?>" class="regular-text">
                        <p class="description">Where to insert blocks. Example: <code>3, 7, 10</code></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="ira_count">Articles per Block</label></th>
                    <td>
                        <input type="number" id="ira_count" name="ira_settings[count]"
                               value="<?php echo esc_attr($settings['count']); ?>" min="1" max="6">
                        <p class="description">How many links to show in each related section.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">AI Relevance</th>
                    <td>
                        <label for="ira_ai">
                            <input type="checkbox" id="ira_ai" name="ira_settings[ai]"
                                   value="1" <?php checked(1, $settings['ai']); ?>>
                            Enable AI-powered relevance scoring
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}