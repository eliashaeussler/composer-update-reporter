# Custom service

The plugin already provides a set of [supported services](index.md#supported-services)
which can be extended to make use of custom implemented services. Follow this guide
to learn how you can implement your own services and use them together with the
update reporter plugin.

!!! warning "Two variants"
    To register new services, two variants are available:
    
    1. In the **recommended variant**, you create a **separate Composer plugin** which
       can be used in your specific project in addition to the update reporter plugin.
    2. In the **alternative variant**, you create the service **directly in your concrete
       project**. This variant is easier to implement on the one hand, but does not fit
       optimally into the Composer lifecycle.
    
    Both variants have upsides and downsides. Feel free to test both of them and find
    a suitable way matching your requirements.


## 1. Create service class

Create a new service class – depending on your selected variant either in your custom
Composer plugin or directly in your project:

```php linenums="1"
# src/Service/MyCustomService.php

namespace App\Service;

use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Service\AbstractService;
use EliasHaeussler\ComposerUpdateReporter\Service\ServiceInterface;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class MyCustomService extends AbstractService
{
    /**
     * @var UriInterface
     */
    private $uri;
    
    /**
     * @var string
     */
    private $authKey;
    
    public function __construct(UriInterface $uri, string $authKey)
    {
        $this->uri = $uri;
        $this->authKey = $authKey;
    }
    
    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $uri = new Uri((string) static::resolveConfigurationKey($configuration, 'url'));
        $authKey = (string) static::resolveConfigurationKey($configuration, 'authKey');

        return new self($uri, $authKey);
    }
    
    protected static function getName(): string
    {
        return 'My custom service';
    }
    
    public static function getIdentifier(): string
    {
        return 'myCustomService';
    }
    
    protected function sendReport(UpdateCheckResult $result): bool
    {
        // Do something...
        
        return $successful;
    }
}
```

In case you're sending the report to some remote service provider, you might want
to use the provided [`RemoteServiceTrait`]({{ repository.blob }}/src/Traits/RemoteServiceTrait.php):

```diff
@@ -5,11 +5,14 @@ namespace App\Service;
 use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
 use EliasHaeussler\ComposerUpdateReporter\Service\AbstractService;
 use EliasHaeussler\ComposerUpdateReporter\Service\ServiceInterface;
+use EliasHaeussler\ComposerUpdateReporter\Traits\RemoteServiceTrait;
 use Nyholm\Psr7\Uri;
 use Psr\Http\Message\UriInterface;
 
 class MyCustomService extends AbstractService
 {
+    use RemoteServiceTrait;
+
     /**
      * @var UriInterface
      */
@@ -48,6 +51,8 @@ class MyCustomService extends AbstractService
     {
         // Do something...
 
-        return $successful;
+        $response = $this->sendRequest($payload);
+
+        return $response->getStatusCode() < 400;
     }
 }
```

## 2. Register custom service

### Variant 1: Custom Composer plugin (recommended)

First, ensure your plugins' `composer.json` looks similar to the following:

```json linenums="1"
{
    "name": "my-vendor/my-custom-service",
    "type": "composer-plugin",
    "description": "My custom service for the Composer update reporter plugin",
    "require": {
        "php": "^7.1 || 8.0.*",
        "composer-plugin-api": "^1.0 || ^2.0",
        "eliashaeussler/composer-update-reporter": "^1.0"
    },
    "require-dev": {
        "composer/composer": "^1.0 || ^2.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "extra": {
        "class": "App\\Plugin"
    }
}
```

Now provide the appropriate `Plugin` class:

```php linenums="1"
# src/Plugin.php

namespace App;

use App\Service\MyCustomService;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use EliasHaeussler\ComposerUpdateReporter\Registry;

class Plugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        Registry::registerService(MyCustomService::class);
    }
    
    // ...
}
```

### Variant 2: Extending a concrete project

First, ensure class autoloading is enabled in your projects' `composer.json`:

```json linenums="1"
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Now create a new class which serves as event listener in the Composer lifecycle:

```php linenums="1"
# src/ServiceRegistration.php

namespace App;

use App\Service\MyCustomService;
use EliasHaeussler\ComposerUpdateReporter\Registry;

class ServiceRegistration
{
    public static function registerCustomServices(): void
    {
        Registry::registerService(MyCustomService::class);
    }
} 
```

Finally, let Composer call the previously created class to trigger the
service registration by adding the following lines to your projects'
`composer.json`:

```json linenums="1"
{
    "scripts": {
        "post-update-check": "App\\ServiceRegistration::registerCustomServices"
    }
}
```

## 3. Add service configuration

Once the service is implemented and properly registered, you should provide
a valid service configuration in your project. This can be done by either using
the projects' `composer.json` or providing the appropriate environment variables.

### `composer.json`

```json linenums="1"
{
    "extra": {
        "update-check": {
            "myCustomService": {
                "enabled": true,
                "url": "https://foo.baz/",
                "authKey": "enteryourauthkeyhere"
            }
        }
    }
}
```

### Environment variables

```bash linenums="1"
MY_CUSTOM_SERVICE_ENABLE=1
MY_CUSTOM_SERVICE_URL="https://foo.baz/"
MY_CUSTOM_SERVICE_AUTH_KEY="enteryourauthkeyhere"
```
