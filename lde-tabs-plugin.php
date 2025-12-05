<?php

/**
 * Plugin Name: LDE Tabs Plugin
 * Plugin URI: https://les-ey.online
 * Description: Creates a responsive tab menu to toggle
 * visibility of Block Editor content.
 * Usage: Place [lde_tabs config="Label 1:id-1, Label 2:id-2"]
 * where you want the tabs. 
 * Ensure content blocks (like Code Blocks) have matching HTML 
 * Anchor IDs and the CSS class 'lde-tab-content'
 * (Use Advanced in the Block settings to set these values)
 * Version: 0.9.1
 * Author: Les Ey
 * Author URI: https://les-ey.online/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lde-tabs
 */

// Prevent direct access to the plugin file
if (! defined('ABSPATH')) exit;

// Define global variable to store the page hook (must be defined globally)
global $lde_tabs_page_hook; 

// --- 1. ADMIN MENU SETUP & OPTIONS API ---

/**
 * Add the plugin settings link to the WP Admin Settings menu and capture its hook.
 */
function lde_tabs_add_admin_menu()
{
    global $lde_tabs_page_hook; // Use the global variable
    
    // Capture the return value (the page hook)
    $lde_tabs_page_hook = add_options_page(
        'LDE Tabs Settings',
        'LDE Tabs',
        'manage_options',
        'lde_tabs',
        'lde_tabs_settings_page_html'
    );
}
add_action('admin_menu', 'lde_tabs_add_admin_menu');

/**
 * Adds a 'Settings' link to the plugin row on the plugins page.
 *
 * @param array $links Array of action links for the plugin.
 * @return array Modified array of action links.
 */
function lde_tabs_plugin_action_links($links) {
    // 1. Create the link URL pointing to your settings page slug ('lde_tabs')
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=lde_tabs')) . '">' . __('Settings', 'lde-tabs') . '</a>';

    // 2. Prepend the settings link to the existing links array
    array_unshift($links, $settings_link);

    return $links;
}

// 3. Hook into the specific filter for your plugin file
$plugin_file = plugin_basename(__FILE__);
add_filter("plugin_action_links_{$plugin_file}", 'lde_tabs_plugin_action_links');

/**
 * Register settings and fields for the Options API.
 */
function lde_tabs_register_settings()
{
    register_setting('lde_tabs_options_group', 'lde_tabs_settings', 'lde_tabs_sanitize_input');

    add_settings_section(
        'lde_tabs_design_section',
        'Tab Design & Spacing Settings',
        'lde_tabs_design_section_callback',
        'lde_tabs'
    );

    // --- COLOR FIELDS: TAB STATES ---
    add_settings_field('tab_selected_color', 'Selected Tab Color', 'lde_tabs_color_field_callback', 'lde_tabs', 'lde_tabs_design_section', ['id' => 'tab_selected_color', 'default' => '#0073aa', 'description' => 'Sets the text/border color for the active tab.']);
    add_settings_field('tab_hover_bg_color', 'Tab Hover Background', 'lde_tabs_color_field_callback', 'lde_tabs', 'lde_tabs_design_section', ['id' => 'tab_hover_bg_color', 'default' => '#eee', 'description' => 'Sets the background color when hovering over an unselected tab.']);
    add_settings_field('tab_unselected_bg_color', 'Unselected Tab Background', 'lde_tabs_color_field_callback', 'lde_tabs', 'lde_tabs_design_section', ['id' => 'tab_unselected_bg_color', 'default' => '#f7f7f7', 'description' => 'Background color for all inactive tabs.']);
    add_settings_field('tab_unselected_text_color', 'Unselected Tab Text Color', 'lde_tabs_color_field_callback', 'lde_tabs', 'lde_tabs_design_section', ['id' => 'tab_unselected_text_color', 'default' => '#555', 'description' => 'Text color for all inactive tabs.']);

    // --- COLOR FIELD: CONTENT ---
    add_settings_field('content_bg_color', 'Content Background Color', 'lde_tabs_color_field_callback', 'lde_tabs', 'lde_tabs_design_section', ['id' => 'content_bg_color', 'default' => '#f9f9f9', 'description' => 'Sets the background color for the active content block.']);

    // --- MARGIN FIELDS: CONTENT ---
    add_settings_field('content_vertical_margin', 'Content Vertical Padding (px)', 'lde_tabs_margin_field_callback', 'lde_tabs', 'lde_tabs_design_section', ['id' => 'content_vertical_margin', 'default' => '20', 'description' => 'Top and bottom padding for the content area (in pixels).']);
    add_settings_field('content_side_margin', 'Content Side Padding (px)', 'lde_tabs_margin_field_callback', 'lde_tabs', 'lde_tabs_design_section', ['id' => 'content_side_margin', 'default' => '20', 'description' => 'Left and right padding for the content area (in pixels).']);
}
add_action('admin_init', 'lde_tabs_register_settings');

// --- 2. CALLBACK FUNCTIONS (REQUIRED) ---

/**
 * Renders the section text.
 */
function lde_tabs_design_section_callback()
{
    echo '<p>Define the colors and spacing for your tabs and content blocks.</p>';
}

/**
 * Renders the margin input field.
 */
function lde_tabs_margin_field_callback($args)
{
    $options = get_option('lde_tabs_settings');
    $value = isset($options[$args['id']]) ? absint($options[$args['id']]) : absint($args['default']);

    printf(
        '<input type="number" name="lde_tabs_settings[%s]" id="%s" value="%s" min="0" style="width: 80px;" />',
        esc_attr($args['id']),
        esc_attr($args['id']),
        esc_attr($value)
    );
    if (isset($args['description'])) {
        printf('<p class="description">%s</p>', esc_html($args['description']));
    }
}

/**
 * Renders a color picker field (Used for all color options).
 */
function lde_tabs_color_field_callback($args)
{
    $options = get_option('lde_tabs_settings');
    $value = isset($options[$args['id']]) ? sanitize_hex_color($options[$args['id']]) : $args['default'];

    printf(
        '<input type="text" name="lde_tabs_settings[%s]" id="%s" value="%s" class="lde-color-field" data-default-color="%s" />',
        esc_attr($args['id']),
        esc_attr($args['id']),
        esc_attr($value),
        esc_attr($args['default'])
    );
    if (isset($args['description'])) {
        printf('<p class="description">%s</p>', esc_html($args['description']));
    }
}

/**
 * Handles the reset action for the plugin settings.
 */
function lde_tabs_reset_handler() {
    // 1. Check if the reset button was pressed
    if (isset($_POST['reset_settings'])) {
        
        // 2. Security check using the nonce field
        if (isset($_POST['lde_tabs_reset_nonce']) && wp_verify_nonce($_POST['lde_tabs_reset_nonce'], 'lde_tabs_reset_action')) {
            
            // 3. Delete the entire option from the database
            delete_option('lde_tabs_settings');
            
            // 4. Redirect back to the settings page with a success message
            // This prevents the form from resubmitting on refresh.
            wp_redirect(add_query_arg('settings-updated', 'reset', wp_get_referer()));
            exit;
        } else {
            // Handle failed nonce/security check
            wp_die('Security check failed. Please try again.');
        }
    }
}
// Hook the handler early in the admin sequence
add_action('admin_init', 'lde_tabs_reset_handler');

/**
 * Renders the main settings page HTML.
 */
function lde_tabs_settings_page_html()
{
    if (! current_user_can('manage_options')) return;
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        
        <?php settings_fields('lde_tabs_options_group'); ?> 
        <?php do_settings_sections('lde_tabs'); ?>
        
        <?php submit_button('Save Tab Settings', 'primary', 'save_settings'); ?> 
    </form>
    
    <hr>
    
    <form method="post" action="">
        <p>Clicking this button will revert all settings (colors and padding) to their original default values.</p>
        
        <input type="hidden" name="lde_tabs_reset_nonce" value="<?php echo wp_create_nonce('lde_tabs_reset_action'); ?>" />
        
        <?php submit_button('Reset to Plugin Defaults', 'secondary', 'reset_settings'); ?>
    </form>
</div>
<?php
}

/**
 * Custom sanitization function (ensures colors are hex codes and margins are safe numbers).
 */
function lde_tabs_sanitize_input($input)
{
    $sanitized_input = array();
    foreach ($input as $key => $value) {
        if (strpos($key, 'color') !== false) {
            $sanitized_input[$key] = sanitize_hex_color($value);
        } elseif (strpos($key, 'margin') !== false) {
            $sanitized_input[$key] = absint($value); // Use absint for safe integer values
        } else {
            $sanitized_input[$key] = sanitize_text_field($value);
        }
    }
    return $sanitized_input;
}

// --- 3. ASSET ENQUEUING ---

/**
 * Helper function to determine which version of the asset to load (minified or not).
 * Uses the standard .min convention and prioritizes loading the minified version.
 */
function lde_tabs_get_asset_url($filename) {
    // Determine the minified filename using the standard .min convention
    $minified_filename = str_replace( 
        ['.js', '.css'], 
        ['.min.js', '.min.css'], 
        $filename 
    );
    
    $dir = plugin_dir_path(__FILE__);
    $url = plugin_dir_url(__FILE__);
    
    // Check if the minified file exists on the filesystem
    if (file_exists($dir . $minified_filename)) {
        // Load the minified version
        return $url . $minified_filename;
    }
    
    // Fallback to the regular (unminified) version
    return $url . $filename;
}

// --- ADMIN ASSETS ---

/**
 * Enqueue the color picker script and necessary styles for the Admin page.
 */
function lde_tabs_enqueue_color_picker($hook_suffix)
{
    // Ensure we only load these assets on our specific settings page.
    if ('settings_page_lde_tabs' !== $hook_suffix) {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    // Custom script to initialize the color picker on our field
    wp_add_inline_script(
        'wp-color-picker',
        'jQuery(document).ready(function($) {
            $(".lde-color-field").wpColorPicker();
        });'
    );
}
add_action('admin_enqueue_scripts', 'lde_tabs_enqueue_color_picker');


// --- FRONT-END ASSETS ---

/**
 * Enqueue the plugin's JavaScript and base structural CSS for the front-end.
 */
function lde_tabs_assets()
{
    // The version number should be dynamic or set as a constant
    $version = '1.0.0';
    
    // Enqueue static CSS stylesheet (structural only)
    wp_enqueue_style(
        'lde-tabs-style',
        lde_tabs_get_asset_url('lde-tabs-plugin.css'), // <-- Uses the helper function
        array(),
        $version
    );
    
    // Enqueue JavaScript (behavioral logic)
    wp_enqueue_script(
        'lde-tabs-script',
        lde_tabs_get_asset_url('lde-tabs-plugin.js'), // <-- Uses the helper function
        array('jquery'),
        $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'lde_tabs_assets');

// --- 4. DYNAMIC CSS GENERATION ---

/**
 * Generates and enqueues dynamic CSS based on saved settings.
 */
function lde_tabs_dynamic_css() {
    if ( ! is_admin() ) {
        
        $options = get_option('lde_tabs_settings', array());
        
        $defaults = array(
            'tab_selected_color'        => '#0073aa', 'content_bg_color' => '#f9f9f9',
            'content_vertical_margin'   => '20', 'content_side_margin' => '20',
            'tab_hover_bg_color'        => '#eee', 'tab_unselected_bg_color' => '#f7f7f7',
            'tab_unselected_text_color' => '#555',
        );
        
        $settings = wp_parse_args($options, $defaults);

        // Sanitize and prepare variables
        $selected_color = sanitize_hex_color($settings['tab_selected_color']);
        $content_bg     = sanitize_hex_color($settings['content_bg_color']);
        $v_margin       = absint($settings['content_vertical_margin']) . 'px';
        $s_margin       = absint($settings['content_side_margin']) . 'px';
        $hover_bg       = sanitize_hex_color($settings['tab_hover_bg_color']);
        $unselected_bg  = sanitize_hex_color($settings['tab_unselected_bg_color']);
        $unselected_text= sanitize_hex_color($settings['tab_unselected_text_color']);

        $css = "
            /* --- Dynamic LDE Tabs Styling (Max Specificity) --- */
            
            .lde-tabs-container {
                padding-bottom: 8px;
            }

            .lde-tab-button {
                background-color: {$unselected_bg} !important;
                color: {$unselected_text} !important;
            }
            
            .lde-tab-button:hover:not(.lde-active) {
                background-color: {$hover_bg} !important;
            }

            .lde-tab-button.lde-active {
                color: {$selected_color} !important;
                border-bottom: 2px solid {$selected_color} !important;
            }

            .lde-tab-content {
                background-color: {$content_bg} !important;
                
                /* Padding rules */
                padding-top: {$v_margin} !important;
                padding-bottom: {$v_margin} !important;
                padding-left: {$s_margin} !important;
                padding-right: {$s_margin} !important;

                /* Fix for the tab/content gap */
                border: 1px solid #ddd;
                border-top: 1px solid #ddd !important;
                margin-top: -8px !important; /* <-- CRITICAL FIX: Increased negative margin */
                border-radius: 0 0 4px 4px;
            }
        ";
        
        wp_add_inline_style('lde-tabs-style', $css);
    }
}
add_action('wp_enqueue_scripts', 'lde_tabs_dynamic_css');


// --- 5. SHORTCODE HANDLER (REQUIRED) ---
/**
 * Shortcode handler: renders the tab buttons inside a unique group container.
 */
function lde_tabs_shortcode($atts) {
    $atts = shortcode_atts(array(
        'config' => '',
        'group'  => 'set1', // <-- This is the default setting
    ), $atts);
    
    if (empty($atts['config'])) return '';

    $group_id = sanitize_html_class($atts['group']); // Sanitize the group ID
    
    $pairs = explode(',', $atts['config']);
    $tabs = [];

    foreach ($pairs as $index => $pair) {
        $parts = explode(':', sanitize_text_field(trim($pair)));
        if (count($parts) == 2) {
            $id = sanitize_html_class(trim($parts[1]));
            $tabs[] = [
                'label' => trim($parts[0]),
                'id' => $id
            ];
        }
    }

    ob_start();
    ?>
    
    <div class="lde-tab-group-wrapper" id="lde-tabs-group-<?php echo esc_attr($group_id); ?>">
        
        <div class="lde-tabs-container">
            <?php foreach ($tabs as $index => $tab): ?>
                <button 
                    class="lde-tab-button <?php echo $index === 0 ? 'lde-active' : ''; ?>" 
                    data-target="<?php echo esc_attr($tab['id']); ?>"
                    data-group="<?php echo esc_attr($group_id); ?>"> <?php echo esc_html($tab['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('lde_tabs', 'lde_tabs_shortcode');


// --- 6. ADMIN HELP TABS (REQUIRES ADMIN MENU HOOK) ---

// --- 6. ADMIN HELP TABS (REQUIRES ADMIN MENU HOOK) ---

/**
 * Adds context-sensitive help tabs to the LDE Tabs settings page.
 */
function lde_tabs_add_help_tabs() {
    // This function runs only when the screen is active, so we grab the screen object directly.
    $screen = get_current_screen(); 

    // --- Tab 1: Usage Instructions ---
    $screen->add_help_tab(array(
        'id'      => 'lde_tabs_instructions',
        'title'   => __('Usage Instructions', 'lde-tabs'),
        'content' => '
            <h2>Plugin Usage: Three Steps</h2>
            <p><strong>1. Shortcode:</strong> Use <code>[lde_tabs config="Label:ID, Label:ID"]</code> where you want the tabs to appear. Use the <code>group</code> attribute for multiple sets (e.g., <code>group="set2"</code>).</p>
            <p><strong>Example Shortcode:</strong></p>
            <pre><code>[lde_tabs group="set1" config="PHP Code:php-sec, CSS Styles:css-sec"]</code></pre>
            <p>* The <code>group</code> attribute is **optional** and defaults to <code>set1</code>. Only use it when adding a second, independent set of tabs to the same page (e.g., <code>group="set2"</code>).</p>
            <p><strong>2. Content Block:</strong> Create a single block (e.g., Group Block or Code Block) for each tab\'s content.</p>
            <p><strong>3. Advanced Settings:</strong> In the **Advanced** panel for each content block, set the <strong>HTML Anchor</strong> to match the shortcode\'s ID (e.g., <code>php-sec</code>) and the **Additional CSS class(es)** to <code>lde-tab-content</code>.</p>
            <p>Repeat for every tab content block.</p>
        ',
    ));

    // --- Tab 2: Settings Overview ---
    $screen->add_help_tab(array(
        'id'      => 'lde_tabs_settings_info',
        'title'   => __('Settings Overview', 'lde-tabs'),
        'content' => '
            <p>Use the settings below to customize the appearance of your tabs globally:</p>
            <ul>
                <li><strong>Colors:</strong> Adjust the background and text color for active, inactive, and hover states.</li>
                <li><strong>Content Padding:</strong> Control the internal spacing (padding) of the content box that appears below the active tab.</li>
                <li>**Reset Button:** Use the "Reset to Plugin Defaults" button to restore all original color and spacing values.</li>
            </ul>
        ',
    ));
    
    // --- Adding contextual help text (Sidebar) ---
    $screen->set_help_sidebar(
        '<p><strong>Need Support?</strong></p>' .
        '<p>For a detailed guide, visit the <a href="https://www.les-ey.online/free-wordpress-tab-plugin/" target="_blank">LDE Tabs Plugin Blog Post</a>.</p>'
    );
}

/**
 * Hooks the help tabs function to the specific settings page load hook.
 */
function lde_tabs_hook_help_tabs() {
    global $lde_tabs_page_hook;
    
    // Check if the hook was successfully set by add_options_page()
    if (!empty($lde_tabs_page_hook)) {
        add_action('load-' . $lde_tabs_page_hook, 'lde_tabs_add_help_tabs');
    }
}
// Run this immediately after the admin menu is set up
add_action('admin_menu', 'lde_tabs_hook_help_tabs');