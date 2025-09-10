<?php
/*
Plugin Name: Custom Post Type Manager
Description: A plugin to manage custom post types.
Version: 1.0
Author: Mayur Chauhan
Author URI: https://mayurportfolio.tech/
License: GPL2
Text Domain: custom-post-type-manager
*/

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Register default "Books" custom post type
function cptm_register_default_cpt()
{
    $labels = array(
        'name'               => __('Books', 'custom-post-type-manager'),
        'singular_name'      => __('Book', 'custom-post-type-manager'),
        'add_new'            => __('Add New', 'custom-post-type-manager'),
        'add_new_item'       => __('Add New Book', 'custom-post-type-manager'),
        'edit_item'          => __('Edit Book', 'custom-post-type-manager'),
        'new_item'           => __('New Book', 'custom-post-type-manager'),
        'all_items'          => __('All Books', 'custom-post-type-manager'),
        'view_item'          => __('View Book', 'custom-post-type-manager'),
        'search_items'       => __('Search Books', 'custom-post-type-manager'),
        'not_found'          => __('No books found', 'custom-post-type-manager'),
        'not_found_in_trash' => __('No books found in Trash', 'custom-post-type-manager'),
        'menu_name'          => __('Books', 'custom-post-type-manager'),
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'books'),
        'supports'           => array('title', 'editor', 'thumbnail'),
        'show_in_rest'       => true,
    );
    register_post_type('book', $args);
}

// Register custom post types from settings
function cptm_register_dynamic_cpts()
{
    $cpts = get_option('cptm_custom_post_types', array());
    if (! empty($cpts) && is_array($cpts)) {
        foreach ($cpts as $cpt) {
            $slug = sanitize_key($cpt);
            $labels = array(
                'name'               => ucwords(str_replace('_', ' ', $slug)),
                'singular_name'      => ucwords(str_replace('_', ' ', $slug)),
                'add_new'            => __('Add New', 'custom-post-type-manager'),
                'add_new_item'       => sprintf(__('Add New %s', 'custom-post-type-manager'), ucwords($slug)),
                'edit_item'          => sprintf(__('Edit %s', 'custom-post-type-manager'), ucwords($slug)),
                'new_item'           => sprintf(__('New %s', 'custom-post-type-manager'), ucwords($slug)),
                'all_items'          => sprintf(__('All %s', 'custom-post-type-manager'), ucwords($slug)),
                'view_item'          => sprintf(__('View %s', 'custom-post-type-manager'), ucwords($slug)),
                'search_items'       => sprintf(__('Search %s', 'custom-post-type-manager'), ucwords($slug)),
                'not_found'          => __('Not found', 'custom-post-type-manager'),
                'not_found_in_trash' => __('Not found in Trash', 'custom-post-type-manager'),
                'menu_name'          => ucwords(str_replace('_', ' ', $slug)),
            );
            $args = array(
                'labels'             => $labels,
                'public'             => true,
                'has_archive'        => true,
                'rewrite'            => array('slug' => $slug),
                'supports'           => array('title', 'editor', 'thumbnail'),
                'show_in_rest'       => true,
            );
            register_post_type($slug, $args);
        }
    }
}


// Add settings page
function cptm_add_settings_page()
{
    add_options_page(
        __('Custom Post Type Manager', 'custom-post-type-manager'),
        __('CPT Manager', 'custom-post-type-manager'),
        'manage_options',
        'cptm-settings',
        'cptm_render_settings_page'
    );
}


// Register settings
function cptm_register_settings()
{
    register_setting(
        'cptm_settings_group',
        'cptm_custom_post_types',
        array(
            'type' => 'array',
            'sanitize_callback' => 'cptm_sanitize_cpts',
            'default' => array(),
        )
    );
}

// Sanitize CPTs array
function cptm_sanitize_cpts($input)
{
    $output = array();
    if (is_array($input)) {
        foreach ($input as $cpt) {
            $cpt = sanitize_key($cpt);
            if (! empty($cpt) && strlen($cpt) <= 20 && ! in_array($cpt, $output)) {
                $output[] = $cpt;
            }
        }
    }
    return $output;
}


// Render settings page
function cptm_render_settings_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    // Show settings messages
    settings_errors('cptm_messages');
?>
    <div class="wrap">
        <h1><?php _e('Custom Post Type Manager', 'custom-post-type-manager'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cptm_settings_group');
            $cpts = get_option('cptm_custom_post_types', array());
            ?>
            <h2><?php _e('Add New Custom Post Type', 'custom-post-type-manager'); ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Custom Post Type Slug', 'custom-post-type-manager'); ?></th>
                    <td>
                        <input type="text" name="cptm_new_cpt" value="" maxlength="20" pattern="[a-z0-9_]+" required />
                        <p class="description"><?php _e('Lowercase letters, numbers, and underscores only. Max 20 chars.', 'custom-post-type-manager'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Add Custom Post Type', 'custom-post-type-manager'), 'primary', 'cptm_add_cpt'); ?>
        </form>
        <hr>
        <h2><?php _e('Registered Custom Post Types', 'custom-post-type-manager'); ?></h2>
        <ul>
            <li><strong><?php _e('book (default)', 'custom-post-type-manager'); ?></strong></li>
            <?php
            if (! empty($cpts)) {
                foreach ($cpts as $cpt) {
                    $delete_url = wp_nonce_url(
                        admin_url('options-general.php?page=cptm-settings&cptm_delete_cpt=' . urlencode($cpt)),
                        'cptm_delete_cpt_' . $cpt
                    );
                    echo '<li>' . esc_html($cpt) . ' <a href="' . esc_url($delete_url) . '" style="color:red;" onclick="return confirm(\'Are you sure you want to delete this custom post type?\')">' . __('Delete', 'custom-post-type-manager') . '</a></li>';
                }
            } else {
                echo '<li>' . __('No custom post types registered.', 'custom-post-type-manager') . '</li>';
            }
            ?>
        </ul>
    </div>
<?php
}

// Handle form submission and deletion
function cptm_handle_form_submission()
{
    // Handle add
    if (
        isset($_POST['cptm_add_cpt']) &&
        isset($_POST['cptm_new_cpt']) &&
        current_user_can('manage_options') &&
        check_admin_referer('cptm_settings_group-options')
    ) {
        $new_cpt = sanitize_key($_POST['cptm_new_cpt']);
        if (! empty($new_cpt) && strlen($new_cpt) <= 20) {
            $cpts = get_option('cptm_custom_post_types', array());
            if (! in_array($new_cpt, $cpts) && $new_cpt !== 'book') {
                $cpts[] = $new_cpt;
                update_option('cptm_custom_post_types', $cpts);
                flush_rewrite_rules();
                add_settings_error(
                    'cptm_messages',
                    'cptm_message',
                    __('Custom post type added!', 'custom-post-type-manager'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'cptm_messages',
                    'cptm_message',
                    __('Custom post type already exists or is reserved.', 'custom-post-type-manager'),
                    'error'
                );
            }
        } else {
            add_settings_error(
                'cptm_messages',
                'cptm_message',
                __('Invalid custom post type slug.', 'custom-post-type-manager'),
                'error'
            );
        }

        wp_redirect(admin_url('options-general.php?page=cptm-settings'));
        exit;
    }

    // Handle delete
    if (
        isset($_GET['cptm_delete_cpt']) &&
        current_user_can('manage_options')
    ) {
        $delete_cpt = sanitize_key($_GET['cptm_delete_cpt']);
        if (! empty($delete_cpt) && $delete_cpt !== 'book' && wp_verify_nonce($_GET['_wpnonce'], 'cptm_delete_cpt_' . $delete_cpt)) {
            $cpts = get_option('cptm_custom_post_types', array());
            $key = array_search($delete_cpt, $cpts);
            if ($key !== false) {
                unset($cpts[$key]);
                update_option('cptm_custom_post_types', array_values($cpts));
                flush_rewrite_rules();
                add_settings_error(
                    'cptm_messages',
                    'cptm_message',
                    __('Custom post type deleted!', 'custom-post-type-manager'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'cptm_messages',
                    'cptm_message',
                    __('Custom post type not found.', 'custom-post-type-manager'),
                    'error'
                );
            }
        } else {
            add_settings_error(
                'cptm_messages',
                'cptm_message',
                __('Invalid request or cannot delete default post type.', 'custom-post-type-manager'),
                'error'
            );
        }

        wp_redirect(admin_url('options-general.php?page=cptm-settings'));
        exit;
    }
}

// Flush rewrite rules on activation/deactivation
function cptm_flush_rewrite_rules()
{
    flush_rewrite_rules();
}

// Hooks
add_action('init', 'cptm_register_default_cpt');
add_action('init', 'cptm_register_dynamic_cpts');
add_action('admin_menu', 'cptm_add_settings_page');
add_action('admin_init', 'cptm_register_settings');
add_action('admin_init', 'cptm_handle_form_submission');
register_activation_hook(__FILE__, 'cptm_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'cptm_flush_rewrite_rules');
// End of plugin file