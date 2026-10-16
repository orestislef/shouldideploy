# Should I Deploy Today? 🚀

A fun deployment decision tool that helps developers decide whether they should deploy their code based on the current day, time, and special conditions.

Live at https://orestislef.gr/shouldideploy/

This is a PHP port of [baires/shouldideploy](https://github.com/baires/shouldideploy) (Next.js), kept in sync with upstream and with Greek and Bulgarian added.

![shouldideploy](https://orestislef.gr/shouldideploy/badge.php?tz=Europe/Athens)

## Files

- `index.html` web ui. Language picker at the top, timezone, optional date, live docs for the api.
- `api.php` JSON endpoint
- `badge.php` SVG badge for READMEs
- `cli.php` plain text endpoint for scripts and CI
- `lib.php` shared logic (time checks, picking the reasons)
- `locales/*.json` the reasons per language, same format as upstream

## Languages

`en`, `es`, `es-AR`, `pt`, `cs`, `sl` come straight from upstream. `el` and `bg` are ours.

`lang` falls back the same way upstream does: exact match (`es-AR`), then the base language (`es`), then English.

## API

### JSON

```
GET api.php
```

- `lang` language code, defaults to `en`
- `tz` timezone, defaults to `UTC`
- `date` custom date in YYYY-MM-DD format (optional)

```
api.php
api.php?lang=el&tz=Europe/Athens
api.php?lang=bg&tz=Europe/Sofia
api.php?date=2024-12-25&lang=es
```

```json
{
  "timezone": "UTC",
  "date": "2024-09-05T10:44:00+00:00",
  "shouldideploy": false,
  "message": "No, it's Friday"
}
```

### Badge

```
![Should I Deploy?](https://orestislef.gr/shouldideploy/badge.php?tz=Europe/Athens)
```

Supports `tz`, `lang` and `style` (`flat` default, `flat-square`, `plastic`, `for-the-badge`, `social`).

### CLI

```
curl --fail -s "https://orestislef.gr/shouldideploy/cli.php?tz=Europe/Athens" && ./deploy.sh
```

Returns `[shouldideploy] YES: <reason>` with HTTP 200 when it's fine to deploy, or `[shouldideploy] NO: <reason>` with HTTP 423 when not, so `curl --fail` blocks the deploy. Supports `tz` and `lang`.

## Deployment Logic

The tool recommends **NOT** to deploy if:
- It's Friday (any time)
- It's Friday the 13th
- It's afternoon (after 4 PM)
- It's Thursday afternoon
- It's Friday afternoon
- It's weekend
- It's holidays (Christmas Eve afternoon, Christmas Day, New Year's Eve afternoon, New Year's Day)

Otherwise, it recommends deploying with encouraging messages.

## Setup

Copy everything (including `locales/`) to a web server with PHP. No extensions or other dependencies needed.

## Syncing with upstream

Copy the json files from upstream `locales/` over ours. Leave `el.json` and `bg.json` alone, and if upstream added new reasons, add the Greek and Bulgarian versions by hand.
