<?php
namespace OffbeatWP\AcfSiteSettings;

use OffbeatWP\AcfCore\FieldsMapper;
use OffbeatWP\SiteSettings\AbstractSiteSettings;

class SiteSettings extends AbstractSiteSettings
{
    const ID = 'site-settings';

    protected $settings;

    public function register()
    {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page(array(
                'page_title' => 'Site Settings',
                'menu_title' => 'Site Settings',
                'menu_slug'  => self::ID,
                'capability' => 'manage_options',
                'redirect'   => true,
            ));
        }

        add_filter('acf/format_value/type=relationship', [$this, 'convertPostObject'], 99, 3);

        add_action('acf/init', [$this, 'registerAcfSiteSettings']);
    }

    public function convertPostObject($value, $post_id, $field)
    {
        if ($field['return_format'] != 'object' || empty($value)) {
            return $value;
        }

        foreach ($value as &$postObject) {
            $postObject = new \OffbeatWP\Content\Post($postObject);
        }

        return $value;
    }

    public function addSection($class)
    {
        if (!class_exists($class) || !function_exists('acf_add_options_sub_page')) {
            return null;
        }

        $sectionConfig = container()->make($class);

        $priority = 10;
        if (defined("{$class}::PRIORITY")) {
            $priority = $class::PRIORITY;
        }

        add_action('acf_site_settings', function () use ($sectionConfig, $class) {
            $title       = $sectionConfig->title();
            $subMenuSlug = self::ID . '-' . $sectionConfig::ID;

            acf_add_options_sub_page([
                'page_title'  => $title,
                'menu_title'  => $title,
                'parent_slug' => self::ID,
                'menu_slug'   => $subMenuSlug,
            ]);

            if (method_exists($sectionConfig, 'fields')) {
                $fields = $sectionConfig->fields($subMenuSlug);

                if (is_array($fields)) {
                    $fieldsMapper = new FieldsMapper($fields);
                    $mappedFields = $fieldsMapper->map();

                    acf_add_local_field_group(array(
                        'key'                   => 'group_' . str_replace(' ', '_', strtolower($title)),
                        'title'                 => $title,
                        'fields'                => $mappedFields,
                        'location'              => [
                            [
                                [
                                    'param'    => 'options_page',
                                    'operator' => '==',
                                    'value'    => $subMenuSlug,
                                ],
                            ],
                        ],
                        'menu_order'            => 0,
                        'position'              => 'normal',
                        'style'                 => 'seamless',
                        'label_placement'       => 'top',
                        'instruction_placement' => 'label',
                        'active'                => 1,
                    ));
                }
            }

        }, $priority);

    }

    public function get($key)
    {
        $return   = null;
        $settings = $this->getSettings();

        if (isset($settings[$key])) {
            $return = $settings[$key];
        } elseif (strpos($key, '.') !== false) {
            foreach (explode('.', $key) as $var) {
                if (isset($settings[$var])) {
                    $settings = $settings[$var];
                } else {
                    return null;
                }
            }

            $return = $settings;
        }

        return $return;
    }

    public function fetchSettings()
    {
        $settings = (array) get_fields('option');
        $settings = $this->normalizeSettings($settings);

        return $settings;
    }

    public function normalizeSettings($settings)
    {
        if (is_array($settings)) {
            foreach ($settings as $settingKey => $setting) {
                $field = get_field_object($settingKey, 'option');

                if (!$field) {
                    continue;
                }

                switch ($field['type']) {
                    case 'group':
                        if (is_array($settings[$settingKey])) {
                            $settings = array_merge($settings, $settings[$settingKey]);
                        }

                        break;
                }
            }
        }

        return $settings;
    }

    public function getSettings()
    {
        if (!$this->settings) {
            $this->settings = $this->fetchSettings();
        }

        return $this->settings;
    }

    public function update($key, $value)
    {
        return update_field($key, $value, 'option');
    }

    public function registerAcfSiteSettings()
    {
        do_action('acf_site_settings');
    }
}
