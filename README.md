# Webpage Checksum Checker

A PHP script that checksums the HTML of a webpage daily using GitHub Actions. Built with PSR-4 autoloading, following SOLID principles and clean code practices.

## Architecture

This project follows:
- **PSR-4** autoloading standards
- **SOLID** principles
- **Clean Code** practices
- **Object-Oriented Programming**

### Structure

```
src/
├── Checker.php              # Main orchestrator
├── Fetcher.php              # Fetches webpage content
├── Calculator.php           # Calculates checksums
├── Storage.php              # Handles checksum storage
├── Detector.php            # Detects checksum changes
├── MessageFormatter.php    # Formats notification messages
├── Notification/
│   ├── NotificationInterface.php
│   └── EmailNotifier.php   # Email notification implementation
└── Output/
    ├── OutputInterface.php
    └── ConsoleOutput.php   # Console output implementation
```

## Setup

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Set the webpage URL as a GitHub secret:**
   - Go to your repository settings
   - Navigate to "Secrets and variables" → "Actions"
   - Click "New repository secret"
   - Name: `WEBPAGE_URL`
   - Value: The URL of the webpage you want to monitor (e.g., `https://example.com`)

3. **Set up email notifications (optional):**
   - Add GitHub secrets:
     - Name: `EMAIL_TO` - Recipient email address
     - Name: `EMAIL_FROM` - Sender email address (optional)
     - Name: `EMAIL_REPLY_TO` - Reply-to address (optional)

4. **The workflow will run daily at 00:00 UTC**

5. **Manual testing:**
   ```bash
   export WEBPAGE_URL="https://example.com"
   export EMAIL_TO="your-email@example.com"
   export EMAIL_FROM="notifications@example.com"
   php bin/check-page.php
   ```
   
   Or pass the URL as an argument:
   ```bash
   php bin/check-page.php https://example.com
   ```

## How it works

- The script fetches the HTML from the specified webpage
- Calculates an MD5 checksum of the HTML content
- Compares it with the previous checksum (stored in `.checksum`)
- If the checksum changes:
  - Sends an email notification with the date and change details (if configured)
  - The GitHub Action will commit the new checksum to the repository
- The exit code indicates whether the checksum changed (1) or remained the same (0)

## Design Principles

### SOLID Principles

- **Single Responsibility**: Each class has one reason to change
  - `Fetcher` - only fetches content
  - `Calculator` - only calculates checksums
  - `Storage` - only handles storage
  - `Detector` - only detects changes
  - `EmailNotifier` - only sends emails

- **Open/Closed**: Open for extension, closed for modification
  - New notification types can be added by implementing `NotificationInterface`
  - New output types can be added by implementing `OutputInterface`

- **Liskov Substitution**: Subtypes are substitutable
  - Any `NotificationInterface` implementation can replace `EmailNotifier`
  - Any `OutputInterface` implementation can replace `ConsoleOutput`

- **Interface Segregation**: Many specific interfaces
  - `NotificationInterface` - only notification methods
  - `OutputInterface` - only output methods

- **Dependency Inversion**: Depend on abstractions
  - `Checker` depends on `NotificationInterface`, not concrete implementations
  - All classes depend on `OutputInterface`, not concrete implementations

## Customization

You can modify `.github/workflows/check-page.yml` to:
- Change the schedule (cron expression)
- Add notifications when checksum changes
- Add additional actions when changes are detected

You can extend the functionality by:
- Implementing `NotificationInterface` for other notification types (SMS, Slack, etc.)
- Implementing `OutputInterface` for other output types (file logging, etc.)

