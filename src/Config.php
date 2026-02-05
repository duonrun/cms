<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Core\AddsConfigInterface;
use Duon\Core\ConfigInterface;
use Duon\Core\Exception\ValueError;

class Config implements ConfigInterface
{
	use AddsConfigInterface;

	public function __construct(
		public readonly string $app = 'cms',
		public readonly bool $debug = false,
		public readonly string $env = '',
		array $settings = [],
	) {
		$this->settings = array_merge([
			'path.prefix' => '',
			'path.assets' => '/assets',
			'path.cache' => '/cache',
			'path.panel' => '/cms',
			'path.api' => null,
			'panel.theme' => null,
			'panel.logo' => '/images/logo.png',
			'db.dsn' => null,
			'db.sql' => [],
			'db.migrations' => [],
			'db.print' => false,
			'db.options' => [],
			'db.sqlite.pragmas.foreign_keys' => true,
			'db.sqlite.pragmas.journal_mode' => 'WAL',
			'db.sqlite.pragmas.synchronous' => 'NORMAL',
			'db.sqlite.pragmas.busy_timeout_ms' => 5000,
			'db.sqlite.pragmas.secure_delete' => false,
			'db.sqlite.pragmas.trusted_schema' => false,
			'session.options' => [
				'cookie_httponly' => true,
				'cookie_lifetime' => 0,
				'gc_maxlifetime' => 3600,
			],
			'media.fileserver' => null,
			'upload.mimetypes.file' => [
				'application/pdf' => ['pdf'],
			],
			'upload.mimetypes.image' => [
				'image/gif' => ['gif'],
				'image/jpeg' => ['jpeg', 'jpg', 'jfif'],
				'image/png' => ['png'],
				'image/webp' => ['webp'],
				'image/svg+xml' => ['svg'],
			],
			'upload.mimetypes.video' => [
				'video/mp4' => ['mp4'],
				'video/ogg' => ['ogg'],
			],
			'upload.maxsize' => 10 * 1024 * 1024,
			'slug.transliterate' => [
				'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Å' => 'Aa', 'Ā' => 'A', 'Ă' => 'A', 'Ą' => 'A',
				'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'aa', 'ā' => 'a', 'ă' => 'a', 'ą' => 'a',
				'Æ' => 'AE', 'æ' => 'ae',
				'Ç' => 'C', 'Ć' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Č' => 'C',
				'ç' => 'c', 'ć' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'č' => 'c',
				'Ð' => 'D', 'Ď' => 'D', 'Đ' => 'D',
				'ð' => 'd', 'ď' => 'd', 'đ' => 'd',
				'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ę' => 'E', 'Ě' => 'E',
				'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ę' => 'e', 'ě' => 'e',
				'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G',
				'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g',
				'Ĥ' => 'H', 'Ħ' => 'H',
				'ĥ' => 'h', 'ħ' => 'h',
				'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ĩ' => 'I', 'Ī' => 'I', 'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I',
				'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ĩ' => 'i', 'ī' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i',
				'Ĵ' => 'J',
				'ĵ' => 'j',
				'Ķ' => 'K',
				'ķ' => 'k', 'ĸ' => 'k',
				'Ĺ' => 'L', 'Ļ' => 'L', 'Ľ' => 'L', 'Ŀ' => 'L', 'Ł' => 'L',
				'ĺ' => 'l', 'ļ' => 'l', 'ľ' => 'l', 'ŀ' => 'l', 'ł' => 'l',
				'Ñ' => 'N', 'Ń' => 'N', 'Ņ' => 'N', 'Ň' => 'N', 'Ŋ' => 'N',
				'ñ' => 'n', 'ń' => 'n', 'ņ' => 'n', 'ň' => 'n', 'ŉ' => 'n', 'ŋ' => 'n',
				'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ø' => 'Oe', 'Ō' => 'O', 'Ŏ' => 'O', 'Ő' => 'O',
				'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'oe', 'ō' => 'o', 'ŏ' => 'o', 'ő' => 'o',
				'Œ' => 'OE', 'œ' => 'oe',
				'Ŕ' => 'R', 'Ŗ' => 'R', 'Ř' => 'R',
				'ŕ' => 'r', 'ŗ' => 'r', 'ř' => 'r',
				'Ś' => 'S', 'Ŝ' => 'S', 'Ş' => 'S', 'Š' => 'S',
				'ś' => 's', 'ŝ' => 's', 'ş' => 's', 'š' => 's',
				'Ţ' => 'T', 'Ť' => 'T', 'Ŧ' => 'T',
				'ţ' => 't', 'ť' => 't', 'ŧ' => 't',
				'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ũ' => 'U', 'Ū' => 'U', 'Ŭ' => 'U', 'Ů' => 'U', 'Ű' => 'U', 'Ų' => 'U',
				'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ũ' => 'u', 'ū' => 'u', 'ŭ' => 'u', 'ů' => 'u', 'ű' => 'u', 'ų' => 'u',
				'Ŵ' => 'W',
				'ŵ' => 'w',
				'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y',
				'ý' => 'y', 'ŷ' => 'y', 'ÿ' => 'y',
				'Ź' => 'Z', 'Ż' => 'Z', 'Ž' => 'Z',
				'ź' => 'z', 'ż' => 'z', 'ž' => 'z',
				'Þ' => 'Th', 'þ' => 'th',
				'ß' => 'ss', 'ẞ' => 'SS',
			],
		], $settings);
		$this->validateApp($app);
	}

	public function app(): string
	{
		return $this->app;
	}

	public function debug(): bool
	{
		return $this->debug;
	}

	public function panelPath(): string
	{
		if ($this->env === 'cms-development') {
			return '/cms';
		}

		return $this->settings['path.panel'];
	}

	public function apiPath(): ?string
	{
		return $this->get('path.api', null);
	}

	public function env(): string
	{
		return $this->env;
	}

	protected function validateApp(string $app): void
	{
		if (!preg_match('/^[a-zA-Z0-9_$-]{1,64}$/', $app)) {
			throw new ValueError(
				'The app name must be a nonempty string which consist only of lower case '
					. 'letters and numbers. Its length must not be longer than 32 characters.',
			);
		}
	}

	public function printAll(): void
	{
		error_log(print_r($this->settings, true));
	}
}
