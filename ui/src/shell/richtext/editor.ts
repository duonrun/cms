import { Editor } from '@tiptap/core';
import type { EditorOptions } from '@tiptap/core';
import { readable, type Readable } from 'svelte/store';

const createEditor = (
	options: Partial<EditorOptions>,
	callback: (ed: Editor) => void,
): Readable<Editor> => {
	const editor = new Editor(options);

	return readable(editor, set => {
		editor.on('transaction', () => {
			callback(editor);
			set(editor);
		});

		return () => {
			editor.destroy();
		};
	});
};

export default createEditor;
