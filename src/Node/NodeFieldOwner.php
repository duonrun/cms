<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Config;
use Duon\Cms\Context;
use Duon\Cms\Field\Owner;
use Duon\Cms\Locale;
use Duon\Cms\Locales;
use Duon\Core\Request;

class NodeFieldOwner implements Owner
{
	public function __construct(
		private readonly Context $context,
		private readonly string $nodeUid,
	) {}

	public function uid(): string
	{
		return $this->nodeUid;
	}

	public function locale(): Locale
	{
		return $this->context->locale();
	}

	public function defaultLocale(): Locale
	{
		return $this->context->request->get('defaultLocale');
	}

	public function locales(): Locales
	{
		return $this->context->locales();
	}

	public function request(): Request
	{
		return $this->context->request;
	}

	public function config(): Config
	{
		return $this->context->config;
	}
}
