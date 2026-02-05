# Duon Content Management Framework

> **Note**: This library is under active development, some of the listed features
> are still experimental and subject to change. Large parts of the documentation
> are missing.

## Database Configuration

Duon CMS supports two database backends: **PostgreSQL** (recommended for production)
and **SQLite** (ideal for development, small sites, or embedded deployments).

### PostgreSQL Setup

```php
// config.php
return [
    'db.dsn' => 'pgsql:host=localhost;port=5432;dbname=duoncms;user=duoncms;password=secret',
];
```

Create the database:

```bash
echo "duoncms" | createuser --pwprompt --createdb duoncms
createdb --owner duoncms duoncms
```

Required PHP extensions:

```bash
# Debian/Ubuntu
apt install php8.5-pgsql

# macOS/Homebrew
brew install php
```

### SQLite Setup

```php
// config.php
return [
    'db.dsn' => 'sqlite:/var/lib/duoncms/database.sqlite',
];
```

Required PHP extensions:

```bash
# Debian/Ubuntu
apt install php8.5-sqlite3

# macOS/Homebrew (included by default)
brew install php
```

### SQLite Operational Notes

When using SQLite in production, keep these considerations in mind:

#### WAL Mode

Duon CMS automatically enables WAL (Write-Ahead Logging) mode for better
concurrency. This creates two additional files alongside your database:

- `database.sqlite-wal` - Write-ahead log
- `database.sqlite-shm` - Shared memory index

These files are essential for database integrity and must be included in backups.

#### Backup Procedures

To safely backup an SQLite database:

```bash
# Option 1: Use sqlite3 backup command (recommended)
sqlite3 /var/lib/duoncms/database.sqlite ".backup /backup/database.sqlite"

# Option 2: Copy all files atomically (requires brief write lock)
cp /var/lib/duoncms/database.sqlite* /backup/
```

Never copy only the main `.sqlite` file while the application is running.

#### File Permissions

```bash
# Recommended permissions (owner read/write only)
chmod 600 /var/lib/duoncms/database.sqlite
chmod 600 /var/lib/duoncms/database.sqlite-wal
chmod 600 /var/lib/duoncms/database.sqlite-shm

# Ensure web server user owns the files
chown www-data:www-data /var/lib/duoncms/database.sqlite*
```

Store the database file outside the web root to prevent direct access.

#### Concurrency Limitations

SQLite uses file-level locking:

- Multiple readers can access the database simultaneously
- Only one writer can write at a time (others wait up to 5 seconds by default)
- For high-write workloads, consider PostgreSQL instead

#### SQLite PRAGMA Configuration

Duon CMS applies these PRAGMAs automatically:

| PRAGMA           | Default | Description                         |
| ---------------- | ------- | ----------------------------------- |
| `foreign_keys`   | 1       | Enable foreign key constraints      |
| `journal_mode`   | WAL     | Use Write-Ahead Logging             |
| `synchronous`    | NORMAL  | Balance durability and performance  |
| `busy_timeout`   | 5000    | Wait up to 5s for locks             |
| `trusted_schema` | 0       | Disable untrusted schema features   |

Override via config:

```php
return [
    'db.sqlite.pragmas.busy_timeout' => 10000, // 10 seconds
    'db.sqlite.pragmas.synchronous' => 'FULL', // Maximum durability
];
```

## Fulltext Search

Duon CMS includes fulltext search capabilities for both database backends.

### Configuration

Fulltext search is enabled by default when the database supports it:

```php
return [
    // Auto-detect based on driver (default)
    'features.fulltext' => null,

    // Explicitly enable or disable
    'features.fulltext' => true,  // Enable
    'features.fulltext' => false, // Disable
];
```

### Implementation Details

| Backend    | Technology                           | Features                              |
| ---------- | ------------------------------------ | ------------------------------------- |
| PostgreSQL | Built-in `tsvector` with GIN index   | Stemming, ranking, phrase search      |
| SQLite     | FTS5 virtual table                   | Porter stemmer, unicode normalization |

### DSL Usage

Query nodes using fulltext search in the Finder DSL:

```php
// Search across all locales
$finder->nodes()->filter("fulltext = 'search terms'");

// Search in specific locale
$finder->nodes()->filter("fulltext.en = 'search terms'");
$finder->nodes()->filter("fulltext.de = 'Suchbegriffe'");

// Combine with other filters
$finder->nodes()
    ->types('article', 'news')
    ->filter("fulltext = 'important' AND published = true");
```

## System Requirements

### Common Requirements

```bash
# Debian/Ubuntu
apt install php8.5 php8.5-gd php8.5-xml php8.5-intl php8.5-curl

# macOS/Homebrew
brew install php php-intl
```

### PostgreSQL Backend

```bash
apt install php8.5-pgsql
```

### SQLite Backend

```bash
apt install php8.5-sqlite3
```

### Development

```bash
apt install php8.5-xdebug
```

## Configuration Reference

```php
return [
    // Database
    'db.dsn' => 'pgsql:host=localhost;dbname=duoncms;user=duoncms;password=secret',

    // Session
    'session.authcookie' => 'duon_auth',  // Name of the auth cookie
    'session.expires' => 60 * 60 * 24,    // Session lifetime (1 day)

    // Features
    'features.fulltext' => null,          // Auto-detect fulltext support

    // SQLite-specific (only when using SQLite)
    'db.sqlite.pragmas.foreign_keys' => 1,
    'db.sqlite.pragmas.journal_mode' => 'WAL',
    'db.sqlite.pragmas.synchronous' => 'NORMAL',
    'db.sqlite.pragmas.busy_timeout' => 5000,
    'db.sqlite.pragmas.trusted_schema' => 0,
];
```

## License

This project is licensed under the [MIT license](LICENSE.md).
