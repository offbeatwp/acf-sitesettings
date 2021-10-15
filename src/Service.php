<?php
namespace OffbeatWP\AcfSiteSettings;

use OffbeatWP\Services\AbstractService;
use OffbeatWP\Contracts\SiteSettings as SiteSettingsContract;

class Service extends AbstractService {
    public $bindings = [
        SiteSettingsContract::class => SiteSettings::class
    ];

    public function register()
    {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title' => __('Site Settings', 'offbeatwp'),
                'menu_title' => __('Site Settings', 'offbeatwp'),
                'menu_slug'  => SiteSettings::ID,
                'capability' => apply_filters('acf/sitesettings/capability', 'manage_options'),
                'redirect'   => true,
            ]);

            add_action('acf/init', [$this, 'registerAcfSiteSettings']);

            add_action('acf/save_post', [$this, 'clearSiteSettingsTransients'], 10);
        }
    }

    public function registerAcfSiteSettings()
    {
        do_action('acf_site_settings');
    }

    public function clearSiteSettingsTransients()
    {
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();

            if (strpos($screen->id, '_site-settings-')) {
                delete_transient('site_settings');
            }
        }
    }
}