# Changelog

## [0.2.0-beta.1] (Unreleased)

Codename: Plain Objects

### Breaking changes

This release removes the `Node` / `Page` / `Block` / `Document` inheritance
hierarchy and dedicated node kind modeling. Content types are now plain PHP
classes with metadata attributes, and behavior is derived from route/render
conventions.

- **Removed** abstract base classes `Node`, `Page`, `Block`, `Document`.
- **Removed** the `RendersTemplate` trait.
- **Removed** the dead `Fulltext` class.
- **Removed** `#[Page]`, `#[Block]`, `#[Document]` metadata attributes.
- **Changed** routability/rendering semantics to use `#[Route]` and `#[Render]`
  conventions (renderer fallback remains node handle).
- **Changed** finder facade class from `Duon\Cms\Finder\Finder` to
  `Duon\Cms\Cms`.
- **Changed** plugin class from `Duon\Cms\Cms` to `Duon\Cms\Plugin`.
- **Changed** template embedding API from `find->block(...)` to
  `cms->render(...)`.
- **Changed** all Field and Value classes to depend on the `FieldOwner`
  interface instead of the `Node` class.
- **Changed** node type-hints throughout the framework from `Node` to `object`.
- **Changed** the `Node::class` registry tag to `Plugin::NODE_TAG` constant.

### Added

- `#[Name]`, `#[Handle]`, `#[Route]`, `#[Render]`, `#[Title]`, `#[FieldOrder]`,
  `#[Deletable]`, `#[Permission]` attributes for node metadata.
- `HasTitle`, `HasInit`, `HandlesFormPost`, `ProvidesRenderContext` interfaces
  for behavioral hooks.
- `FieldOwner` interface decoupling fields from the node hierarchy.
- `FieldHydrator` service for external field initialization (two-phase init).
- `NodeFactory` service for creating node instances via `duon/wire` autowiring.
- `NodeSerializer` service for node data serialization, blueprint generation,
  and title resolution.
- `NodeManager` service for node CRUD operations (save, create, delete).
- `PathManager` service for URL path persistence.
- `TemplateRenderer` service for rendering nodes to templates.
- `NodeProxy` for template-friendly access to node fields and methods.
- `NodeMeta` caching facade and `Meta` reflection reader for node metadata.
- `NodeFieldOwner` adapter bridging `FieldOwner` with `Context` and uid.
- `Plugin::NODE_TAG` constant replacing the old `Node::class` registry tag.

### Migration guide

Replace inheritance with attributes and implement interfaces as needed:

```php
// Before
class Article extends Page
{
    public Text $title;

    public function title(): string
    {
        return $this->title?->value()->unwrap() ?? '';
    }
}

// After
#[Name('Article'), Route('/{title}')]
class Article implements HasTitle
{
    #[Label('Title'), Translate]
    public Text $title;

    public function title(): string
    {
        return $this->title?->value()->unwrap() ?? '';
    }
}
```

Constructor dependencies are autowired from the Registry via `duon/wire`:

```php
#[Name('Department'), Route('/{title}')]
final class Department implements HasTitle
{
    public function __construct(
        protected readonly Request $request,
        protected readonly Cms $cms,
    ) {}

    #[Label('Title'), Required, Translate]
    public Text $title;

    public function title(): string
    {
        return $this->title?->value()->unwrap() ?? '';
    }
}
```

## [0.1.0-beta.2](https://github.com/duonrun/cms/releases/tag/0.1.0-beta.2) (2026-02-01)

Codename: Benjamin

- Added support for installing the panel from tagged releases (including alpha/beta/rc), instead of only nightly builds.
- Improved the `install-panel` command output and removed the unnecessary Quma command dependency.
- Updated the panel release workflow to support prerelease tag patterns and manual (retroactive) runs.

## [0.1.0-beta.1](https://github.com/duonrun/cms/releases/tag/0.1.0-beta.1) (2026-02-01)

Initial release - Codename: Sabine
