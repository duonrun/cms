# Duon Content Management Framework

> **Note**: This library is under active development, some of the listed features are still experimental and subject to change. Large parts of the documentation are missing.

## Defining content types

Content types (nodes) are plain PHP classes annotated with attributes. There is no base class to extend. Dependencies are autowired from the Registry via `duon/wire`.

```php
use Duon\Cms\Field\Text;
use Duon\Cms\Field\Grid;
use Duon\Cms\Field\Image;
use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Required;
use Duon\Cms\Field\Meta\Translate;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Node\Attribute\Name;
use Duon\Cms\Node\Attribute\Page;
use Duon\Cms\Node\Attribute\Route;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Core\Request;

#[Page, Name('Department'), Route('/{title}')]
final class Department implements HasTitle
{
    public function __construct(
        protected readonly Request $request,
        protected readonly Finder $find,
    ) {}

    #[Label('Title'), Required, Translate]
    public Text $title;

    #[Label('Content'), Translate]
    public Grid $content;

    #[Label('Image')]
    public Image $clipart;

    public function title(): string
    {
        return $this->title?->value()->unwrap() ?? '';
    }
}
```

### Node kind attributes

| Attribute | Kind |
| --------- | ---- |
| `#[Page]` | A routable page with a URL path |
| `#[Block]` | A reusable content block (no URL) |
| `#[Document]` | A data-only node (no URL, no template) |

### Metadata attributes

| Attribute | Purpose |
| --------- | ------- |
| `#[Name('...')]` | Human-readable display name |
| `#[Handle('...')]` | URL-safe identifier (auto-derived if omitted) |
| `#[Route('...')]` | URL pattern for pages |
| `#[Render('...')]` | Template name override |
| `#[Title('...')]` | Field name to use as title |
| `#[FieldOrder('...')]` | Admin panel field order |
| `#[Deletable(false)]` | Prevent deletion in admin panel (default: `true`) |

### Behavioral interfaces

| Interface | Method | Purpose |
| --------- | ------ | ------- |
| `HasTitle` | `title(): string` | Computed title (takes precedence over `#[Title]`) |
| `HasInit` | `init(): void` | Post-hydration initialization hook |
| `HandlesFormPost` | `formPost(?array $body): Response` | Frontend form submission handling |
| `ProvidesRenderContext` | `renderContext(): array` | Extra template variables |

## Settings

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
apt install php8.5 php8.5-pgsql php8.5-gd php8.5-xml php8.5-intl php8.5-curl
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
