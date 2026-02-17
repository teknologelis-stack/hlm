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

### System Update Manager

The HLM panel includes a sophisticated update system that integrates with GitHub releases for seamless updates.

#### Automatic Updates

1. Navigate to **Update Manager** from the dashboard
2. Click **Check for Updates** to query GitHub for new releases
3. Review the changelog and release notes
4. The system automatically:
   - Creates a backup before updating
   - Downloads the update from GitHub
   - Applies changes while preserving configurations
   - Rolls back on failure

#### Update Process

When you apply an update, the system:
1. **Creates automatic backup** - Full database backup before any changes
2. **Downloads from GitHub** - Fetches the release ZIP from the repository
3. **Extracts files** - Unpacks the update in a temporary directory
4. **Applies safely** - Only updates application files, preserving:
   - `config/database.php` (your database configuration)
   - `backups/` (your backup files)
   - `temp/` (temporary files)
   - `.env` (environment variables)
   - User data and uploads
5. **Runs migrations** - Executes any database schema updates
6. **Updates version** - Records the new version in the system
7. **Cleans up** - Removes temporary files

#### Manual Updates

If automatic updates fail or you prefer manual installation:

1. Download the release ZIP from GitHub
2. Go to Update Manager → Manual Update
3. Upload the ZIP file
4. System will handle extraction and installation

#### Rollback Support

If an update fails:
- The system automatically restores from the backup created before the update
- All failed updates are logged with error messages
- You can manually restore from any backup via the Backup History

#### Update Settings

Configure update behavior:
- **Auto-backup before update** - Automatically create backups (recommended)
- **Update channel** - Choose between stable and beta releases
- **Excluded files** - Files that should never be updated

### Backup Management

The backup system protects your data:

#### Creating Backups

- **Manual backups** - Create on-demand via the "Create Backup" button
- **Auto backups** - Automatically created before each update
- **Pre-update backups** - Special backups tagged before system updates

#### Restoring Backups

1. Navigate to Backup History
2. Find the backup you want to restore
3. Click "Restore"
4. Confirm the restoration
5. System will restore database and reload

#### Backup Retention

- Backups are stored in the `backups/` directory
- Default retention: 30 days (configurable)
- Monitor disk space regularly

## Security

- Passwords are hashed using PHP's `password_hash()`
- SQL injection protection via PDO prepared statements
- Session-based authentication
- CSRF protection (recommended to implement)
- Update system preserves sensitive configuration files
- Automatic rollback on update failures
- All errors are logged for security auditing

## Technical Details

### GitHub Integration

The update system connects to the GitHub API to:
- Check for new releases: `https://api.github.com/repos/teknologelis-stack/hlm/releases/latest`
- Download release packages as ZIP files
- Parse changelogs from release notes
- Compare versions using semantic versioning

### File Exclusions

The following files/directories are never modified during updates:
- `config/database.php` - Database configuration
- `backups/*` - Backup files
- `temp/*` - Temporary files
- `.git/*` - Git repository data
- `.env` - Environment configuration
- User uploads and custom data

### Error Handling

- All operations are logged to `logs/` directory
- Failed updates trigger automatic rollback
- Update status is tracked in the database
- Progress tracking available during updates

## License

This project is open-source software.

## Version

Current version: 1.0.0
