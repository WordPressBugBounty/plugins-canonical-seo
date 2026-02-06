<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Canonical_Seo class
 * 
 * Main class of the plugin responsible for the main logics
 */
class Canonical_Seo
{

    /**
     * Singleton instance variable
     */
    protected static $instance = null;


    /**
     * Defualt construct for the calss
     */
    protected function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register_metaboxes']);
        add_action('save_post', [$this, 'save_metaboxes']);

        //Write custom meta tags
        add_action('wp_head', [$this, 'display_data_on_page']);

        //Write custom canonical url
        add_filter("get_canonical_url", [$this, 'apply_custom_canonical_url'], 10, 2);
    }


    /**
     * @return Canonical_Seo instance Singleton instance for the class
     */
    public static function instance(): Canonical_Seo
    {

        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * Register metaboxes that we need for our plugin
     */
    public function register_metaboxes($post_type)
    {

        /**
         * Define meta box for canonical url
         */
        add_meta_box(
            'cseo_canonical_url',
            'Canonical URL',
            [$this, 'render_canonical_url_meta_box'],
            $post_type,
            'advanced',
            'high'
        );

        /**
         * Define meta box for meta description
         */
        add_meta_box(
            'cseo_meta_description',
            'Meta description',
            [$this, 'render_meta_description_meta_box'],
            $post_type,
            'advanced',
            'high'
        );
    }


    /**
     * Render nonce for our custom fields
     */
    protected function doNonce()
    {

        wp_nonce_field('cseo_meta_box', 'cseo_meta_box_nonce');
    }


    /**
     * Render field to the admin page
     */
    public function render_canonical_url_meta_box($post)
    {

        $this->doNonce();

        $value = get_post_meta($post->ID, '_cseo_canonical_url', true);

?>
        <label for="cseo_canonical_url">
            <?php echo esc_html('Customize canonical URL'); ?>
        </label>
        <input style="width: 100%;" type="text" id="cseo_canonical_url" name="cseo_canonical_url" value="<?php echo esc_attr($value); ?>" />
    <?php
    }


    /**
     * Render field to the admin page
     */
    public function render_meta_description_meta_box($post)
    {

        $value = get_post_meta($post->ID, '_cseo_meta_description', true);

    ?>
        <label for="cseo_meta_description">
            <?php echo esc_html('Write meta tag "description"'); ?>
        </label>
        <input style="width: 100%;" type="text" id="cseo_meta_description" name="cseo_meta_description" value="<?php echo esc_attr($value); ?>" />
    <?php
    }


    /**
     * Save value from the metaboxes
     */
    public function save_metaboxes($post_id)
    {

        if (! isset($_POST['cseo_meta_box_nonce'])) {
            return $post_id;
        }

        if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cseo_meta_box_nonce'])), 'cseo_meta_box')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Check the user's permissions.
        if (!empty($_POST['post_type']) && 'page' == sanitize_text_field(wp_unslash($_POST['post_type']))) {
            if (! current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } else {
            if (! current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
        }

        if (!empty($_POST['cseo_canonical_url'])) {

            // Sanitize the user input.
            $mydataCanUrl = sanitize_text_field(wp_unslash($_POST['cseo_canonical_url']));

            // Update the meta field.
            update_post_meta($post_id, '_cseo_canonical_url', $mydataCanUrl);
        } else {
            delete_post_meta($post_id, '_cseo_canonical_url');
        }

        if (!empty($_POST['cseo_meta_description'])) {

            // Sanitize the user input.
            $mydataMetaDesc = sanitize_text_field(wp_unslash($_POST['cseo_meta_description']));

            // Update the meta field.
            update_post_meta($post_id, '_cseo_meta_description', $mydataMetaDesc);
        } else {
            delete_post_meta($post_id, '_cseo_meta_description');
        }
    }


    /**
     * Display data as meta fields for the current post/page
     */
    public function display_data_on_page()
    {
        $metaDescription = get_post_meta(get_the_ID(), '_cseo_meta_description', true);

        if (!$metaDescription || trim($metaDescription) == "") {
            return;
        }

    ?>
        <meta name="description" content="<?php echo esc_html($metaDescription); ?>" />
<?php
    }


    /**
     * Override wp canonical url if custom one is set for the post/page
     */
    public function apply_custom_canonical_url($value, $post)
    {

        $result = $value;

        $canonicalURL = get_post_meta($post->ID, '_cseo_canonical_url', true);

        if (!$canonicalURL || trim($canonicalURL) == "") {
            return $result;
        }

        //Apply our custom canonical url
        $result = $canonicalURL;

        return $result;
    }
}

/**
 * Let's initiate everything now!
 */
add_action('plugin_loaded', ['Canonical_Seo', 'instance'], 30, 1);
