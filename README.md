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
use Duon\Cms\Cms;
use Duon\Cms\Schema\Name;
use Duon\Cms\Schema\Route;
use Duon\Cms\Node\Contract\Title;
use Duon\Core\Request;

#[Name('Department'), Route('/{title}')]
final class Department implements Title
{
    public function __construct(
        protected readonly Request $request,
        protected readonly Cms $cms,
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

### Derived behavior

| Signal | Behavior |
| ------ | -------- |
| `#[Route('...')]` is present | Node is routable and has URL path settings |
| `#[Render('...')]` is present | Explicit renderer id is used |
| `#[Render]` is absent | Node handle is used as renderer id |

### Metadata attributes

| Attribute | Purpose |
| --------- | ------- |
| `#[Name('...')]` | Human-readable display name |
| `#[Handle('...')]` | URL-safe identifier (auto-derived if omitted) |
| `#[Route('...')]` | URL pattern for routable nodes |
| `#[Render('...')]` | Template name override |
| `#[Title('...')]` | Field name to use as title |
| `#[FieldOrder('...')]` | Admin panel field order |
| `#[Deletable(false)]` | Prevent deletion in admin panel (default: `true`) |

### Behavioral interfaces

| Interface | Method | Purpose |
| --------- | ------ | ------- |
| `Title` | `title(): string` | Computed title (takes precedence over `#[Title]`) |
| `HasInit` | `init(): void` | Post-hydration initialization hook |
| `HandlesFormPost` | `formPost(?array $body): Response` | Frontend form submission handling |
| `ProvidesRenderContext` | `renderContext(): array` | Extra template variables |

### Rendering by uid

Render a node by uid from templates with the neutral cms API:

```php
<?= $cms->render('some-node-uid') ?>
```

## Settings

```text
'session.authcookie' => '<app>_auth', // Name of the auth cookie
'session.expires' => 60 * 60 * 24,    // One day by default
```

### Admin panel theming

You can style the admin panel through `panel.theme` in your CMS config.
Set it to a single stylesheet path (`string`) or multiple stylesheet paths (`string[]`).
The panel links those CSS files and uses your overrides for `--cms-*` tokens.

```php
return [
	'panel.theme' => [
		'/assets/cms/theme/base.css',
		'/assets/cms/theme/brand.css',
	],
];
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
