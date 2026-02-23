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
		limitMax?: number;
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
		limitMax = multiple ? 999 : 1,
		required = false,
		disabled = false,
		disabledMsg = '',
		callback = null,
		inline = false,
	}: Props = $props();

	let loading = $state(false);
	let dragging = $state(false);
	let isMultiple = $derived(limitMax > 1);
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
		let result: File[] = [];

		for (const item of items) {
			if (item.kind === 'file') {
				const file = item.getAsFile();

				if (file) {
					result.push(file);
				}
			}
		}

		return result;
	}

	function getFilesFromDrop(event: DragEvent | Event) {
		if (!(event instanceof DragEvent) || !event.dataTransfer) {
			return [];
		}

		const { files, items } = event.dataTransfer;
		let result = files.length ? [...files] : readItems(items);

		if (!isMultiple && result.length > 1) {
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

	function enforceLimit(files: File[]): File[] {
		const slotsLeft = Math.max(limitMax - (assets?.length ?? 0), 0);

		if (slotsLeft === 0) {
			open(
				Dialog,
				{
					title: _('Fehler'),
					body:
						_('In diesem Feld sind maximal') +
						' ' +
						limitMax +
						' ' +
						_('Dateien erlaubt.'),
					type: 'error',
					close,
				},
				{},
			);

			return [];
		}

		if (files.length > slotsLeft) {
			open(
				Dialog,
				{
					title: _('Fehler'),
					body:
						_('Es können nur noch') +
						' ' +
						slotsLeft +
						' ' +
						_('Datei(en) hinzugefügt werden.'),
					type: 'error',
					close,
				},
				{},
			);

			return files.slice(0, slotsLeft);
		}

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
			let files = enforceLimit(getFilesFunction(event));

			if (files.length > 0) {
				loading = true;

				let responses = await Promise.all(
					files.map(async (file: File) => {
						return upload(file).then(resp => resp.data);
					}),
				);

				const value = getTitleAltValue();

				if (isMultiple) {
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
		class="upload upload-{type}"
		class:required
		class:upload-multiple={isMultiple}
		class:upload-inline={inline}>
		<MediaList
			bind:assets
			multiple={isMultiple}
			{type}
			{path}
			{remove}
			{loading}
			{translate} />
		{#if !assets || assets.length < limitMax}
			<label
				class="dragdrop"
				class:dragging
				class:image={type === 'image'}
				for={name}
				ondrop={preventDefault(onFile(getFilesFromDrop))}
				ondragover={preventDefault(startDragging)}
				ondragleave={preventDefault(stopDragging)}>
				<div class="cms-field-label upload-drop-label">
					<span class="upload-drop-icon"><IcoUpload /></span>
					{_('Neue Dateien per Drag and Drop hier einfügen oder')}
					<u>{_('auswählen')}</u>
				</div>
				<div class="file-extensions">
					Erlaubte Dateiendungen: {allowedExtensions}
				</div>
				<input
					type="file"
					id={name}
					multiple={isMultiple}
					oninput={onFile(getFilesFromInput)} />
			</label>
		{/if}
	</div>
{/if}

<style lang="postcss">
	.upload {
		display: flex;
		width: 100%;
		height: 100%;
		flex-direction: column;

		&.upload-inline {
			margin-top: var(--cms-space-6);
		}

		&.upload-multiple {
			flex-direction: column;
		}

		&.required .dragdrop {
			border-left-width: 4px;
			border-left-color: var(--cms-color-danger-700);
			border-left-style: solid;
		}
	}

	@media (min-width: var(--cms-breakpoint-md)) {
		.upload {
			flex-direction: row;
		}
	}

	.dragdrop {
		display: flex;
		flex: 1 1 auto;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		border: 2px dashed var(--cms-color-neutral-300);
		border-radius: var(--cms-radius-md);
		background-color: var(--cms-color-neutral-100);
		padding: var(--cms-space-4) var(--cms-space-2);
		text-align: center;
		vertical-align: middle;
	}

	.dragdrop.dragging {
		border-color: var(--cms-color-info-700);
		background-color: color-mix(
			in srgb,
			var(--cms-color-info-700) 10%,
			var(--cms-color-neutral-100)
		);
	}

	.upload-drop-label {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: center;
		gap: var(--cms-space-2);
		color: var(--cms-color-neutral-600);
	}

	.upload-drop-icon {
		display: inline-block;
		width: var(--cms-space-6);
		height: var(--cms-space-6);
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

	:global(.dragdrop > .upload-drop-label svg) {
		display: inline;
		margin-bottom: var(--cms-space-2);
	}
	:global(.dragdrop > .upload-drop-label u) {
		color: var(--cms-color-info-700);
	}

	.dragdrop > div.file-extensions {
		font-weight: normal;
		font-size: var(--cms-font-size-xs);
		color: var(--cms-color-neutral-400);
		margin-top: var(--cms-space-1);
	}

	@media (min-width: var(--cms-breakpoint-md)) {
		:global(.upload-image .preview) {
			width: var(--cms-fraction-2-5);
		}
	}
</style>
