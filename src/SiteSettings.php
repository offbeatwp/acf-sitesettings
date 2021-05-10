<?php
namespace OffbeatWP\AcfSiteSettings;

use OffbeatWP\AcfCore\FieldsMapper;
use OffbeatWP\Content\Post\PostModel;
use OffbeatWP\SiteSettings\AbstractSiteSettings;

class SiteSettings extends AbstractSiteSettings
{
    const ID = 'site-settings';

    protected $settings;

    public function addPage($class)
    {
        if (!class_exists($class) || !function_exists('acf_add_options_sub_page')) {
            return null;
        }

        $pageConfig = container()->make($class);

        $priority = 10;
        if (defined("{$class}::PRIORITY")) {
            $priority = $class::PRIORITY;
        }

        add_action('acf_site_settings', function () use ($pageConfig, $class) {
            $title       = $pageConfig->title();
            $subMenuSlug = self::ID . '-' . $pageConfig::ID;

            acf_add_options_sub_page([
                'page_title'  => $title,
                'menu_title'  => $title,
                'parent_slug' => self::ID,
                'menu_slug'   => $subMenuSlug,
                'capability' => apply_filters('acf/sitesettings/capability', 'manage_options'),
            ]);

            if (method_exists($pageConfig, 'form')) {
                $form = $pageConfig->form();

                if ($form instanceof \OffbeatWP\Form\Form) {
                    $fieldsMapper = new FieldsMapper($form);
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
                $dottedSettings = $settings;
                if (isset($dottedSettings[$var])) {
                    $dottedSettings = $dottedSettings[$var];
                } else {
                    $dottedSettings = null;
                }
            }

            $return = $dottedSettings;
        }

        if (!current_filter('offbeatwp/sitesettings/get')) {
            $return = apply_filters('offbeatwp/sitesettings/get', $return, $key, $settings);
        }

        return $return;
    }

    public function fetchSettings()
    {
        if ($siteSettings = get_transient('site_settings')) {
            return $siteSettings;
        }

        $settings = (array) get_fields('option');
        $settings = $this->normalizeSettings($settings);

        set_transient('site_settings', $settings);

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
}
