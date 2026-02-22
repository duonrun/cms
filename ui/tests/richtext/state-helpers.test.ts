import { describe, test, expect } from 'vitest';
import { EditorState, TextSelection } from 'prosemirror-state';
import { schema, parser } from '$shell/richtext/schema';
import {
	isMarkActive,
	isNodeActive,
	getBlockAttributes,
	getActiveTextAlign,
	getMarkAttributes,
} from '$shell/richtext/state-helpers';

function createState(html: string, cursorPos?: number): EditorState {
	const div = document.createElement('div');
	div.innerHTML = html;
	const doc = parser.parse(div);
	const state = EditorState.create({ doc, schema });
	if (cursorPos !== undefined) {
		return state.apply(state.tr.setSelection(TextSelection.create(state.doc, cursorPos)));
	}
	return state;
}

describe('isMarkActive', () => {
	test('returns true when cursor is inside bold text', () => {
		// <p><strong>hello</strong></p> -> positions: 0=before doc, 1=before p, 2=before "hello", 3=after "h"
		const state = createState('<p><strong>hello</strong></p>', 3);
		expect(isMarkActive(state, schema.marks.bold)).toBe(true);
	});

	test('returns false when cursor is outside bold text', () => {
		const state = createState('<p>plain <strong>bold</strong></p>', 3);
		expect(isMarkActive(state, schema.marks.bold)).toBe(false);
	});

	test('returns true for italic at cursor inside italic text', () => {
		const state = createState('<p><em>italic</em></p>', 3);
		expect(isMarkActive(state, schema.marks.italic)).toBe(true);
	});

	test('returns false for italic when cursor is in bold text', () => {
		const state = createState('<p><strong>bold</strong></p>', 3);
		expect(isMarkActive(state, schema.marks.italic)).toBe(false);
	});

	test('returns true for link at cursor inside link', () => {
		const state = createState(
			'<p><a href="/test" rel="noopener noreferrer nofollow">link</a></p>',
			3,
		);
		expect(isMarkActive(state, schema.marks.link)).toBe(true);
	});

	test('returns true for range selection within bold text', () => {
		const state = createState('<p><strong>hello</strong></p>');
		// Select "ell" within "hello" (positions 3-6 in the doc)
		const rangeState = state.apply(
			state.tr.setSelection(TextSelection.create(state.doc, 3, 6)),
		);
		expect(isMarkActive(rangeState, schema.marks.bold)).toBe(true);
	});
});

describe('getMarkAttributes', () => {
	test('returns link href and target', () => {
		const state = createState(
			'<p><a href="/test" target="_blank" rel="noopener noreferrer nofollow">link</a></p>',
			3,
		);
		const attrs = getMarkAttributes(state, schema.marks.link);
		expect(attrs).not.toBeNull();
		expect(attrs!.href).toBe('/test');
		expect(attrs!.target).toBe('_blank');
	});

	test('returns null when mark is not active', () => {
		const state = createState('<p>plain text</p>', 3);
		expect(getMarkAttributes(state, schema.marks.link)).toBeNull();
	});

	test('returns attributes from range selection', () => {
		const state = createState(
			'<p><a href="/page" rel="noopener noreferrer nofollow">link text</a></p>',
		);
		const rangeState = state.apply(
			state.tr.setSelection(TextSelection.create(state.doc, 2, 6)),
		);
		const attrs = getMarkAttributes(rangeState, schema.marks.link);
		expect(attrs).not.toBeNull();
		expect(attrs!.href).toBe('/page');
	});
});

describe('isNodeActive', () => {
	test('detects heading with correct level', () => {
		const state = createState('<h2>Title</h2>', 3);
		expect(isNodeActive(state, schema.nodes.heading, { level: 2 })).toBe(true);
	});

	test('returns false for non-matching heading level', () => {
		const state = createState('<h2>Title</h2>', 3);
		expect(isNodeActive(state, schema.nodes.heading, { level: 1 })).toBe(false);
	});

	test('detects heading without checking attributes', () => {
		const state = createState('<h3>Section</h3>', 3);
		expect(isNodeActive(state, schema.nodes.heading)).toBe(true);
	});

	test('detects paragraph', () => {
		const state = createState('<p>text</p>', 3);
		expect(isNodeActive(state, schema.nodes.paragraph)).toBe(true);
	});

	test('detects paragraph with class attribute', () => {
		const state = createState('<p class="large">big text</p>', 3);
		expect(isNodeActive(state, schema.nodes.paragraph, { class: 'large' })).toBe(true);
	});

	test('returns false for non-matching class', () => {
		const state = createState('<p class="large">big text</p>', 3);
		expect(isNodeActive(state, schema.nodes.paragraph, { class: 'small' })).toBe(false);
	});

	test('detects blockquote', () => {
		const state = createState('<blockquote><p>quote</p></blockquote>', 3);
		expect(isNodeActive(state, schema.nodes.blockquote)).toBe(true);
	});

	test('returns false when not inside matching node', () => {
		const state = createState('<p>text</p>', 3);
		expect(isNodeActive(state, schema.nodes.heading)).toBe(false);
	});
});

describe('getBlockAttributes', () => {
	test('returns paragraph attributes', () => {
		const state = createState('<p class="large">text</p>', 3);
		const attrs = getBlockAttributes(state, schema.nodes.paragraph);
		expect(attrs).not.toBeNull();
		expect(attrs!.class).toBe('large');
	});

	test('returns heading attributes', () => {
		const state = createState('<h2>Title</h2>', 3);
		const attrs = getBlockAttributes(state, schema.nodes.heading);
		expect(attrs).not.toBeNull();
		expect(attrs!.level).toBe(2);
	});

	test('returns null for non-matching node type', () => {
		const state = createState('<p>text</p>', 3);
		expect(getBlockAttributes(state, schema.nodes.heading)).toBeNull();
	});
});

describe('getActiveTextAlign', () => {
	test('returns alignment value', () => {
		const state = createState('<p style="text-align: center">centered</p>', 3);
		expect(getActiveTextAlign(state)).toBe('center');
	});

	test('returns right alignment', () => {
		const state = createState('<p style="text-align: right">right</p>', 3);
		expect(getActiveTextAlign(state)).toBe('right');
	});

	test('returns null for default alignment', () => {
		const state = createState('<p>normal</p>', 3);
		expect(getActiveTextAlign(state)).toBeNull();
	});

	test('returns alignment from heading', () => {
		const state = createState('<h1 style="text-align: center">centered heading</h1>', 3);
		expect(getActiveTextAlign(state)).toBe('center');
	});
});
