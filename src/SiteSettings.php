<?php
namespace OffbeatWP\AcfSiteSettings;

use OffbeatWP\AcfCore\FieldsMapper;
use OffbeatWP\Contracts\ISettingsPage;
use OffbeatWP\Form\Form;
use OffbeatWP\SiteSettings\AbstractSiteSettings;

class SiteSettings extends AbstractSiteSettings
{
    public const ID = 'site-settings';

    /** @var array<int, mixed[]> */
    protected $settings = [];

    /**
     * @param class-string<ISettingsPage> $class
     * @return void
     */
    public function addPage($class)
    {
        if (!function_exists('acf_add_options_sub_page')) {
            return;
        }

        if (!class_exists($class)) {
            trigger_error('Could not add SiteSetting ' . static::ID . ' because class ' . $class . ' could not be found.', E_USER_WARNING);
            return;
        }

        /** @var ISettingsPage $pageConfig */
        $pageConfig = container()->make($class);

        $priority = 10;
        if (defined("{$class}::PRIORITY")) {
            $priority = $class::PRIORITY;
        }

        add_action('acf_site_settings', static function () use ($pageConfig) {
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

                if ($form instanceof Form) {
                    $fieldsMapper = new FieldsMapper($form);
                    $mappedFields = $fieldsMapper->map();

                    acf_add_local_field_group([
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
                    ]);
                }
            }

        }, $priority);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $return   = null;
        $settings = $this->getSettings();

        if (isset($settings[$key])) {
            $return = $settings[$key];
        } elseif (strpos($key, '.') !== false) {
            $dottedSettings = $settings;

            foreach (explode('.', $key) as $var) {
                $dottedSettings = $dottedSettings[$var] ?? null;
            }

            $return = $dottedSettings;
        }

        // TODO: Is this working as intended? Looks like current_filter does not accept any parameters.
        if (!current_filter('offbeatwp/sitesettings/get')) {
            $return = apply_filters('offbeatwp/sitesettings/get', $return, $key, $settings);
        }

        return $return;
    }

    /** @return mixed[] */
    public function fetchSettings()
    {
        $siteSettings = get_transient('site_settings');
        if ($siteSettings) {
            return $siteSettings;
        }

        $settings = (array)get_fields('option');
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

                if ($field['type'] === 'group' && is_array($settings[$settingKey])) {
                    $settings = array_merge($settings, $settings[$settingKey]);
                }
            }
        }

        return $settings;
    }

    /** @return mixed[] */
    public function getSettings()
    {
        $id = get_current_blog_id();

        if (!$this->settings[$id]) {
            $this->settings[$id] = $this->fetchSettings();
        }

        return $this->settings[$id];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function update($key, $value)
    {
        return update_field($key, $value, 'option');
    }
}
