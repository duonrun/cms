import { browser } from '$app/environment';

export type CmsTheme = Record<`--cms-${string}`, string>;

export function filterCmsTheme(theme: unknown): CmsTheme {
	if (!theme || typeof theme !== 'object') {
		return {};
	}

	const vars: CmsTheme = {};

	for (const [key, value] of Object.entries(theme)) {
		if (!key.startsWith('--cms-') || typeof value !== 'string') {
			continue;
		}

		vars[key as `--cms-${string}`] = value;
	}

	return vars;
}

export function applyCmsTheme(theme: CmsTheme): void {
	if (!browser) {
		return;
	}

	for (const [key, value] of Object.entries(theme)) {
		document.documentElement.style.setProperty(key, value);
	}
}
