<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

final readonly class QueryState
{
	public function __construct(
		public SqlFragment $filters,
		public SqlFragment $types,
		public string $order = '',
		public ?int $limit = null,
		public ?int $offset = null,
		public ?bool $deleted = false,
		public ?bool $published = true,
		public ?bool $hidden = false,
	) {}

	public static function defaults(): self
	{
		return new self(
			SqlFragment::empty(),
			SqlFragment::empty(),
		);
	}

	public function withFilters(SqlFragment $filters): self
	{
		return new self(
			$filters,
			$this->types,
			$this->order,
			$this->limit,
			$this->offset,
			$this->deleted,
			$this->published,
			$this->hidden,
		);
	}

	public function withTypes(SqlFragment $types): self
	{
		return new self(
			$this->filters,
			$types,
			$this->order,
			$this->limit,
			$this->offset,
			$this->deleted,
			$this->published,
			$this->hidden,
		);
	}

	public function withOrder(string $order): self
	{
		return new self(
			$this->filters,
			$this->types,
			$order,
			$this->limit,
			$this->offset,
			$this->deleted,
			$this->published,
			$this->hidden,
		);
	}

	public function withLimit(?int $limit): self
	{
		return new self(
			$this->filters,
			$this->types,
			$this->order,
			$limit,
			$this->offset,
			$this->deleted,
			$this->published,
			$this->hidden,
		);
	}

	public function withOffset(?int $offset): self
	{
		return new self(
			$this->filters,
			$this->types,
			$this->order,
			$this->limit,
			$offset,
			$this->deleted,
			$this->published,
			$this->hidden,
		);
	}

	public function withDeleted(?bool $deleted): self
	{
		return new self(
			$this->filters,
			$this->types,
			$this->order,
			$this->limit,
			$this->offset,
			$deleted,
			$this->published,
			$this->hidden,
		);
	}

	public function withPublished(?bool $published): self
	{
		return new self(
			$this->filters,
			$this->types,
			$this->order,
			$this->limit,
			$this->offset,
			$this->deleted,
			$published,
			$this->hidden,
		);
	}

	public function withHidden(?bool $hidden): self
	{
		return new self(
			$this->filters,
			$this->types,
			$this->order,
			$this->limit,
			$this->offset,
			$this->deleted,
			$this->published,
			$hidden,
		);
	}

	public function condition(): SqlFragment
	{
		return $this->filters->and($this->types);
	}

	/** @return array<string, scalar|null> */
	public function baseParams(): array
	{
		$condition = $this->condition();
		$params = ['condition' => $condition->sql];

		if (is_bool($this->deleted)) {
			$params['deleted'] = $this->deleted;
		}

		if (is_bool($this->published)) {
			$params['published'] = $this->published;
		}

		if (is_bool($this->hidden)) {
			$params['hidden'] = $this->hidden;
		}

		return [...$params, ...$condition->params];
	}

	/** @return array<string, scalar|null> */
	public function findParams(): array
	{
		$params = $this->baseParams();

		if ($this->order !== '') {
			$params['order'] = $this->order;
		}

		if ($this->limit !== null) {
			$params['limit'] = $this->limit;
		}

		if ($this->offset !== null) {
			$params['offset'] = $this->offset;
		}

		return $params;
	}
}
