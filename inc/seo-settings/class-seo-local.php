<?php
/**
 * SEOKAR for WordPress Themes - Local SEO Module
 * 
 * @package    SeoKar
 * @subpackage Local
 * @author     Sajjad Akbari <https://sajjadakbari.ir>
 * @license    GPL-3.0+
 * @link       https://seokar.click
 * @copyright  2025 SeoKar Development Team
 * @version    3.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SEOKAR_Local implements SEOKAR_Module_Interface {

    /**
     * Parent class instance
     *
     * @var object
     */
    private $seokar;

    /**
     * Local business data
     *
     * @var array
     */
    private $business_data = [];

    /**
     * Constructor
     *
     * @param object $seokar
     */
    public function __construct($seokar) {
        $this->seokar = $seokar;
        $this->load_business_data();
        $this->setup_hooks();
    }

    /**
     * Load business data from options
     */
    private function load_business_data() {
        $this->business_data = wp_parse_args(
            get_option('seokar_local_options', []),
            $this->get_default_business_data()
        );
    }

    /**
     * Get default business data
     *
     * @return array
     */
    private function get_default_business_data() {
        return [
            'business_name' => get_bloginfo('name'),
            'business_type' => 'LocalBusiness',
            'description' => get_bloginfo('description'),
            'address' => [
                'street' => '',
                'city' => '',
                'state' => '',
                'postal_code' => '',
                'country' => ''
            ],
            'geo' => [
                'latitude' => '',
                'longitude' => ''
            ],
            'contact' => [
                'phone' => '',
                'email' => '',
                'fax' => ''
            ],
            'opening_hours' => [],
            'price_range' => '$$',
            'logo' => '',
            'image' => '',
            'same_as' => []
        ];
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Schema markup
        add_action('wp_head', [$this, 'output_local_schema'], 1);
        
        // Admin hooks
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    /**
     * Output local business schema
     */
    public function output_local_schema() {
        if (!is_front_page() && !is_page('contact')) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $this->business_data['business_type'],
            '@id' => home_url('/#localbusiness'),
            'name' => $this->business_data['business_name'],
            'description' => $this->business_data['description'],
            'url' => home_url('/'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->business_data['address']['street'],
                'addressLocality' => $this->business_data['address']['city'],
                'addressRegion' => $this->business_data['address']['state'],
                'postalCode' => $this->business_data['address']['postal_code'],
                'addressCountry' => $this->business_data['address']['country']
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $this->business_data['geo']['latitude'],
                'longitude' => $this->business_data['geo']['longitude']
            ],
            'priceRange' => $this->business_data['price_range'],
            'telephone' => $this->business_data['contact']['phone'],
            'email' => $this->business_data['contact']['email']
        ];

        // Add logo if set
        if (!empty($this->business_data['logo'])) {
            $schema['logo'] = $this->business_data['logo'];
        }

        // Add image if set
        if (!empty($this->business_data['image'])) {
            $schema['image'] = $this->business_data['image'];
        }

        // Add opening hours if set
        if (!empty($this->business_data['opening_hours'])) {
            $schema['openingHoursSpecification'] = $this->format_opening_hours();
        }

        // Add social profiles
        if (!empty($this->business_data['same_as'])) {
            $schema['sameAs'] = $this->business_data['same_as'];
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Format opening hours for schema
     *
     * @return array
     */
    private function format_opening_hours() {
        $formatted = [];
        $days = [
            'monday', 'tuesday', 'wednesday', 
            'thursday', 'friday', 'saturday', 'sunday'
        ];

        foreach ($days as $day) {
            if (!empty($this->business_data['opening_hours'][$day]['open'])) {
                $formatted[] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ucfirst($day),
                    'opens' => $this->business_data['opening_hours'][$day]['open'],
                    'closes' => $this->business_data['opening_hours'][$day]['close']
                ];
            }
        }

        return $formatted;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'seo-settings',
            __('Local SEO', 'seokar'),
            __('Local SEO', 'seokar'),
            'manage_options',
            'seokar-local',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('seokar_local_options_group', 'seokar_local_options', [$this, 'sanitize_options']);

        // Business Info Section
        add_settings_section(
            'seokar_local_business_section',
            __('Business Information', 'seokar'),
            [$this, 'render_business_section'],
            'seokar-local'
        );

        add_settings_field(
            'business_name',
            __('Business Name', 'seokar'),
            [$this, 'render_business_name_field'],
            'seokar-local',
            'seokar_local_business_section'
        );

        add_settings_field(
            'business_type',
            __('Business Type', 'seokar'),
            [$this, 'render_business_type_field'],
            'seokar-local',
            'seokar_local_business_section'
        );

        // Address Section
        add_settings_section(
            'seokar_local_address_section',
            __('Address', 'seokar'),
            [$this, 'render_address_section'],
            'seokar-local'
        );

        add_settings_field(
            'address_street',
            __('Street Address', 'seokar'),
            [$this, 'render_address_street_field'],
            'seokar-local',
            'seokar_local_address_section'
        );

        // Contact Section
        add_settings_section(
            'seokar_local_contact_section',
            __('Contact Information', 'seokar'),
            [$this, 'render_contact_section'],
            'seokar-local'
        );

        add_settings_field(
            'contact_phone',
            __('Phone Number', 'seokar'),
            [$this, 'render_contact_phone_field'],
            'seokar-local',
            'seokar_local_contact_section'
        );

        // Opening Hours Section
        add_settings_section(
            'seokar_local_hours_section',
            __('Opening Hours', 'seokar'),
            [$this, 'render_hours_section'],
            'seokar-local'
        );

        $days = [
            'monday' => __('Monday', 'seokar'),
            'tuesday' => __('Tuesday', 'seokar'),
            'wednesday' => __('Wednesday', 'seokar'),
            'thursday' => __('Thursday', 'seokar'),
            'friday' => __('Friday', 'seokar'),
            'saturday' => __('Saturday', 'seokar'),
            'sunday' => __('Sunday', 'seokar')
        ];

        foreach ($days as $day => $label) {
            add_settings_field(
                "opening_hours_{$day}",
                $label,
                [$this, "render_opening_hours_{$day}_field"],
                'seokar-local',
                'seokar_local_hours_section'
            );
        }
    }

    /**
     * Sanitize options
     *
     * @param array $input
     * @return array
     */
    public function sanitize_options($input) {
        $output = $this->get_default_business_data();

        // Business Info
        if (isset($input['business_name'])) {
            $output['business_name'] = sanitize_text_field($input['business_name']);
        }

        if (isset($input['business_type'])) {
            $output['business_type'] = sanitize_text_field($input['business_type']);
        }

        // Address
        if (isset($input['address'])) {
            $output['address'] = [
                'street' => sanitize_text_field($input['address']['street']),
                'city' => sanitize_text_field($input['address']['city']),
                'state' => sanitize_text_field($input['address']['state']),
                'postal_code' => sanitize_text_field($input['address']['postal_code']),
                'country' => sanitize_text_field($input['address']['country'])
            ];
        }

        // Geo Coordinates
        if (isset($input['geo'])) {
            $output['geo'] = [
                'latitude' => sanitize_text_field($input['geo']['latitude']),
                'longitude' => sanitize_text_field($input['geo']['longitude'])
            ];
        }

        // Contact Info
        if (isset($input['contact'])) {
            $output['contact'] = [
                'phone' => sanitize_text_field($input['contact']['phone']),
                'email' => sanitize_email($input['contact']['email']),
                'fax' => sanitize_text_field($input['contact']['fax'])
            ];
        }

        // Opening Hours
        if (isset($input['opening_hours'])) {
            foreach ($input['opening_hours'] as $day => $hours) {
                $output['opening_hours'][$day] = [
                    'open' => $this->sanitize_time($hours['open']),
                    'close' => $this->sanitize_time($hours['close'])
                ];
            }
        }

        // Other fields
        if (isset($input['price_range'])) {
            $output['price_range'] = sanitize_text_field($input['price_range']);
        }

        if (isset($input['logo'])) {
            $output['logo'] = esc_url_raw($input['logo']);
        }

        if (isset($input['image'])) {
            $output['image'] = esc_url_raw($input['image']);
        }

        if (isset($input['same_as'])) {
            $output['same_as'] = array_map('esc_url_raw', explode("\n", $input['same_as']));
        }

        return $output;
    }

    /**
     * Sanitize time value
     *
     * @param string $time
     * @return string
     */
    private function sanitize_time($time) {
        return preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $time) ? $time : '';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap seokar-local-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'seokar'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('seokar_local_options_group');
                do_settings_sections('seokar-local');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render business section
     */
    public function render_business_section() {
        echo '<p>' . __('Enter your business information for local SEO.', 'seokar') . '</p>';
    }

    /**
     * Render business name field
     */
    public function render_business_name_field() {
        ?>
        <input type="text" name="seokar_local_options[business_name]" class="regular-text" 
               value="<?php echo esc_attr($this->business_data['business_name']); ?>">
        <?php
    }

    /**
     * Render business type field
     */
    public function render_business_type_field() {
        $business_types = [
            'LocalBusiness' => __('Local Business', 'seokar'),
            'Store' => __('Store', 'seokar'),
            'Restaurant' => __('Restaurant', 'seokar'),
            'Hotel' => __('Hotel', 'seokar'),
            'ProfessionalService' => __('Professional Service', 'seokar'),
            'MedicalBusiness' => __('Medical Business', 'seokar')
        ];
        ?>
        <select name="seokar_local_options[business_type]" class="regular-text">
            <?php foreach ($business_types as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($this->business_data['business_type'], $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render address section
     */
    public function render_address_section() {
        echo '<p>' . __('Enter your business address details.', 'seokar') . '</p>';
    }

    /**
     * Render address street field
     */
    public function render_address_street_field() {
        ?>
        <input type="text" name="seokar_local_options[address][street]" class="regular-text" 
               value="<?php echo esc_attr($this->business_data['address']['street']); ?>">
        <?php
    }

    /**
     * Render contact section
     */
    public function render_contact_section() {
        echo '<p>' . __('Enter your business contact information.', 'seokar') . '</p>';
    }

    /**
     * Render contact phone field
     */
    public function render_contact_phone_field() {
        ?>
        <input type="text" name="seokar_local_options[contact][phone]" class="regular-text" 
               value="<?php echo esc_attr($this->business_data['contact']['phone']); ?>">
        <?php
    }

    /**
     * Render hours section
     */
    public function render_hours_section() {
        echo '<p>' . __('Set your business opening hours.', 'seokar') . '</p>';
    }

    /**
     * Render opening hours fields for each day
     */
    public function render_opening_hours_monday_field() {
        $this->render_opening_hours_field('monday');
    }

    // Similar methods for other days (tuesday, wednesday, etc.)
    // ...

    /**
     * Render opening hours field
     *
     * @param string $day
     */
    private function render_opening_hours_field($day) {
        $hours = $this->business_data['opening_hours'][$day] ?? ['open' => '', 'close' => ''];
        ?>
        <label>
            <?php _e('Open:', 'seokar'); ?>
            <input type="time" name="seokar_local_options[opening_hours][<?php echo $day; ?>][open]" 
                   value="<?php echo esc_attr($hours['open']); ?>">
        </label>
        <label>
            <?php _e('Close:', 'seokar'); ?>
            <input type="time" name="seokar_local_options[opening_hours][<?php echo $day; ?>][close]" 
                   value="<?php echo esc_attr($hours['close']); ?>">
        </label>
        <?php
    }
}
