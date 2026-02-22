import { describe, test, expect } from 'vitest';
import type { Node as PmNode } from 'prosemirror-model';
import { schema, parser, serializer } from './schema';

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

function roundTrip(html: string): string {
	return serializeHTML(parseHTML(html));
}

describe('schema', () => {
	test('schema has expected node types', () => {
		const nodeNames = Object.keys(schema.nodes);
		expect(nodeNames).toContain('doc');
		expect(nodeNames).toContain('paragraph');
		expect(nodeNames).toContain('heading');
		expect(nodeNames).toContain('bulletList');
		expect(nodeNames).toContain('orderedList');
		expect(nodeNames).toContain('listItem');
		expect(nodeNames).toContain('blockquote');
		expect(nodeNames).toContain('codeBlock');
		expect(nodeNames).toContain('horizontalRule');
		expect(nodeNames).toContain('hardBreak');
		expect(nodeNames).toContain('text');
	});

	test('schema has expected mark types', () => {
		const markNames = Object.keys(schema.marks);
		expect(markNames).toContain('bold');
		expect(markNames).toContain('italic');
		expect(markNames).toContain('underline');
		expect(markNames).toContain('strike');
		expect(markNames).toContain('code');
		expect(markNames).toContain('link');
		expect(markNames).toContain('subscript');
		expect(markNames).toContain('superscript');
	});
});

describe('paragraph round-trip', () => {
	test('plain paragraph', () => {
		expect(roundTrip('<p>Hello world</p>')).toBe('<p>Hello world</p>');
	});

	test('paragraph with class="large"', () => {
		expect(roundTrip('<p class="large">Big text</p>')).toBe('<p class="large">Big text</p>');
	});

	test('paragraph with class="small"', () => {
		expect(roundTrip('<p class="small">Small text</p>')).toBe(
			'<p class="small">Small text</p>',
		);
	});

	test('paragraph with class="default" normalizes to no class', () => {
		expect(roundTrip('<p class="default">Normal</p>')).toBe('<p>Normal</p>');
	});

	test('paragraph with text-align center', () => {
		expect(roundTrip('<p style="text-align: center">Centered</p>')).toBe(
			'<p style="text-align: center;">Centered</p>',
		);
	});

	test('paragraph with text-align right', () => {
		expect(roundTrip('<p style="text-align: right">Right</p>')).toBe(
			'<p style="text-align: right;">Right</p>',
		);
	});

	test('paragraph with text-align justify', () => {
		expect(roundTrip('<p style="text-align: justify">Justified</p>')).toBe(
			'<p style="text-align: justify;">Justified</p>',
		);
	});

	test('paragraph with class and text-align combined', () => {
		expect(roundTrip('<p class="large" style="text-align: center">Large centered</p>')).toBe(
			'<p class="large" style="text-align: center;">Large centered</p>',
		);
	});

	test('empty paragraph', () => {
		expect(roundTrip('<p></p>')).toBe('<p></p>');
	});
});

describe('heading round-trip', () => {
	test('heading level 1', () => {
		expect(roundTrip('<h1>Title</h1>')).toBe('<h1>Title</h1>');
	});

	test('heading level 2', () => {
		expect(roundTrip('<h2>Subtitle</h2>')).toBe('<h2>Subtitle</h2>');
	});

	test('heading level 3', () => {
		expect(roundTrip('<h3>Section</h3>')).toBe('<h3>Section</h3>');
	});

	test('heading with text-align center', () => {
		expect(roundTrip('<h2 style="text-align: center">Centered</h2>')).toBe(
			'<h2 style="text-align: center;">Centered</h2>',
		);
	});

	test('heading with inline marks', () => {
		expect(roundTrip('<h1><strong>Bold Title</strong></h1>')).toBe(
			'<h1><strong>Bold Title</strong></h1>',
		);
	});
});

describe('list round-trip', () => {
	test('bullet list', () => {
		const html = '<ul><li><p>Item 1</p></li><li><p>Item 2</p></li></ul>';
		expect(roundTrip(html)).toBe(html);
	});

	test('ordered list', () => {
		const html = '<ol><li><p>First</p></li><li><p>Second</p></li></ol>';
		expect(roundTrip(html)).toBe(html);
	});

	test('ordered list with start attribute', () => {
		const html = '<ol start="5"><li><p>Fifth</p></li></ol>';
		expect(roundTrip(html)).toBe(html);
	});

	test('nested list', () => {
		const html = '<ul><li><p>Parent</p><ul><li><p>Child</p></li></ul></li></ul>';
		expect(roundTrip(html)).toBe(html);
	});
});

describe('blockquote round-trip', () => {
	test('simple blockquote', () => {
		const html = '<blockquote><p>Quote text</p></blockquote>';
		expect(roundTrip(html)).toBe(html);
	});

	test('blockquote with multiple paragraphs', () => {
		const html = '<blockquote><p>First</p><p>Second</p></blockquote>';
		expect(roundTrip(html)).toBe(html);
	});
});

describe('horizontal rule round-trip', () => {
	test('plain horizontal rule', () => {
		const result = roundTrip('<hr>');
		expect(result).toBe('<hr>');
	});

	test('horizontal rule with class', () => {
		const result = roundTrip('<hr class="divider">');
		expect(result).toBe('<hr class="divider">');
	});
});

describe('hard break round-trip', () => {
	test('hard break within paragraph', () => {
		const html = '<p>Line one<br>Line two</p>';
		expect(roundTrip(html)).toBe(html);
	});
});

describe('code block round-trip', () => {
	test('code block with code wrapper', () => {
		const html = '<pre><code>const x = 1;</code></pre>';
		expect(roundTrip(html)).toBe(html);
	});
});

describe('bold mark round-trip', () => {
	test('<strong> round-trips', () => {
		expect(roundTrip('<p><strong>Bold</strong></p>')).toBe('<p><strong>Bold</strong></p>');
	});

	test('<b> is normalized to <strong>', () => {
		expect(roundTrip('<p><b>Bold</b></p>')).toBe('<p><strong>Bold</strong></p>');
	});

	test('bold with surrounding text', () => {
		expect(roundTrip('<p>Hello <strong>World</strong></p>')).toBe(
			'<p>Hello <strong>World</strong></p>',
		);
	});
});

describe('italic mark round-trip', () => {
	test('<em> round-trips', () => {
		expect(roundTrip('<p><em>Italic</em></p>')).toBe('<p><em>Italic</em></p>');
	});

	test('<i> is normalized to <em>', () => {
		expect(roundTrip('<p><i>Italic</i></p>')).toBe('<p><em>Italic</em></p>');
	});
});

describe('strike mark round-trip', () => {
	test('<s> round-trips', () => {
		expect(roundTrip('<p><s>Struck</s></p>')).toBe('<p><s>Struck</s></p>');
	});

	test('<del> is normalized to <s>', () => {
		expect(roundTrip('<p><del>Deleted</del></p>')).toBe('<p><s>Deleted</s></p>');
	});

	test('<strike> is normalized to <s>', () => {
		expect(roundTrip('<p><strike>Old</strike></p>')).toBe('<p><s>Old</s></p>');
	});
});

describe('inline code mark round-trip', () => {
	test('inline code round-trips', () => {
		expect(roundTrip('<p><code>code</code></p>')).toBe('<p><code>code</code></p>');
	});
});

describe('subscript mark round-trip', () => {
	test('subscript round-trips', () => {
		expect(roundTrip('<p>H<sub>2</sub>O</p>')).toBe('<p>H<sub>2</sub>O</p>');
	});
});

describe('superscript mark round-trip', () => {
	test('superscript round-trips', () => {
		expect(roundTrip('<p>x<sup>2</sup></p>')).toBe('<p>x<sup>2</sup></p>');
	});
});

describe('underline mark round-trip', () => {
	test('underline round-trips', () => {
		expect(roundTrip('<p><u>Underlined</u></p>')).toBe('<p><u>Underlined</u></p>');
	});
});

describe('link mark round-trip', () => {
	test('link with href and rel', () => {
		const html = '<p><a href="/page" rel="noopener noreferrer nofollow">Link</a></p>';
		expect(roundTrip(html)).toBe(html);
	});

	test('link with target blank', () => {
		const html =
			'<p><a href="/page" target="_blank" rel="noopener noreferrer nofollow">Link</a></p>';
		expect(roundTrip(html)).toBe(html);
	});

	test('link without rel gets default rel added', () => {
		const result = roundTrip('<p><a href="/page">Link</a></p>');
		expect(result).toBe('<p><a href="/page" rel="noopener noreferrer nofollow">Link</a></p>');
	});

	test('link with class', () => {
		const html =
			'<p><a href="/page" class="btn" rel="noopener noreferrer nofollow">Link</a></p>';
		expect(roundTrip(html)).toBe(html);
	});
});

describe('nested marks round-trip', () => {
	test('bold and italic combined', () => {
		const result = roundTrip('<p><strong><em>Bold italic</em></strong></p>');
		expect(result).toBe('<p><strong><em>Bold italic</em></strong></p>');
	});

	test('multiple marks on same text', () => {
		const result = roundTrip('<p><strong><em><s>All three</s></em></strong></p>');
		expect(result).toBe('<p><strong><em><s>All three</s></em></strong></p>');
	});
});

describe('complex document round-trip', () => {
	test('mixed content types', () => {
		const html = [
			'<h1>Title</h1>',
			'<p>A paragraph with <strong>bold</strong> and <em>italic</em> text.</p>',
			'<ul><li><p>Item one</p></li><li><p>Item two</p></li></ul>',
			'<blockquote><p>A quote</p></blockquote>',
			'<hr>',
			'<p>Final paragraph</p>',
		].join('');
		expect(roundTrip(html)).toBe(html);
	});

	test('production-like content from test fixtures', () => {
		expect(roundTrip('<p>Willkommen auf der Testseite</p>')).toBe(
			'<p>Willkommen auf der Testseite</p>',
		);
	});

	test('production-like content with bold', () => {
		expect(roundTrip('<p>Hello <strong>World</strong></p>')).toBe(
			'<p>Hello <strong>World</strong></p>',
		);
	});
});
