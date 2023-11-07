<?php
namespace OffbeatWP\AcfSiteSettings;

use InvalidArgumentException;
use OffbeatWP\AcfCore\FieldsMapper;
use OffbeatWP\Contracts\ISettingsPage;

/** Requires WordPress <b>6.4</b> or later */
final class SiteOptions extends SiteSettings
{
    public const ID = 'site-settings';

    /** @var string[] */
    private static $keys = [];

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

    /** @return mixed[] */
    public function fetchSettings(): array
    {
        $siteSettings = get_transient('site_settings');
        if ($siteSettings) {
            return $siteSettings;
        }

        $settings = get_options(SiteOptions::$keys);
        $settings = $this->normalizeSettings($settings);

        set_transient('site_settings', $settings);

        return $settings;
    }

    /**
     * @param mixed[] $settings
     * @return mixed[]
     */
    public function normalizeSettings($settings): array
    {
        $normalizedSettings = [];
        foreach (self::$keys as $key) {
            $normalizedSettings[$key] = $settings['options_' . $key];
        }

        return $normalizedSettings;
    }
}
