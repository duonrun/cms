# Duon Content Management Framework

> **Note**: This library is under active development, some of the listed features are still experimental and subject to change. Large parts of the documentation are missing.

Settings

```text
'session.authcookie' => '<app>_auth', // Name of the auth cookie
'session.expires' => 60 * 60 * 24,    // One day by default
```

Test database:

```bash
echo "duoncms" | createuser --pwprompt --createdb duoncms
createdb --owner duoncms duoncms
```

System Requirements:

```bash
apt install php8.5 php8.5-pdo php8.5-gd php8.5-xml php8.5-intl php8.5-curl
```

Database drivers:

```bash
# PostgreSQL
apt install php8.5-pgsql

# SQLite
apt install php8.5-sqlite3
```

Database configuration:

```php
// PostgreSQL
'db.dsn' => 'pgsql:host=localhost;dbname=duoncms;user=duoncms;password=duoncms',

// SQLite (file path)
'db.dsn' => 'sqlite:/var/lib/duon/duoncms.sqlite',
```

Fulltext:

```php
// Enable or disable fulltext search per driver.
// Defaults: enabled on pgsql, disabled on sqlite until explicitly enabled.
'db.features.fulltext.enabled' => true,
```

SQLite operational notes:

- WAL mode is recommended for concurrency; the CMS sets this by default via PRAGMA.
- Backups must copy the database file and the `-wal` and `-shm` files atomically.
- Store the SQLite file outside the web root with strict permissions (e.g. 0600).
- SQLite allows only a single writer at a time; design write-heavy workloads accordingly.

For development

```bash
apt install php8.5 php8.5-xdebug
```

macOS/homebrew:

```bash
brew install php php-intl
```

## License

This project is licensed under the [MIT license](LICENSE.md).
