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
