import { readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const scanRoots = [
	'src',
	'vite.config.ts',
	'postcss.config.js',
	'svelte.config.js',
	'package.json',
	'.prettierrc',
];

const bannedPatterns = [
	/@import\s+['"]tailwindcss['"]/,
	/@plugin\s+['"]@tailwindcss\//,
	/@source\s+inline\(/,
	/@apply\s+/,
	/@tailwindcss\/vite/,
	/prettier-plugin-tailwindcss/,
	/"tailwindcss"\s*:/,
	/"@tailwindcss\//,
];

const allowedExtensions = new Set(['.css', '.svelte', '.ts', '.js', '.json', '.mjs', '.cjs']);

const excludedDirectories = new Set(['node_modules', 'build', '.svelte-kit']);

function walk(relativePath) {
	const absolutePath = path.join(root, relativePath);
	const entries = readdirSync(absolutePath, { withFileTypes: true });
	const files = [];

	for (const entry of entries) {
		if (entry.isDirectory()) {
			if (excludedDirectories.has(entry.name)) {
				continue;
			}

			files.push(...walk(path.join(relativePath, entry.name)));
			continue;
		}

		if (!allowedExtensions.has(path.extname(entry.name))) {
			continue;
		}

		files.push(path.join(relativePath, entry.name));
	}

	return files;
}

const filesToScan = scanRoots.flatMap(scanRoot => {
	const absolutePath = path.join(root, scanRoot);

	try {
		const stat = readdirSync(absolutePath, { withFileTypes: true });
		if (stat) {
			return walk(scanRoot);
		}
	} catch {
		return [scanRoot];
	}

	return [scanRoot];
});

const violations = [];

for (const file of filesToScan) {
	let content = '';

	try {
		content = readFileSync(path.join(root, file), 'utf8');
	} catch {
		continue;
	}

	const lines = content.split('\n');

	for (let index = 0; index < lines.length; index += 1) {
		const line = lines[index];

		for (const pattern of bannedPatterns) {
			if (!pattern.test(line)) {
				continue;
			}

			violations.push(`${file}:${index + 1}: ${line.trim()}`);
		}
	}
}

if (violations.length > 0) {
	console.error('Tailwind usage is forbidden in this package.');
	console.error(violations.join('\n'));
	process.exit(1);
}

console.log('No Tailwind directives or dependencies found.');
