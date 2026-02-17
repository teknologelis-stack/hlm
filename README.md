# HLM - Minimal MikroTik Panel

A minimal, modern MikroTik management panel with a built-in system update manager.

## Features

- 🔐 Secure authentication system
- 📊 Dashboard overview
- 🔄 System update manager
- 💾 Automated backup system
- 🎨 Modern Bootstrap 5 UI
- 🔔 SweetAlert2 notifications

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache/Nginx web server

## Installation

1. Clone the repository:
```bash
git clone https://github.com/teknologelis-stack/hlm.git
cd hlm
```

2. Create database and import schema:
```bash
mysql -u root -p -e "CREATE DATABASE hlm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p hlm_db < config/migrations/init.sql
```

3. Configure database connection in `config/database.php`

4. Set appropriate permissions:
```bash
chmod 755 backups temp
```

5. Access the application in your browser

## Default Credentials

- **Username:** admin
- **Password:** admin

⚠️ **Important:** Change the default password after first login!

## Directory Structure

```
hlm/
├── api/                    # API endpoints
├── assets/                 # CSS and JavaScript files
├── backups/                # System backups
├── config/                 # Configuration files
│   └── migrations/         # Database migrations
├── includes/               # PHP includes and classes
├── pages/                  # Application pages
├── temp/                   # Temporary files
└── index.php              # Entry point (Login page)
```

## Usage

### Update Manager

1. Navigate to **Update Manager** from the dashboard
2. Click **Check for Updates** to see available updates
3. Create a backup before updating (recommended)
4. Apply the update

## Security

- Passwords are hashed using PHP's `password_hash()`
- SQL injection protection via PDO prepared statements
- Session-based authentication
- CSRF protection (recommended to implement)

## License

This project is open-source software.

## Version

Current version: 1.0.0
