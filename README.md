# STCP Telegram Bot

I wrote this small Telegram bot to make it quicker to check STCP information while moving around Porto. It accepts a stop code and returns the next expected passages, or a line code and returns the stops in both directions.

The live instance is available as [`@stcp_bot`](https://t.me/stcp_bot).

This is an independent, unofficial project. It is not affiliated with, endorsed by, or operated by STCP.

## What it does

- `/paragem <código>` shows the next expected passages at a stop;
- `/linha <código>` lists the stops of a line in both directions;
- a stop code such as `FCUP1` can be sent directly;
- a line code such as `404`, `1M` or `ZC` can also be sent directly.

The replies and command descriptions are currently in European Portuguese.

## How it works

The bot reads the JSON services used by the current STCP website for real-time arrivals and route stops. Public line codes do not always match the internal identifiers used by those services, so the client resolves the mapping from the STCP line list and retains a small fallback for known exceptions such as `ZC`.

These website interfaces are not documented as a stable public API. A future STCP website change may therefore require an update to the client.

HTTP requests use HTTPS certificate verification, bounded connection and request timeouts, and this browser user agent:

```text
Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36
```

I keep this repository focused on my own code. The Telegram framework and the other third-party packages are declared through Composer and are not copied into the repository.

## Requirements

- PHP 8.1 or newer;
- the PHP cURL, JSON, Mbstring and PDO extensions;
- Composer;
- a Telegram bot token and username created with BotFather.

## Installation

```sh
git clone https://github.com/diogogithub/telegram-stcp-bot.git
cd telegram-stcp-bot
composer install
cp .env.example .env
```

Fill in at least these values in `.env`:

```dotenv
TELEGRAM_BOT_TOKEN=123456789:replace-me
TELEGRAM_BOT_USERNAME=replace_me_bot
```

The application reads normal environment variables; it does not parse `.env` automatically. For a local shell session, the file can be loaded with:

```sh
set -a
. ./.env
set +a
```

`TELEGRAM_ADMIN_IDS` is optional and accepts comma-separated numeric Telegram user IDs.

## Running with long polling

Long polling is the simplest option for local development or a supervised command-line process:

```sh
php bin/poll.php
```

Only one polling process should run for a bot token. An existing webhook must be removed first:

```sh
php bin/delete-webhook.php
```

## Running with a webhook

Point the web server document root at `public/`, or expose only `public/webhook.php`. The rest of the repository, including `vendor/`, should not be directly web-accessible.

Set the public HTTPS URL and a secret token:

```dotenv
TELEGRAM_WEBHOOK_URL=https://example.org/webhook.php
TELEGRAM_WEBHOOK_SECRET=replace-with-a-long-random-value
```

Register the webhook with:

```sh
php bin/set-webhook.php
```

Remove it with:

```sh
php bin/delete-webhook.php
```

Pass `--drop-pending` only when old queued updates should also be discarded.

## Development

Install the development dependencies and run all checks with:

```sh
composer install
composer check
```

This validates the Composer files, checks PHP syntax and PSR-12 formatting, and runs the dependency-free STCP parser tests.

The project-specific code is organised as follows:

```text
bin/       command-line entry points
commands/  Telegram command handlers
public/    webhook entry point
src/       configuration, bot setup, reply handling and STCP client
tests/     local parser and formatting tests
```

`composer.lock` is committed because this is an application and deployments should resolve the same dependency versions. The generated `vendor/` directory must remain untracked.

## Security

Never commit a Telegram token, webhook secret, local `.env` file or logs. Anyone who obtains the bot token can control the bot, so an exposed token must be revoked and replaced through BotFather.

The webhook secret remains optional so local setups stay simple, but I strongly recommend setting it for every public deployment.

Please see [SECURITY.md](SECURITY.md) for reporting security-sensitive issues.

## Licence

I release this project under the MIT Licence. See [LICENSE](LICENSE).

The runtime depends on [`longman/telegram-bot`](https://github.com/php-telegram-bot/core), which Composer retrieves under its own licence.
