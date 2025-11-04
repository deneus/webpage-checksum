# Webpage Checksum Checker

A PHP script that checksums the HTML of a webpage daily using GitHub Actions.

## Setup

1. **Set the webpage URL as a GitHub secret:**
   - Go to your repository settings
   - Navigate to "Secrets and variables" → "Actions"
   - Click "New repository secret"
   - Name: `WEBPAGE_URL`
   - Value: The URL of the webpage you want to monitor (e.g., `https://example.com`)

2. **The workflow will run daily at 00:00 UTC**

3. **Manual testing:**
   ```bash
   export WEBPAGE_URL="https://example.com"
   php check-page.php
   ```
   
   Or pass the URL as an argument:
   ```bash
   php check-page.php https://example.com
   ```

## How it works

- The script fetches the HTML from the specified webpage
- Calculates an MD5 checksum of the HTML content
- Compares it with the previous checksum (stored in `.checksum`)
- If the checksum changes, the GitHub Action will commit the new checksum to the repository
- The exit code indicates whether the checksum changed (1) or remained the same (0)

## Customization

You can modify `.github/workflows/check-page.yml` to:
- Change the schedule (cron expression)
- Add notifications when checksum changes
- Add additional actions when changes are detected

