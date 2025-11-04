#!/usr/bin/env python3
"""
Webpage Change Monitor Script.

This script checks a webpage for changes and sends an email notification
when changes are detected.
"""

import hashlib
import os
import smtplib
import sys
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from pathlib import Path
from urllib.request import urlopen, Request
from urllib.error import URLError


def fetch_webpage(url: str, user_agent: str = None) -> bytes:
    """
    Fetch the content of a webpage.

    Args:
        url: The URL to fetch.
        user_agent: Optional custom user agent string.

    Returns:
        The webpage content as bytes.

    Raises:
        URLError: If the webpage cannot be fetched.
    """
    headers = {}
    if user_agent:
        headers['User-Agent'] = user_agent
    else:
        headers['User-Agent'] = (
            'Mozilla/5.0 (compatible; WebpageMonitor/1.0; '
            '+https://github.com/your-org/cablecar)'
        )

    request = Request(url, headers=headers)
    with urlopen(request, timeout=30) as response:
        return response.read()


def calculate_hash(content: bytes) -> str:
    """
    Calculate SHA256 hash of content.

    Args:
        content: The content to hash.

    Returns:
        The hexadecimal hash string.
    """
    return hashlib.sha256(content).hexdigest()


def get_previous_hash(storage_path: Path) -> str | None:
    """
    Get the previous hash from storage file.

    Args:
        storage_path: Path to the storage file.

    Returns:
        The previous hash or None if not found.
    """
    if storage_path.exists():
        return storage_path.read_text().strip()
    return None


def save_hash(storage_path: Path, hash_value: str) -> None:
    """
    Save hash to storage file.

    Args:
        storage_path: Path to the storage file.
        hash_value: The hash value to save.
    """
    storage_path.parent.mkdir(parents=True, exist_ok=True)
    storage_path.write_text(hash_value)


def send_email(
    smtp_server: str,
    smtp_port: int,
    smtp_username: str,
    smtp_password: str,
    from_email: str,
    to_email: str,
    url: str,
    subject: str = None,
) -> None:
    """
    Send email notification about webpage change.

    Args:
        smtp_server: SMTP server hostname.
        smtp_port: SMTP server port.
        smtp_username: SMTP username.
        smtp_password: SMTP password.
        from_email: Sender email address.
        to_email: Recipient email address.
        url: The URL that changed.
        subject: Optional custom email subject.
    """
    if subject is None:
        subject = f'Webpage Change Detected: {url}'

    msg = MIMEMultipart()
    msg['From'] = from_email
    msg['To'] = to_email
    msg['Subject'] = subject

    body = (
        f'A change has been detected on the following webpage:\n\n'
        f'URL: {url}\n\n'
        f'Please check the webpage to see what has changed.\n'
    )
    msg.attach(MIMEText(body, 'plain'))

    try:
        with smtplib.SMTP(smtp_server, smtp_port) as server:
            server.starttls()
            server.login(smtp_username, smtp_password)
            server.send_message(msg)
        print(f'Email notification sent to {to_email}')
    except Exception as e:
        print(f'Error sending email: {e}', file=sys.stderr)
        sys.exit(1)


def main():
    """Main function to run the webpage monitor."""
    # Get configuration from environment variables.
    url = os.getenv('WEBPAGE_URL')
    if not url:
        print('Error: WEBPAGE_URL environment variable is not set',
              file=sys.stderr)
        sys.exit(1)

    storage_dir = Path(os.getenv('STORAGE_DIR', '/tmp/webpage_monitor'))
    storage_file = storage_dir / f'{hashlib.md5(url.encode()).hexdigest()}.txt'

    # Email configuration.
    smtp_server = os.getenv('SMTP_SERVER')
    smtp_port = int(os.getenv('SMTP_PORT', '587'))
    smtp_username = os.getenv('SMTP_USERNAME')
    smtp_password = os.getenv('SMTP_PASSWORD')
    from_email = os.getenv('FROM_EMAIL')
    to_email = os.getenv('TO_EMAIL')

    if not all([smtp_server, smtp_username, smtp_password, from_email,
                to_email]):
        print('Error: Email configuration is incomplete',
              file=sys.stderr)
        sys.exit(1)

    try:
        # Fetch the webpage.
        print(f'Fetching webpage: {url}')
        content = fetch_webpage(url)

        # Calculate hash.
        current_hash = calculate_hash(content)
        print(f'Current hash: {current_hash}')

        # Get previous hash.
        previous_hash = get_previous_hash(storage_file)

        if previous_hash is None:
            print('No previous hash found. Saving current hash.')
            save_hash(storage_file, current_hash)
            print('Initial check complete. No notification sent.')
        elif current_hash != previous_hash:
            print('Change detected! Sending email notification.')
            send_email(
                smtp_server,
                smtp_port,
                smtp_username,
                smtp_password,
                from_email,
                to_email,
                url,
            )
            save_hash(storage_file, current_hash)
            print('Hash updated.')
        else:
            print('No changes detected.')

    except URLError as e:
        print(f'Error fetching webpage: {e}', file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f'Unexpected error: {e}', file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()

