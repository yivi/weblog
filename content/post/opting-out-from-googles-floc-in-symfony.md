+++
title="Opting out from Google's FLOC in Symfony"
type="post"
date=2021-04-21T12:12:12
tags=["symfony", "google", "http"]
+++
[FLOC is evil.](https://www.eff.org/deeplinks/2021/03/googles-floc-terrible-idea)

But opting-out your site from this nasty "experiment" is easy enough.
If you are hosting your Symfony application on a server where you do not have access to webserver settings, setting the opt-out header is easy enough.

You can simply edit your  `public/index.php`  front-controller and adjust the response just before sending it.

Assuming you have something like this:

```php
$kernel   = new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);

$response->send();
$kernel->terminate($request, $response);
```

Just modify it to add the header before sending out the response:
```php
$kernel   = new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);

// add this line
$response->headers->set('Permissions-Policy', 'interest-cohort=()');


$response->send();
$kernel->terminate($request, $response);
```

If you do not want to modify your front-controller to keep things tidy, you could do the same using an  [EventListener](https://symfony.com/doc/current/event_dispatcher.html):
```php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class FlocHeadersListener
{
	public function onKernelResponse(ResponseEvent $event)
    {
    	$event->getResponse()->headers->set('Permissions-Policy', 'interest-cohort=()');
    }

}
```

Which you would then need to configure:
```yaml
services:
    App\EventListener\FlocHeadersListener:
        tags:
            - { name: kernel.event_listener, event: kernel.response }
```

Or, if you are using PHP configuration files:
```php
// config/services.php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\EventListener\FlocHeadersListener;

return function(ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->set(FlocHeadersListener::class)
        ->tag('kernel.event_listener', ['event' => 'kernel.response'])
    ;
};
```
