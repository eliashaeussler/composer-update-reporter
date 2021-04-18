# API

The plugin provides a slim API which can be used to unregister currently
registered services or to register new services.

## `Registry::registerService()`

!!! tip
    Learn how to [create a custom service](custom-service.md) if you want to register it here.

Registers the given service class in the
[`Registry`]({{ repository.blob }}/src/Registry.php)
so that it can be used by the
[`Reporter`]({{ repository.blob }}/src/Reporter.php). It is required that
the given service class implements the
[`ServiceInterface`]({{ repository.blob }}/src/Service/ServiceInterface.php).

| Parameter           | Description                                                       |
| ------------------- | ----------------------------------------------------------------- |
| `string $className` | The class name of a service to be registered within the registry. |

Example:

```php
Registry::registerService(\My\Vendor\Service\MyService::class);
```

## `Registry::unregisterService()`

Removes the given service class from the list of registered services
in the [`Registry`]({{ repository.blob }}/src/Registry.php).

| Parameter           | Description                                                  |
| ------------------- | ------------------------------------------------------------ |
| `string $className` | The class name of a service to be removed from the registry. |

Example:

```php
Registry::unregisterService(\My\Vendor\Service\MyService::class);
```

## `Registry::getServices()`

Returns the list of currently registered services in the
[`Registry`]({{ repository.blob }}/src/Registry.php).

Example:

```php
$services = Registry::getServices();
```

## `Reporter::report()`

Reports the given `UpdateCheckResult` to all currently registered services.

| Parameter                   | Description                                                           |
| --------------------------- | --------------------------------------------------------------------- |
| `UpdateCheckResult $result` | The scan results to be reported to all currently registered services. |

Example:

```php
$reporter = new Reporter($composer);
$reporter->report($result);
```
