import type { Field } from '$types/fields';

export interface User {
	uid: string;
	email: string;
	username: string;
	name: string;
	password: string;
	passwordRepeat: string;
}

export interface FileItem {
	file?: string;
	alt?: string | Record<string, string>;
	title?: string | Record<string, string>;
}

export interface TranslatedFile {
	file: string;
	alt?: string;
	title?: string;
}

export interface TextData {
	type: 'text' | 'richtext' | 'hidden' | 'date' | 'time' | 'datetime' | 'option' | 'iframe';
	value?: string | Record<string, string>;
}

export interface NumberData {
	type: 'number';
	value?: number;
}

export interface BooleanData {
	type: 'checkbox';
	value?: boolean;
}

export interface GenericFieldData {
	type: string;
	value?: unknown;
	files?: FileItem[] | Record<string, TranslatedFile[]>;
	columns?: number;
}

export interface FileData {
	type: 'picture' | 'image' | 'video';
	files: FileItem[] | Record<string, TranslatedFile[]>;
}

export interface UploadResponse {
	ok: boolean;
	file: string;
	error: string;
}

export type UploadType = 'image' | 'file' | 'video';

export interface GridBase {
	type: string;
	colspan: number;
	rowspan: number;
	colstart?: number | null;
	width?: number | null; // will be added while rendering the grid
}

export interface GridText extends GridBase {
	type: 'text';
	value: string;
}

export interface GridRichText extends GridBase {
	type: 'richtext';
	value: string;
}

export interface GridIframe extends GridBase {
	type: 'iframe';
	value: string;
}

export interface GridImage extends GridBase {
	type: 'image';
	files: TranslatedFile[];
}

export interface GridVideo extends GridBase {
	type: 'video';
	files: TranslatedFile[];
}

export interface GridYoutube extends GridBase {
	type: 'youtube';
	value: string;
	aspectRatioX: number;
	aspectRatioY: number;
}

export type GridType = 'text' | 'richtext' | 'image' | 'youtube' | 'images' | 'video' | 'iframe';

export type GridItem = GridText | GridRichText | GridImage | GridYoutube | GridVideo | GridIframe;

export interface LocalizedGridValue {
	[key: string]: GridItem[];
}

export interface GridData {
	type: 'grid';
	columns: number;
	value: GridItem[] | LocalizedGridValue;
}

// Matrix field types
export interface MatrixItemData {
	[subfieldName: string]: Data | GenericFieldData;
}

export interface MatrixData {
	type: 'matrix';
	value: MatrixItemData[];
}

export type Data = TextData | FileData | GridData | NumberData | MatrixData;
export type Content = Record<string, Data>;
export type Route = string | Record<string, string>;

export interface Column {
	value: string | boolean | number;
	bold: boolean;
	italic: boolean;
	badge: boolean;
	date: boolean;
	color: string;
}

export interface ListedNode {
	uid: string;
	published: boolean;
	hidden: boolean;
	locked: boolean;
	columns: Column[];
}

export interface Blueprint {
	slug: string;
	name: string;
}

export interface Collection {
	name: string;
	slug: string;
	showPublished: boolean;
	showHidden: boolean;
	showLocked: boolean;
	header: string[];
	nodes: ListedNode[];
	blueprints: Blueprint[];
}

export interface Type {
	handle: string;
	class: string;
	routable: boolean;
	renderable: boolean;
}

export interface Editor {
	uid: string;
	email: string;
	username: string;
	data: {
		name: string;
	};
}

export interface Node {
	uid: string;
	title: string;
	published: boolean;
	hidden: boolean;
	locked: boolean;
	deletable: boolean;
	created: string;
	changed: string;
	deleted: null | string;
	type: Type;
	paths: Record<string, string>;
	generatedPaths: Record<string, string>;
	route?: Route;

	fields: Field[];
	content: Content;

	creator: Editor;
	editor: Editor;
}
