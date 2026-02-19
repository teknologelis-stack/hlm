# AGENTS.md - HLM (MikroTik Panel) Development Guide

This document provides guidance for agentic coding agents working on this codebase.

## Project Overview

HLM is a minimal, modern MikroTik management panel built in PHP with:
- Pure PHP (no framework)
- MySQL/MariaDB database
- Bootstrap 5 UI
- Built-in update system with GitHub integration

## Build/Lint/Test Commands

### Running Tests

This project uses custom PHP test scripts rather than a formal test framework.

```bash
# Run system verification test
php test-system.php

# Run database test
php test-db.php

# Run login test
php test-login.php

# Run session test
php test-session.php

# Run update system test
php test-update-system.php

# Run specific test (create a new test file similar to existing tests)
php test-[feature].php
```

### Database Migrations

```bash
# Run main migration (requires MySQL CLI)
mysql -u root -p mikrotik_panel < config/migrations/init.sql

# Run fix migration for schema updates
mysql -u root -p mikrotik_panel < config/migrations/fix_update_tables.sql
```

### No Formal Linting

This project does not have PHP_CodeSniffer, Psalm, or PHPStan configured. Code style is informal.

---

## Code Style Guidelines

### General Principles

- PHP 7.4+ compatibility (avoid PHP 8+ syntax like named arguments, match expressions)
- Use `<?php` opening tag (no short tags `<?`)
- Always use full PHP tags, never close with `?>` at end of files (except when including)
- End files with newline character

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `UpdateManager`, `GitHubUpdateService` |
| Methods | camelCase | `checkForUpdates()`, `createBackup()` |
| Functions | snake_case | `get_current_version()` |
| Variables | snake_case | `$db_connection`, `$current_version` |
| Constants | UPPER_SNAKE_CASE | `APP_VERSION`, `ROOT_PATH` |
| Database tables | snake_case | `system_updates`, `system_backups` |

### File Structure

```
├── api/                    # API endpoints (return JSON)
├── assets/                # JS/CSS files
├── backups/               # Backup files (git-ignored)
├── config/                # Configuration
│   ├── app.php           # App constants and settings
│   ├── database.php      # Database connection (PDO)
│   └── migrations/       # SQL migrations
├── includes/              # PHP classes
├── pages/                # Page controllers/views
├── temp/                 # Temporary files (git-ignored)
├── logs/                 # Error logs (git-ignored)
└── index.php            # Entry point (login)
```

### Class Structure

Follow this pattern for classes in `includes/`:

```php
<?php
/**
 * Class Description
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
// Other requires as needed

class ClassName {
    private $db;
    private $otherDependency;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        // Initialize other dependencies
    }
    
    /**
     * Method description
     * @param type $param Description
     * @return type Return description
     */
    public function methodName($param) {
        // Implementation
    }
    
    /**
     * Private helper method
     */
    private function helperMethod() {
        // Implementation
    }
}
```

### Database Access

- Use PDO with prepared statements for all queries
- Use singleton pattern for Database class
- Always use parameterized queries (never string concatenation in SQL)

```php
// Good
$stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Bad - SQL injection risk
$stmt = $this->db->query("SELECT * FROM users WHERE id = $userId");
```

### Error Handling

- Use try/catch blocks for operations that may fail
- Log errors using `error_log()` for debugging
- Return structured error arrays from methods
- Never expose raw exception messages to users

```php
try {
    $result = $this->riskyOperation();
    return ['success' => true, 'data' => $result];
} catch (Exception $e) {
    error_log("[ClassName] Error: " . $e->getMessage());
    return ['success' => false, 'error' => 'User-friendly error message'];
}
```

### API Endpoints

All API endpoints must:
- Return JSON with `Content-Type: application/json`
- Include `success` key (boolean)
- Include `data` key on success or `error` key on failure
- Check authentication before processing
- Disable error display: `ini_set('display_errors', 0)`

```php
<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Process request
    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    error_log("[API:endpoint] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
```

### Authentication

- Use session-based authentication
- Include `config/app.php` at the top of every page (starts session)
- Check auth in API endpoints before processing
- Use `$auth->requireLogin()` to protect pages

### Constants

All paths and settings should use constants from `config/app.php`:
- `ROOT_PATH` - Application root directory
- `BASE_URL` - Base URL
- `TEMP_PATH` - Temp directory for updates
- `BACKUPS_PATH` - Backup directory
- `LOGS_PATH` - Log directory

### Import/Require Pattern

Use `require_once` with `__DIR__` for relative paths:

```php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SomeClass.php';
```

### Logging

- Use `error_log()` with prefix: `error_log("[ClassName] Message")`
- Log important operations in UpdateManager (steps, success, failures)
- Log errors with context: `error_log("[Auth] Login error: " . $e->getMessage())`

### JavaScript/AJAX

- Use fetch API for AJAX calls
- Handle JSON responses consistently
- Use SweetAlert2 for user notifications (already included)

### Security

- Use `password_hash()` and `password_verify()` for passwords
- Never store plain text passwords
- Use PDO prepared statements (already noted)
- Set `session.cookie_httponly = 1`
- Keep `config/database.php` out of version control (it's in .gitignore)

---

## Common Tasks

### Adding a New API Endpoint

1. Create file in `api/` directory
2. Follow API endpoint structure above
3. Add authentication check
4. Return JSON response

### Adding a New Page

1. Create file in `pages/` directory
2. Include header, auth check, and footer
3. Use Bootstrap 5 for styling

### Adding a Database Migration

1. Create SQL file in `database/migrations/` or `config/migrations/`
2. Use sequential naming: `006_update_version_to_1_0_5.sql`
3. Document what the migration does

### Adding a New Class

1. Create in `includes/` directory
2. Follow class structure above
3. Require config files at top
4. Use Database singleton for connection

---

## Configuration Files (Do Not Commit)

These files contain sensitive data and are gitignored:
- `config/database.php` - Database credentials
- `backups/` - Backup files
- `temp/` - Temporary files
- `logs/` - Log files

---

## Testing New Code

1. Run relevant test script: `php test-[feature].php`
2. Test manually in browser
3. Check `logs/error.log` for errors
4. Run `php test-system.php` to verify overall health
