<?php
if (!defined('ABSPATH')) exit;

class DPF_Admin_Settings {
    private $option_name = 'dpf_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_settings_page() {
        add_options_page(
            'Dynamic Post Filter Settings',
            'Dynamic Post Filter',
            'manage_options',
            'dynamic-post-filter',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
    }

    public function enqueue_admin_scripts($hook) {
        if ('settings_page_dynamic-post-filter' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'dpf-admin',
            plugins_url('../js/admin.js', __FILE__),
            array('jquery'),
            '1.0',
            true
        );

        wp_enqueue_style(
            'dpf-admin-style',
            plugins_url('../css/admin.css', __FILE__),
            array(),
            '1.0'
        );
    }

    public function sanitize_settings($input) {
        if (!is_array($input)) return array();

        $sanitized = array();
        
        if (isset($input['taxonomies']) && is_array($input['taxonomies'])) {
            foreach ($input['taxonomies'] as $tax_key => $taxonomy) {
                $sanitized['taxonomies'][$tax_key] = array(
                    'name' => sanitize_text_field($taxonomy['name']),
                    'slug' => sanitize_title($taxonomy['slug']),
                    'post_types' => isset($taxonomy['post_types']) ? array_map('sanitize_text_field', $taxonomy['post_types']) : array(),
                    'hierarchical' => isset($taxonomy['hierarchical']) ? (bool) $taxonomy['hierarchical'] : false
                );
            }
        }

        return $sanitized;
    }

    public function render_settings_page() {
        $settings = get_option($this->option_name, array());
        $post_types = get_post_types(array('public' => true), 'objects');
        ?>
        <div class="wrap">
            <h1>Dynamic Post Filter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_name);
                do_settings_sections($this->option_name);
                ?>

                <div class="dpf-taxonomies">
                    <h2>Custom Taxonomies</h2>
                    <div class="dpf-info">
                        <p>To use the filter in Bricks Builder:</p>
                        <ol>
                            <li>Add your taxonomies below</li>
                            <li>Insert the filter using this shortcode: <code>[dynamic_filter target_element="your-bricks-query-id"]</code></li>
                            <li>Replace "your-bricks-query-id" with the ID of your Bricks query loop element</li>
                        </ol>
                    </div>
                    <div id="dpf-taxonomy-list">
                        <?php
                        if (!empty($settings['taxonomies'])) {
                            foreach ($settings['taxonomies'] as $tax_key => $taxonomy) {
                                $this->render_taxonomy_row($tax_key, $taxonomy, $post_types);
                            }
                        }
                        ?>
                    </div>

                    <button type="button" class="button button-secondary" id="add-taxonomy">Add New Taxonomy</button>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <template id="taxonomy-row-template">
            <?php $this->render_taxonomy_row('{{KEY}}', array(), $post_types); ?>
        </template>
        <?php
    }

    private function render_taxonomy_row($key, $taxonomy, $post_types) {
        ?>
        <div class="taxonomy-row" data-key="<?php echo esc_attr($key); ?>">
            <div class="taxonomy-header">
                <h3>Taxonomy Settings</h3>
                <button type="button" class="button button-link-delete remove-taxonomy">Remove</button>
            </div>
            
            <div class="taxonomy-fields">
                <div class="field-group">
                    <label>Name:</label>
                    <input type="text" 
                           name="<?php echo esc_attr($this->option_name); ?>[taxonomies][<?php echo esc_attr($key); ?>][name]" 
                           value="<?php echo esc_attr(isset($taxonomy['name']) ? $taxonomy['name'] : ''); ?>" 
                           required>
                </div>

                <div class="field-group">
                    <label>Slug:</label>
                    <input type="text" 
                           name="<?php echo esc_attr($this->option_name); ?>[taxonomies][<?php echo esc_attr($key); ?>][slug]" 
                           value="<?php echo esc_attr(isset($taxonomy['slug']) ? $taxonomy['slug'] : ''); ?>" 
                           required>
                </div>

                <div class="field-group">
                    <label>Hierarchical:</label>
                    <input type="checkbox" 
                           name="<?php echo esc_attr($this->option_name); ?>[taxonomies][<?php echo esc_attr($key); ?>][hierarchical]" 
                           <?php checked(isset($taxonomy['hierarchical']) ? $taxonomy['hierarchical'] : false); ?>>
                </div>

                <div class="field-group">
                    <label>Apply to Post Types:</label>
                    <div class="post-type-checkboxes">
                        <?php foreach ($post_types as $post_type): ?>
                            <label>
                                <input type="checkbox" 
                                       name="<?php echo esc_attr($this->option_name); ?>[taxonomies][<?php echo esc_attr($key); ?>][post_types][]" 
                                       value="<?php echo esc_attr($post_type->name); ?>"
                                       <?php checked(isset($taxonomy['post_types']) && in_array($post_type->name, $taxonomy['post_types'])); ?>>
                                <?php echo esc_html($post_type->labels->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}