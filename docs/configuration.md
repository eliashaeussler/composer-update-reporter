# Configuration

Services need to be enabled and configured, either in the `composer.json`
file of your project or using environment variables.

Configuration in `composer.json` needs to be placed in the `extra`
section like follows:

```json
{
  "extra": {
    "update-check": {
      "<service name>": {
        "<config key>": "<config value>"
      }
    }
  }
}
```

## E-mail

E-mail reports are being processed using the [Symfony Mailer](https://packagist.org/packages/symfony/mailer).
Consult the [official documentation](https://symfony.com/doc/current/mailer.html) for help regarding DSN.

| `composer.json` config key | Environment variable | Type                            | Required |
| -------------------------- | -------------------- | ------------------------------- | -------- |
| `email.enable`             | `EMAIL_ENABLE`       | `bool`                          | yes      |
| `email.dsn`                | `EMAIL_DSN`          | `string`                        | yes      |
| `email.receivers`          | `EMAIL_RECEIVERS`    | `string` (comma-separated list) | yes      |
| `email.sender`             | `EMAIL_SENDER`       | `string`                        | yes      |

Example:

=== "composer.json"

    ```json
    {
      "extra": {
        "update-check": {
          "email": {
            "enable": true,
            "dsn": "smtp://foo:baz@smtp.example.com:25",
            "receivers": "john@example.org, marc@example.org",
            "sender": "alerts@example.org"
          }
        }
      }
    }
    ```

=== "Environment variables"

    ```bash
    EMAIL_ENABLE=1
    EMAIL_DSN="smtp://foo:baz@smtp.example.com:25"
    EMAIL_RECEIVERS="john@example.org, marc@example.org"
    EMAIL_SENDER="alerts@example.org"
    ```

## GitLab Alerts

[:octicons-link-external-16: Official documentation][1]{: target=_blank }

[1]: https://docs.gitlab.com/ee/operations/incident_management/integrations.html#configuration

| `composer.json` config key | Environment variable | Type     | Required |
| -------------------------- | -------------------- | -------- | -------- |
| `gitlab.enable`            | `GITLAB_ENABLE`      | `bool`   | yes      |
| `gitlab.url`               | `GITLAB_URL`         | `string` | yes      |
| `gitlab.authKey`           | `GITLAB_AUTH_KEY`    | `string` | yes      |

Example:

=== "composer.json"

    ```json
    {
      "extra": {
        "update-check": {
          "gitlab": {
            "enable": true,
            "url": "https://gitlab.example.org/vendor/project/alerts/notify.json",
            "authKey": "5scqqjpgw3dzipuawi8fp19acy"
          }
        }
      }
    }
    ```

=== "Environment variables"

    ```bash
    GITLAB_ENABLE=1
    GITLAB_URL="https://gitlab.example.org/vendor/project/alerts/notify.json"
    GITLAB_AUTH_KEY="5scqqjpgw3dzipuawi8fp19acy"
    ```

## Mattermost

[:octicons-link-external-16: Official documentation][2]{: target=_blank }

[2]: https://docs.mattermost.com/developer/webhooks-incoming.html

| `composer.json` config key | Environment variable  | Type     | Required |
| -------------------------- | --------------------- | -------- | -------- |
| `mattermost.enable`        | `MATTERMOST_ENABLE`   | `bool`   | yes      |
| `mattermost.url`           | `MATTERMOST_URL`      | `string` | yes      |
| `mattermost.channel`       | `MATTERMOST_CHANNEL`  | `string` | yes      |
| `mattermost.username`      | `MATTERMOST_USERNAME` | `string` | no       |

Example:

=== "composer.json"

    ```json
    {
      "extra": {
        "update-check": {
          "mattermost": {
            "enable": true,
            "url": "https://mattermost.example.org/hooks/5scqqjpgw3dzipuawi8fp19acy",
            "channel": "alerts",
            "username": "alertbot"
          }
        }
      }
    }
    ```

=== "Environment variables"

    ```bash
    MATTERMOST_ENABLE=1
    MATTERMOST_URL="https://mattermost.example.org/hooks/5scqqjpgw3dzipuawi8fp19acy"
    MATTERMOST_CHANNEL="alerts"
    MATTERMOST_USERNAME="alertbot"
    ```

## Slack

[:octicons-link-external-16: Official documentation][3]{: target=_blank }

[3]: https://api.slack.com/messaging/webhooks

| `composer.json` config key | Environment variable | Type     | Required |
| -------------------------- | -------------------- | -------- | -------- |
| `slack.enable`             | `SLACK_ENABLE`       | `bool`   | yes      |
| `slack.url`                | `SLACK_URL`          | `string` | yes      |

Example:

=== "composer.json"

    ```json
    {
      "extra": {
        "update-check": {
          "slack": {
            "enable": true,
            "url": "https://hooks.slack.com/services/TU5C6A7/B01J/ZG5AR77F/5scqqjpgw3dzipuawi8fp19acy"
          }
        }
      }
    }
    ```

=== "Environment variables"

    ```bash
    SLACK_ENABLE=1
    SLACK_URL="https://hooks.slack.com/services/TU5C6A7/B01J/ZG5AR77F/5scqqjpgw3dzipuawi8fp19acy"
    ```

## Microsoft Teams

[:octicons-link-external-16: Official documentation][4]{: target=_blank }

[4]: https://docs.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/how-to/add-incoming-webhook

| `composer.json` config key | Environment variable | Type     | Required |
| -------------------------- | -------------------- | -------- | -------- |
| `teams.enable`             | `TEAMS_ENABLE`       | `bool`   | yes      |
| `teams.url`                | `TEAMS_URL`          | `string` | yes      |

Example:

=== "composer.json"

    ```json
    {
      "extra": {
        "update-check": {
          "teams": {
            "enable": true,
            "url": "https://my-team.webhook.office.com/webhookb2/5scqqjpgw3dzipuawi8fp19acy/IncomingWebhook/5scqqjpgw3dzipuawi8fp19acy/5scqqjpgw3dzipuawi8fp19acy"
          }
        }
      }
    }
    ```

=== "Environment variables"

    ```bash
    TEAMS_ENABLE=1
    TEAMS_URL="https://my-team.webhook.office.com/webhookb2/5scqqjpgw3dzipuawi8fp19acy/IncomingWebhook/5scqqjpgw3dzipuawi8fp19acy/5scqqjpgw3dzipuawi8fp19acy"
    ```
