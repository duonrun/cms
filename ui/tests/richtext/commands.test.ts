import { describe, test, expect } from 'vitest';
import { EditorState, TextSelection } from 'prosemirror-state';
import type { Node as PmNode } from 'prosemirror-model';
import { schema, parser, serializer } from '$shell/richtext/schema';
import {
	setTextAlign,
	unsetTextAlign,
	setParagraphClass,
	insertHorizontalRule,
	setLink,
	unsetLink,
	clearMarks,
	clearNodes,
	insertHardBreak,
	setHeading,
	setParagraph,
	toggleBold,
	toggleItalic,
	toggleStrike,
	toggleBlockquote,
} from '$shell/richtext/commands';

function parseHTML(html: string): PmNode {
	const div = document.createElement('div');
	div.innerHTML = html;
	return parser.parse(div);
}

function serializeHTML(doc: PmNode): string {
	const fragment = serializer.serializeFragment(doc.content);
	const div = document.createElement('div');
	div.appendChild(fragment);
	return div.innerHTML;
}

function createState(html: string, from?: number, to?: number): EditorState {
	const doc = parseHTML(html);
	const state = EditorState.create({ doc, schema });
	if (from !== undefined) {
		return state.apply(
			state.tr.setSelection(TextSelection.create(state.doc, from, to ?? from)),
		);
	}
	return state;
}

function applyCommand(
	state: EditorState,
	command: (state: EditorState, dispatch?: (tr: any) => void) => boolean,
): EditorState {
	let newState = state;
	command(state, tr => {
		newState = state.apply(tr);
	});
	return newState;
}

describe('setTextAlign', () => {
	test('sets center alignment on paragraph', () => {
		const state = createState('<p>hello</p>', 3);
		const result = applyCommand(state, setTextAlign('center'));
		expect(serializeHTML(result.doc)).toBe('<p style="text-align: center;">hello</p>');
	});

	test('sets alignment on heading', () => {
		const state = createState('<h2>title</h2>', 3);
		const result = applyCommand(state, setTextAlign('right'));
		expect(serializeHTML(result.doc)).toBe('<h2 style="text-align: right;">title</h2>');
	});
});

describe('unsetTextAlign', () => {
	test('removes alignment from paragraph', () => {
		const state = createState('<p style="text-align: center">centered</p>', 3);
		const result = applyCommand(state, unsetTextAlign());
		expect(serializeHTML(result.doc)).toBe('<p>centered</p>');
	});
});

describe('setParagraphClass', () => {
	test('sets cms text class on paragraph', () => {
		const state = createState('<p>text</p>', 3);
		const result = applyCommand(state, setParagraphClass('cms-text-lg'));
		expect(serializeHTML(result.doc)).toBe('<p class="cms-text-lg">text</p>');
	});

	test('converts heading to paragraph with class', () => {
		const state = createState('<h2>title</h2>', 3);
		const result = applyCommand(state, setParagraphClass('cms-text-2xl'));
		expect(serializeHTML(result.doc)).toBe('<p class="cms-text-2xl">title</p>');
	});
});

describe('insertHorizontalRule', () => {
	test('inserts HR after current block', () => {
		const state = createState('<p>hello</p>', 3);
		const result = applyCommand(state, insertHorizontalRule());
		expect(serializeHTML(result.doc)).toBe('<p>hello</p><hr>');
	});
});

describe('setLink', () => {
	test('applies link mark to selection', () => {
		// "hello" is at positions 1-6 in the doc
		const state = createState('<p>hello</p>', 1, 6);
		const result = applyCommand(state, setLink({ href: '/page', target: '_blank' }));
		expect(serializeHTML(result.doc)).toBe(
			'<p><a href="/page" target="_blank" rel="noopener noreferrer nofollow">hello</a></p>',
		);
	});

	test('returns false with empty selection', () => {
		const state = createState('<p>hello</p>', 3);
		let dispatched = false;
		const result = setLink({ href: '/page' })(state, () => {
			dispatched = true;
		});
		expect(result).toBe(false);
		expect(dispatched).toBe(false);
	});
});

describe('unsetLink', () => {
	test('removes link from selection', () => {
		const state = createState(
			'<p><a href="/page" rel="noopener noreferrer nofollow">hello</a></p>',
			1,
			6,
		);
		const result = applyCommand(state, unsetLink());
		expect(serializeHTML(result.doc)).toBe('<p>hello</p>');
	});

	test('removes link at cursor position (empty selection)', () => {
		const state = createState(
			'<p><a href="/page" rel="noopener noreferrer nofollow">hello</a></p>',
			3,
		);
		const result = applyCommand(state, unsetLink());
		expect(serializeHTML(result.doc)).toBe('<p>hello</p>');
	});
});

describe('clearMarks', () => {
	test('removes all inline marks from selection', () => {
		const state = createState('<p><strong><em>formatted</em></strong></p>', 1, 10);
		const result = applyCommand(state, clearMarks());
		expect(serializeHTML(result.doc)).toBe('<p>formatted</p>');
	});

	test('returns false with empty selection', () => {
		const state = createState('<p><strong>bold</strong></p>', 3);
		const result = clearMarks()(state);
		expect(result).toBe(false);
	});
});

describe('clearNodes', () => {
	test('converts heading to paragraph', () => {
		const state = createState('<h2>title</h2>', 3);
		const result = applyCommand(state, clearNodes());
		expect(serializeHTML(result.doc)).toBe('<p>title</p>');
	});
});

describe('insertHardBreak', () => {
	test('inserts br at cursor', () => {
		// "helloworld" -> position 6 is between "hello" and "world"
		const state = createState('<p>helloworld</p>', 6);
		const result = applyCommand(state, insertHardBreak());
		expect(serializeHTML(result.doc)).toBe('<p>hello<br>world</p>');
	});
});

describe('setHeading', () => {
	test('converts paragraph to heading', () => {
		const state = createState('<p>text</p>', 3);
		const result = applyCommand(state, setHeading(2));
		expect(serializeHTML(result.doc)).toBe('<h2>text</h2>');
	});

	test('toggles heading back to paragraph if same level', () => {
		const state = createState('<h2>text</h2>', 3);
		const result = applyCommand(state, setHeading(2));
		expect(serializeHTML(result.doc)).toBe('<p>text</p>');
	});

	test('changes heading level', () => {
		const state = createState('<h2>text</h2>', 3);
		const result = applyCommand(state, setHeading(1));
		expect(serializeHTML(result.doc)).toBe('<h1>text</h1>');
	});

	test('preserves text-align when changing to heading', () => {
		const state = createState('<p style="text-align: center">text</p>', 3);
		const result = applyCommand(state, setHeading(1));
		expect(serializeHTML(result.doc)).toBe('<h1 style="text-align: center;">text</h1>');
	});
});

describe('setParagraph', () => {
	test('converts heading to paragraph', () => {
		const state = createState('<h2>text</h2>', 3);
		const result = applyCommand(state, setParagraph());
		expect(serializeHTML(result.doc)).toBe('<p>text</p>');
	});

	test('preserves text-align when converting', () => {
		const state = createState('<h2 style="text-align: center">text</h2>', 3);
		const result = applyCommand(state, setParagraph());
		expect(serializeHTML(result.doc)).toBe('<p style="text-align: center;">text</p>');
	});

	test('does not change existing paragraph', () => {
		const state = createState('<p>text</p>', 3);
		const result = applyCommand(state, setParagraph());
		expect(serializeHTML(result.doc)).toBe('<p>text</p>');
	});
});

describe('toggleBold', () => {
	test('adds bold to selection', () => {
		const state = createState('<p>hello</p>', 1, 6);
		const result = applyCommand(state, toggleBold());
		expect(serializeHTML(result.doc)).toBe('<p><strong>hello</strong></p>');
	});
});

describe('toggleItalic', () => {
	test('adds italic to selection', () => {
		const state = createState('<p>hello</p>', 1, 6);
		const result = applyCommand(state, toggleItalic());
		expect(serializeHTML(result.doc)).toBe('<p><em>hello</em></p>');
	});
});

describe('toggleStrike', () => {
	test('adds strikethrough to selection', () => {
		const state = createState('<p>hello</p>', 1, 6);
		const result = applyCommand(state, toggleStrike());
		expect(serializeHTML(result.doc)).toBe('<p><s>hello</s></p>');
	});
});

describe('toggleBlockquote', () => {
	test('wraps paragraph in blockquote', () => {
		const state = createState('<p>quote me</p>', 3);
		const result = applyCommand(state, toggleBlockquote());
		expect(serializeHTML(result.doc)).toBe('<blockquote><p>quote me</p></blockquote>');
	});

	test('lifts paragraph out of blockquote', () => {
		const state = createState('<blockquote><p>quote me</p></blockquote>', 3);
		const result = applyCommand(state, toggleBlockquote());
		expect(serializeHTML(result.doc)).toBe('<p>quote me</p>');
	});
});
