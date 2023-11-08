<?php
namespace OffbeatWP\AcfSiteSettings;

use OffbeatWP\AcfCore\FieldsMapper;
use OffbeatWP\Contracts\ISettingsPage;
use OffbeatWP\Form\Form;
use OffbeatWP\SiteSettings\AbstractSiteSettings;

/** Requires WordPress <b>6.4</b> or later */
final class SiteSettings extends AbstractSiteSettings
{
    public const ID = 'site-settings';

    /** @var string[] */
    private static $keys = [];

    /** @var mixed[] */
    protected $settings;

    /** @param class-string<ISettingsPage> $class */
    public function addPage($class): void
    {
        if (!is_string($class) || !class_exists($class)) {
            throw new InvalidArgumentException('Class ' . $class . ' does not exist.');
        }

        $pageConfig = container()->make($class);
        if (!$pageConfig instanceof ISettingsPage) {
            throw new InvalidArgumentException('Class ' . $class . ' does not implement ISettingsPage.');
        }

        foreach ($pageConfig->form()->keys() as $key) {
            SiteOptions::$keys[] = 'options_' . $key;
        }

        if (is_admin() && function_exists('acf_add_options_sub_page') && function_exists('acf_add_local_field_group')) {
            $priority = (defined("{$class}::PRIORITY")) ? $class::PRIORITY : 10;

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

                $fieldsMapper = new FieldsMapper($pageConfig->form());
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

            }, $priority);
        }
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
    public function fetchSettings(): array
    {
        $siteSettings = get_transient('site_settings');
        if ($siteSettings) {
            return $siteSettings;
        }

        $settings = $this->normalizeSettings(get_options(SiteOptions::$keys));
        set_transient('site_settings', $settings);

        return $settings;
    }

    /**
     * @param mixed[] $settings
     * @return mixed[]
     */
    public function normalizeSettings(array $settings): array
    {
        $normalizedSettings = [];
        foreach (self::$keys as $key) {
            $normalizedSettings[$key] = $settings['options_' . $key];
        }

        return $normalizedSettings;
    }

    /** @return mixed[] */
    public function getSettings()
    {
        if (!$this->settings) {
            $this->settings = $this->fetchSettings();
        }

        return $this->settings;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function update($key, $value): bool
    {
        return update_option($key, $value);
    }
}
