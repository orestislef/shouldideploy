# Should I Deploy Today? 🚀

A fun deployment decision tool that helps developers decide whether they should deploy their code based on the current day, time, and special conditions.

## Files

### `index.html`
Interactive web interface that provides a user-friendly form to check if you should deploy today.

**Features:**
- Language selection (English/Greek)
- Timezone selection 
- Custom date picker
- Real-time API integration
- Responsive design with loading states
- Direct API examples

### `api.php`
RESTful API backend that contains the deployment decision logic.

**Features:**
- Multi-language support (English/Greek)
- Timezone-aware date handling
- Custom date support
- JSON response format
- CORS headers for cross-origin requests

## API Usage

### Endpoint
```
GET api.php
```

### Parameters
- `lang` - Language code (`en` or `el`, defaults to `en`)
- `tz` - Timezone (defaults to `UTC`)
- `date` - Custom date in YYYY-MM-DD format (optional)

### Example Requests
```
# Default (English, UTC, current date)
api.php

# Greek language with Athens timezone
api.php?lang=el&tz=Europe/Athens

# Custom date
api.php?date=2024-12-25&lang=en
```

### Response Format
```json
{
  "timezone": "UTC",
  "date": "2024-09-05T10:44:00+00:00",
  "shouldideploy": false,
  "message": "No, it's Friday"
}
```

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

1. Place both files on a web server with PHP support
2. Access `index.html` in your browser
3. The API at `api.php` will be automatically called

No additional dependencies or setup required.