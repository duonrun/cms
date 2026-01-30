# Duon Content Management Framework

> **Note**: This library is under active development, some of the listed features are still experimental and subject to change. Large parts of the documentation are missing.

Settings

```text
'session.authcookie' => '<app>_auth', // Name of the auth cookie
'session.expires' => 60 * 60 * 24,    // One day by default
```

Test database:

```sql
CREATE DATABASE duon_cms_test_db WITH TEMPLATE = template0 ENCODING = 'UTF8';
CREATE USER duon_cms_test_user PASSWORD 'duon_cms_test_password';
GRANT ALL PRIVILEGES ON DATABASE duon_cms_test_db TO duon_cms_test_user;
ALTER DATABASE duon_cms_test_db OWNER TO duon_cms_test_user;
```

to allow recreation via command RecreateDb:

```sql
ALTER USER duon_cms_test_user SUPERUSER;
```

System Requirements:

```bash
apt install php8.3 php8.3-pgsql php8.3-gd php8.3-xml php8.3-intl php8.3-curl
```

For development

```bash
apt install php8.3 php8.3-xdebug
```

macOS/homebrew:

```bash
brew install php php-intl
```

## License

This project is licensed under the [MIT license](LICENSE.md).
