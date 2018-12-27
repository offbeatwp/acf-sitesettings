<?php
namespace OffbeatWP\AcfSiteSettings;

use OffbeatWP\Services\AbstractService;
use OffbeatWP\Contracts\SiteSettings as SiteSettingsContract;

class Service extends AbstractService {
    public $bindings = [
        SiteSettingsContract::class => SiteSettings::class
    ];
}