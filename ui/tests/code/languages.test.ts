import { describe, expect, test } from 'vitest';
import {
	CODE_SYNTAXES,
	DEFAULT_CODE_SYNTAX,
	loadCodeLanguageExtension,
	normalizeCodeSyntax,
} from '$shell/code/languages';

describe('code language syntax normalization', () => {
	test('normalizes known syntax names', () => {
		expect(normalizeCodeSyntax(' php ')).toBe('php');
		expect(normalizeCodeSyntax('TypeScript')).toBe('typescript');
		expect(normalizeCodeSyntax('JSON')).toBe('json');
	});

	test('normalizes aliases to canonical syntax names', () => {
		expect(normalizeCodeSyntax('js')).toBe('javascript');
		expect(normalizeCodeSyntax('ts')).toBe('typescript');
		expect(normalizeCodeSyntax('md')).toBe('markdown');
		expect(normalizeCodeSyntax('yml')).toBe('yaml');
		expect(normalizeCodeSyntax('sh')).toBe('bash');
		expect(normalizeCodeSyntax('plain')).toBe('plaintext');
		expect(normalizeCodeSyntax('text')).toBe('plaintext');
	});

	test('falls back to default syntax for unknown or empty values', () => {
		expect(normalizeCodeSyntax('')).toBe(DEFAULT_CODE_SYNTAX);
		expect(normalizeCodeSyntax('ruby')).toBe(DEFAULT_CODE_SYNTAX);
		expect(normalizeCodeSyntax(undefined)).toBe(DEFAULT_CODE_SYNTAX);
		expect(normalizeCodeSyntax(null)).toBe(DEFAULT_CODE_SYNTAX);
	});

	test('supports the configured syntax list', () => {
		expect(CODE_SYNTAXES).toEqual([
			'plaintext',
			'php',
			'javascript',
			'typescript',
			'html',
			'css',
			'json',
			'markdown',
			'sql',
			'yaml',
			'xml',
			'bash',
		]);
	});
});

describe('code language extension loading', () => {
	test('loads a no-op extension for plaintext', async () => {
		const extension = await loadCodeLanguageExtension('plaintext');

		expect(extension).toEqual([]);
	});

	test('loads fallback extension for unsupported syntaxes', async () => {
		const extension = await loadCodeLanguageExtension('unknown-language');

		expect(extension).toEqual([]);
	});

	test('loads configured language extensions without throwing', async () => {
		for (const syntax of CODE_SYNTAXES.filter(value => value !== 'plaintext')) {
			const extension = await loadCodeLanguageExtension(syntax);

			expect(extension).toBeDefined();
		}
	});
});
