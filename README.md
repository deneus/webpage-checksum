# Webpage Checksum Checker

A PHP script that checksums the HTML of a webpage daily using GitHub Actions.

## Setup

1. **Set the webpage URL as a GitHub secret:**
   - Go to your repository settings
   - Navigate to "Secrets and variables" → "Actions"
   - Click "New repository secret"
   - Name: `WEBPAGE_URL`
   - Value: The URL of the webpage you want to monitor (e.g., `https://example.com`)

2. **Set up WhatsApp notifications (optional):**
   - Follow the [CallMeBot setup instructions](https://www.callmebot.com/blog/free-api-whatsapp-messages/):
     1. Add the phone number **+34 611 01 16 37** to your WhatsApp contacts
     2. Send the message **"I allow callmebot to send me messages"** to that contact
     3. Wait for the API key response (usually within 2 minutes)
   - Add two GitHub secrets:
     - Name: `WHATSAPP_PHONE` - Your phone number with country code (e.g., `+34123123123`)
     - Name: `WHATSAPP_API_KEY` - The API key you received from CallMeBot

3. **The workflow will run daily at 00:00 UTC**

4. **Manual testing:**
   ```bash
   export WEBPAGE_URL="https://example.com"
   export WHATSAPP_PHONE="+34123123123"
   export WHATSAPP_API_KEY="your_api_key_here"
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
- If the checksum changes:
  - Sends a WhatsApp notification with the date and change details (if configured)
  - The GitHub Action will commit the new checksum to the repository
- The exit code indicates whether the checksum changed (1) or remained the same (0)

## Customization

You can modify `.github/workflows/check-page.yml` to:
- Change the schedule (cron expression)
- Add notifications when checksum changes
- Add additional actions when changes are detected

