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
            acf_add_options_page(array(
                'page_title' => __('Site Settings', 'offbeatwp'),
                'menu_title' => __('Site Settings', 'offbeatwp'),
                'menu_slug'  => SiteSettings::ID,
                'capability' => apply_filters('acf/sitesettings/capability', 'manage_options'),
                'redirect'   => true,
            ));

            add_action('acf/init', [$this, 'registerAcfSiteSettings']);
        }
    }

    public function registerAcfSiteSettings()
    {
        do_action('acf_site_settings');
    }
}