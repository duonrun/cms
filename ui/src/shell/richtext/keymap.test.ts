import { describe, test, expect } from 'vitest';
import { EditorState } from 'prosemirror-state';
import { schema } from './schema';
import { buildKeymap, buildInputRules } from './keymap';

describe('buildKeymap', () => {
	test('returns a valid plugin', () => {
		const plugin = buildKeymap();
		expect(plugin).toBeDefined();
		expect(plugin.spec).toBeDefined();
	});

	test('plugin can be added to editor state', () => {
		const state = EditorState.create({
			schema,
			plugins: [buildKeymap()],
		});
		expect(state.plugins.length).toBeGreaterThan(0);
	});
});

describe('buildInputRules', () => {
	test('returns a valid plugin', () => {
		const plugin = buildInputRules();
		expect(plugin).toBeDefined();
		expect(plugin.spec).toBeDefined();
	});

	test('plugin can be added to editor state', () => {
		const state = EditorState.create({
			schema,
			plugins: [buildInputRules()],
		});
		expect(state.plugins.length).toBeGreaterThan(0);
	});

	test('both plugins can coexist', () => {
		const state = EditorState.create({
			schema,
			plugins: [buildKeymap(), buildInputRules()],
		});
		expect(state.plugins.length).toBe(2);
	});
});
