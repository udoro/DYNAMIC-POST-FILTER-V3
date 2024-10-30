<?php
/*
Plugin Name: Dynamic Post Filter
Description: Filter posts by custom taxonomies in Bricks Builder query loops
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-admin-settings.php';

class DynamicPostFilter {
    private $admin_settings;
    private $current_filter = array();

    public function __construct() {
        $this->admin_settings = new DPF_Admin_Settings();
        
        add_action('init', array($this, 'register_taxonomies'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('bricks/query/run', array($this, 'modify_bricks_query'), 10, 3);
        add_filter('bricks/query/loop_object', array($this, 'modify_loop_object'), 10, 3);
        add_action('wp_ajax_get_filtered_terms', array($this, 'get_filtered_terms'));
        add_action('wp_ajax_nopriv_get_filtered_terms', array($this, 'get_filtered_terms'));
        add_shortcode('dynamic_filter', array($this, 'render_filter'));
    }

    public function register_taxonomies() {
        $settings = get_option('dpf_settings', array());
        
        if (!empty($settings['taxonomies'])) {
            foreach ($settings['taxonomies'] as $taxonomy) {
                if (empty($taxonomy['name']) || empty($taxonomy['slug']) || empty($taxonomy['post_types'])) {
                    continue;
                }

                $labels = array(
                    'name' => $taxonomy['name'],
                    'singular_name' => $taxonomy['name'],
                    'menu_name' => $taxonomy['name'],
                    'all_items' => 'All ' . $taxonomy['name'],
                    'edit_item' => 'Edit ' . $taxonomy['name'],
                    'view_item' => 'View ' . $taxonomy['name'],
                    'update_item' => 'Update ' . $taxonomy['name'],
                    'add_new_item' => 'Add New ' . $taxonomy['name'],
                    'new_item_name' => 'New ' . $taxonomy['name'] . ' Name',
                    'search_items' => 'Search ' . $taxonomy['name'],
                );

                register_taxonomy($taxonomy['slug'], $taxonomy['post_types'], array(
                    'labels' => $labels,
                    'hierarchical' => !empty($taxonomy['hierarchical']),
                    'show_ui' => true,
                    'show_admin_column' => true,
                    'query_var' => true,
                    'rewrite' => array('slug' => $taxonomy['slug']),
                    'show_in_rest' => true,
                ));
            }
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'dynamic-filter',
            plugins_url('js/filter.js', __FILE__),
            array('jquery'),
            '1.0',
            true
        );
        wp_enqueue_style(
            'dynamic-filter-style',
            plugins_url('css/style.css', __FILE__),
            array(),
            '1.0'
        );
        wp_localize_script('dynamic-filter', 'dpfSettings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dpf_nonce')
        ));
    }

    public function render_filter($atts) {
        $atts = shortcode_atts(array(
            'target_element' => '',
        ), $atts);

        $settings = get_option('dpf_settings', array());
        if (empty($settings['taxonomies'])) {
            return 'No taxonomies configured.';
        }

        ob_start();
        ?>
        <div class="dynamic-filter" data-target="<?php echo esc_attr($atts['target_element']); ?>">
            <div class="filter-controls">
                <?php foreach ($settings['taxonomies'] as $taxonomy): ?>
                    <?php
                    $terms = get_terms(array(
                        'taxonomy' => $taxonomy['slug'],
                        'hide_empty' => true,
                    ));
                    
                    if (!empty($terms) && !is_wp_error($terms)):
                    ?>
                        <select id="<?php echo esc_attr($taxonomy['slug']); ?>" 
                                class="filter-select" 
                                data-taxonomy="<?php echo esc_attr($taxonomy['slug']); ?>">
                            <option value="">All <?php echo esc_html($taxonomy['name']); ?></option>
                            <?php foreach ($terms as $term): ?>
                                <option value="<?php echo esc_attr($term->term_id); ?>">
                                    <?php echo esc_html($term->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function modify_bricks_query($query, $query_obj) {
        if (!isset($_POST['tax_query']) || empty($_POST['tax_query'])) {
            return $query;
        }

        if (!isset($query['tax_query'])) {
            $query['tax_query'] = array();
        }

        // Store current filter for use in loop_object filter
        $this->current_filter = $_POST['tax_query'];

        // Convert tax_query to array if it's not already
        if (!is_array($query['tax_query'])) {
            $query['tax_query'] = array();
        }

        // Add relation if there are multiple conditions
        if (!isset($query['tax_query']['relation'])) {
            $query['tax_query']['relation'] = 'AND';
        }

        // Add our taxonomy filters
        foreach ($_POST['tax_query'] as $taxonomy => $term_id) {
            if (!empty($term_id)) {
                $query['tax_query'][] = array(
                    'taxonomy' => sanitize_key($taxonomy),
                    'field' => 'term_id',
                    'terms' => absint($term_id),
                    'operator' => 'IN'
                );
            }
        }

        return $query;
    }

    public function modify_loop_object($loop_object, $loop_key, $query_obj) {
        // Only modify post objects
        if (!is_object($loop_object) || !isset($loop_object->post_type)) {
            return $loop_object;
        }

        // Apply any additional modifications to the loop object if needed
        return $loop_object;
    }

    public function get_filtered_terms() {
        check_ajax_referer('dpf_nonce', 'nonce');

        $taxonomy = sanitize_key($_POST['taxonomy']);
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ));

        wp_send_json_success($terms);
    }
}

new DynamicPostFilter();