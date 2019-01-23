# ACF Site Settings / Options for OffbeatWP

Install by running this command from the root of your OffbeatWP Theme:

```bash
composer require offbeatwp/acf-sitesettings
```

Next add the following line to your `config/services.php` file:

```
OffbeatWP\AcfSiteSettings\Service::class,
```

## Adding a section

A section is an subpage with settings.

You can register a section by injecting the SiteSetting contract to your service and run the `addPage` method, like:

```php
<?php
namespace OffbeatWP\Services;

use OffbeatWP\Contracts\SiteSettings;

class ServiceScripts extends AbstractService
{
    protected $settings;

    public function register(SiteSettings $settings)
    {
        $settings->addPage(\OffbeatWP\SiteSettings\SettingsScripts::class);
    }
}
```

The `addPage` method accepts a class. A Settings class looks like this:

```php
<?php
namespace OffbeatWP\SiteSettings;

class SettingsScripts
{
    const ID = 'scripts';
    const PRIORITY = 90;

    public function title()
    {
        return __('Scripts', 'raow');
    }

    public function form()
    {
        $form = new \OffbeatWP\Form\Form();

        $form ->addField(\OffbeatWP\Form\Fields\TextArea::make('scripts_head', 'Head'));
        $form ->addField(\OffbeatWP\Form\Fields\TextArea::make('scripts_open_body', 'Body open'));
        $form ->addField(\OffbeatWP\Form\Fields\TextArea::make('scripts_footer', 'Footer'));

        return $form;
    }
}
```

Read more about [Forms](basics__forms.md)