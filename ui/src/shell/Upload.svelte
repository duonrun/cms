<script lang="ts">
	import { preventDefault } from 'svelte/legacy';

	import type { FileItem, UploadResponse, UploadType } from '$types/data';
	import type { Toast } from '$lib/toast';
	import type { ModalFunctions } from '$shell/modal';

	import { getContext } from 'svelte';
	import { _ } from '$lib/locale';
	import { system } from '$lib/sys';
	import { setDirty } from '$lib/state';
	import toast from '$lib/toast';
	import req from '$lib/req.js';
	import IcoUpload from '$shell/icons/IcoUpload.svelte';
	import Dialog from '$shell/Dialog.svelte';
	import Message from '$shell/Message.svelte';
	import MediaList from '$shell/MediaList.svelte';

	type Props = {
		path: string;
		type: UploadType;
		name: string;
		translate: boolean;
		assets: FileItem[];
		multiple?: boolean;
		required?: boolean;
		disabled?: boolean;
		disabledMsg?: string;
		callback?: () => void | null;
		inline?: boolean;
	};

	let {
		path,
		type,
		name,
		translate,
		assets = $bindable(),
		multiple = false,
		required = false,
		disabled = false,
		disabledMsg = '',
		callback = null,
		inline = false,
	}: Props = $props();

	let loading = $state(false);
	let dragging = $state(false);
	let allowedExtensions = $derived(
		type === 'image'
			? $system.allowedFiles.image.join(', ')
			: type === 'video'
				? $system.allowedFiles.video.join(', ')
				: $system.allowedFiles.file.join(', '),
	);

	let { open, close } = getContext<ModalFunctions>('modal');

	function remove(index: number | null) {
		if (index === null) {
			assets = [];
		} else {
			assets.splice(index, 1);
			assets = assets;
		}
		setDirty();
	}

	function readItems(items: DataTransferItemList) {
		let result = [];

		for (const item of items) {
			if (item.kind === 'file') {
				result.push(item.getAsFile());
			}
		}

		return result;
	}

	function getFilesFromDrop({ dataTransfer: { files, items } }: DragEvent) {
		let result = files.length ? [...files] : readItems(items);

		if (!multiple && result.length > 1) {
			open(
				Dialog,
				{
					title: _('Fehler'),
					body: _('In diesem Feld ist nur eine einzelne Datei erlaubt.'),
					type: 'error',
					close,
				},
				{},
			);
			return [];
		}

		return result;
	}

	function getFilesFromInput(event: Event) {
		const target = event.target as HTMLInputElement;
		const files = target.files ? [...target.files] : [];

		target.value = '';

		return files;
	}

	function startDragging() {
		dragging = true;
	}

	function stopDragging() {
		dragging = false;
	}

	async function upload(file: File) {
		let formData = new FormData();

		formData.append('file', file);
		return await req.post(path, formData);
	}

	function getTitleAltValue() {
		if (translate) {
			const result: Record<string, string> = {};
			$system.locales.map(locale => (result[locale.id] = ''));
			return result;
		}

		return '';
	}

	function getError(item: UploadResponse): Toast {
		return {
			kind: 'error',
			title: _('Datei:') + ' ' + item.file,
			message: item.error,
		};
	}

	function onFile(getFilesFunction: (event: DragEvent | Event) => File[]) {
		return async (event: Event) => {
			stopDragging();
			let files = getFilesFunction(event);

			if (files.length > 0) {
				loading = true;

				let responses = await Promise.all(
					files.map(async (file: File) => {
						return upload(file).then(resp => resp.data);
					}),
				);

				const value = getTitleAltValue();

				if (multiple) {
					responses.map((item: UploadResponse) => {
						if (item.ok) {
							assets.push({
								alt: value,
								title: value,
								file: item.file,
							});
							assets = [...assets];
						} else {
							toast.add(getError(item));
						}
					});
				} else {
					const item = responses[0] as UploadResponse;

					if (item.ok) {
						assets = [
							{
								alt: value,
								title: value,
								file: item.file,
							},
						];
					} else {
						toast.add(getError(item));
					}
				}

				if (assets && callback) {
					callback();
				}
			}

			loading = false;
			setDirty();
		};
	}
</script>

{#if disabled}
	{#if disabledMsg}
		<Message
			type="warning"
			text={disabledMsg} />
	{:else}
		<Message
			type="warning"
			text={_('-warning-save-to-upload-')} />
	{/if}
{:else}
	<div
		class="upload upload-{type} flex h-full w-full flex-col md:flex-row"
		class:required
		class:upload-multiple={multiple}
		class:mt-6={inline}>
		<MediaList
			bind:assets
			{multiple}
			{type}
			{path}
			{remove}
			{loading}
			{translate} />
		{#if !assets || assets.length === 0 || multiple}
			<label
				class="dragdrop flex flex-1 flex-col items-center justify-center rounded-md border-2 border-dashed border-gray-300 bg-gray-100 px-2 py-4 text-center align-middle md:mt-0 md:h-auto"
				class:dragging
				class:image={type === 'image'}
				for={name}
				ondrop={preventDefault(onFile(getFilesFromDrop))}
				ondragover={preventDefault(startDragging)}
				ondragleave={preventDefault(stopDragging)}>
				<div
					class="cms-field-label flex flex-row items-center justify-center gap-2 text-gray-600">
					<span class="inline-block h-6 w-6"><IcoUpload /></span>
					{_('Neue Dateien per Drag and Drop hier einfügen oder')}
					<u>{_('auswählen')}</u>
				</div>
				<div class="file-extensions mt-0 text-xs">
					Erlaubte Dateiendungen: {allowedExtensions}
				</div>
				<input
					type="file"
					id={name}
					{multiple}
					oninput={onFile(getFilesFromInput)} />
			</label>
		{/if}
	</div>
{/if}

<style lang="postcss">
	.upload {
		&.upload-multiple {
			flex-direction: column;
		}

		&.required .dragdrop {
			border-left-width: var(--border-4);
			border-left-color: var(--color-rose-700);
			border-left-style: solid;
		}
	}

	.upload input {
		position: absolute !important;
		height: 1px;
		width: 1px;
		overflow: hidden;
		clip: rect(1px 1px 1px 1px);
		clip: rect(1px, 1px, 1px, 1px);
		white-space: nowrap;
	}

	.dragdrop:hover {
		cursor: pointer;
	}

	:global(.dragdrop > div.label svg) {
		display: inline;
		margin-bottom: var(--s-2);
	}
	:global(.dragdrop > div.label u) {
		color: var(--color-sky-700);
	}
	.dragdrop > div.file-extensions {
		font-weight: normal;
		color: var(--color-gray-400);
		margin-top: var(--s-1);
	}

	@media (min-width: var(--breakpoint-md)) {
		:global(.upload-image .preview) {
			width: var(--fraction-2-5);
		}
	}
</style>
