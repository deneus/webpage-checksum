# Webpage Change Monitor

This tool monitors a webpage for changes and sends email notifications when
changes are detected.

## How It Works

1. The script fetches the webpage content daily.
2. It calculates a SHA256 hash of the content.
3. It compares the current hash with the previous hash (stored in temporary
   storage).
4. If the hashes differ, it sends an email notification.
5. The new hash is saved for the next check.

## Setup Instructions

### 1. Configure GitHub Secrets

Go to your GitHub repository settings → Secrets and variables → Actions, and
add the following secrets:

- **`WEBPAGE_URL`**: The URL of the webpage to monitor (e.g.,
  `https://example.com/page`)
- **`SMTP_SERVER`**: Your SMTP server hostname (e.g., `smtp.gmail.com`)
- **`SMTP_PORT`**: SMTP port (default: `587`, optional)
- **`SMTP_USERNAME`**: Your SMTP username
- **`SMTP_PASSWORD`**: Your SMTP password or app-specific password
- **`FROM_EMAIL`**: Email address to send from
- **`TO_EMAIL`**: Email address to receive notifications

### 2. Email Provider Examples

#### Gmail
- **SMTP_SERVER**: `smtp.gmail.com`
- **SMTP_PORT**: `587`
- **SMTP_USERNAME**: Your Gmail address
- **SMTP_PASSWORD**: Use an [App Password](https://support.google.com/accounts/answer/185833)
  (not your regular password)

#### Outlook/Hotmail
- **SMTP_SERVER**: `smtp-mail.outlook.com`
- **SMTP_PORT**: `587`
- **SMTP_USERNAME**: Your Outlook email address
- **SMTP_PASSWORD**: Your Outlook password

#### SendGrid (Free Tier)
- **SMTP_SERVER**: `smtp.sendgrid.net`
- **SMTP_PORT**: `587`
- **SMTP_USERNAME**: `apikey`
- **SMTP_PASSWORD**: Your SendGrid API key

### 3. Schedule Configuration

The workflow runs daily at 9:00 AM UTC by default. To change the schedule,
edit `.github/workflows/webpage-monitor.yml` and modify the cron expression:

```yaml
schedule:
  - cron: '0 9 * * *'  # Daily at 9:00 AM UTC
```

Cron format: `minute hour day month day-of-week`

Examples:
- `'0 9 * * *'` - Daily at 9:00 AM UTC
- `'0 */6 * * *'` - Every 6 hours
- `'0 9 * * 1-5'` - Weekdays only at 9:00 AM UTC

### 4. Manual Testing

You can manually trigger the workflow from the GitHub Actions tab:

1. Go to your repository on GitHub
2. Click on "Actions"
3. Select "Webpage Change Monitor"
4. Click "Run workflow"

## Local Usage

You can also run the script locally:

```bash
export WEBPAGE_URL="https://example.com/page"
export SMTP_SERVER="smtp.gmail.com"
export SMTP_PORT="587"
export SMTP_USERNAME="your-email@gmail.com"
export SMTP_PASSWORD="your-app-password"
export FROM_EMAIL="your-email@gmail.com"
export TO_EMAIL="recipient@example.com"
export STORAGE_DIR="/tmp/webpage_monitor"

python3 webpage_monitor/check_webpage.py
```

## How It Detects Changes

The script uses SHA256 hashing to detect changes. Any modification to the
webpage content (even a single character) will result in a different hash and
trigger a notification.

**Note**: Dynamic content (like timestamps, ads, or randomized elements) will
cause false positives. For such pages, you may need to filter the content
before hashing.

## How It Works with GitHub Actions

The workflow uses GitHub Actions artifacts to persist the hash state between
runs:

1. **First Run**: Downloads any existing hash artifact (if none exists,
   the first run is treated as initialization)
2. **Check**: Compares current webpage hash with previous hash
3. **Notify**: Sends email if changes are detected
4. **Save**: Uploads the new hash as an artifact for the next run

The hash is stored in GitHub Actions artifacts, which persist for 90 days. This
ensures continuous monitoring even if the workflow doesn't run for a while.

## Limitations

- **Free GitHub Actions**: Free tier includes 2,000 minutes/month. Daily runs
  use ~1-2 minutes each, so ~60 minutes/month, well within limits.
- **First Run**: The first run after deployment will always be treated as
  "initial check" and won't send a notification (no previous hash exists).
- **Artifact Storage**: Hash artifacts are retained for 90 days. If the
  workflow doesn't run for more than 90 days, the next run will be treated as
  the first run.
- **Rate Limiting**: Be mindful of the target website's rate limits. Daily
  checks are usually fine, but very frequent checks might be blocked.

## Alternative Free Hosting Options

If GitHub Actions doesn't meet your needs, here are other free options:

1. **Cloudflare Workers** (with Cron Triggers)
   - Free tier: 100,000 requests/day
   - Requires JavaScript/TypeScript

2. **PythonAnywhere**
   - Free tier: 1 scheduled task
   - Python-based, easy setup

3. **Render**
   - Free tier available
   - Can run scheduled cron jobs

4. **Vercel Cron Jobs**
   - Free tier available
   - Serverless functions

