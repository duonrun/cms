<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Node\Meta\Block as BlockAttr;
use Duon\Cms\Node\Meta\Deletable;
use Duon\Cms\Node\Meta\Document as DocumentAttr;
use Duon\Cms\Node\Meta\FieldOrder;
use Duon\Cms\Node\Meta\Handle;
use Duon\Cms\Node\Meta\Name;
use Duon\Cms\Node\Meta\Page as PageAttr;
use Duon\Cms\Node\Meta\Permission;
use Duon\Cms\Node\Meta\Render;
use Duon\Cms\Node\Meta\Route;
use Duon\Cms\Node\Meta\Title;
use ReflectionClass;

class Meta
{
	public readonly string $name; // The public name of the node type
	public readonly string $handle; // Used also as slug to address the node type in the panel
	public readonly string $renderer;
	public readonly string|array $route;
	public readonly string|array $permission;
	public readonly string $kind; // 'page', 'block', or 'document'
	public readonly bool $hasKindAttribute; // true if kind was resolved from an attribute
	public readonly ?string $titleField; // Field name from #[Title], or null
	/** @var string[]|null */
	public readonly ?array $fieldOrder; // From #[FieldOrder], or null for declaration order
	public readonly bool $deletable;

	/**
	 * @param class-string $nodeclass
	 */
	public function __construct(private readonly string $nodeClass)
	{
		$attributes = $this->initAttributes();
		$this->name = $this->getName($attributes[Name::class] ?? null);
		$this->handle = $this->getHandle($attributes[Handle::class] ?? null);
		$this->renderer = $this->getRenderer($attributes[Render::class] ?? null, $attributes[Handle::class] ?? null);
		$this->route = $this->getRoute($attributes[Route::class] ?? null);
		$this->permission = $this->getPermission($attributes[Permission::class] ?? null);
		[$this->kind, $this->hasKindAttribute] = $this->resolveKind($attributes);
		$this->titleField = ($attributes[Title::class] ?? null)?->field;
		$this->fieldOrder = ($attributes[FieldOrder::class] ?? null)?->fields;
		$this->deletable = ($attributes[Deletable::class] ?? null)?->value ?? true;
	}

	private function initAttributes(): array
	{
		$reflection = new ReflectionClass($this->nodeClass);
		$attributes = $reflection->getAttributes();
		$map = [];

		foreach ($attributes as $attribute) {
			$instance = $attribute->newInstance();
			$map[$instance::class] = $instance;
		}

		return $map;
	}

	private function getName(?Name $name): string
	{
		if ($name) {
			return $name->value;
		}

		return $this->getClassName();
	}

	private function getHandle(?Handle $handle): string
	{
		if ($handle) {
			return $handle->value;
		}

		return ltrim(
			strtolower(preg_replace(
				'/[A-Z]([A-Z](?![a-z]))*/',
				'-$0',
				$this->getClassName(),
			)),
			'-',
		);
	}

	private function getRenderer(?Render $render, ?Handle $handle): string
	{
		if ($render) {
			return $render->value;
		}

		return $this->getHandle($handle);
	}

	private function getRoute(?Route $route): array|string
	{
		if ($route) {
			return $route->value;
		}

		return '';
	}

	private function getPermission(?Permission $permission): array|string
	{
		if ($permission) {
			return $permission->value;
		}

		return [
			'read' => 'everyone',
			'create' => 'authenticated',
			'change' => 'authenticated',
			'deeete' => 'authenticated',
		];
	}

	private function resolveKind(array $attributes): array
	{
		if (isset($attributes[PageAttr::class])) {
			return ['page', true];
		}

		if (isset($attributes[BlockAttr::class])) {
			return ['block', true];
		}

		if (isset($attributes[DocumentAttr::class])) {
			return ['document', true];
		}

		// Fallback: check class hierarchy for backward compatibility
		return [
			match (true) {
				is_a($this->nodeClass, Page::class, true) => 'page',
				is_a($this->nodeClass, Block::class, true) => 'block',
				is_a($this->nodeClass, Document::class, true) => 'document',
				default => 'document',
			},
			false,
		];
	}

	private function getClassName(): string
	{
		return basename(str_replace('\\', '/', $this->nodeClass));
	}
}
