<script lang="ts">
	import type {
		GridItem,
		GridBase,
		GridText as GridTextData,
		GridHtml as GridHtmlData,
		GridImage as GridImageData,
		GridYoutube as GridYoutubeData,
		GridIframe as GridIframeData,
		GridType,
	} from '$types/data';
	import type { GridField } from '$types/fields';
	import type { ModalFunctions } from '$shell/modal';

	import { _ } from '$lib/locale';
	import resize from '$lib/resize';
	import { getContext } from 'svelte';
	import { flip } from 'svelte/animate';
	import { setDirty } from '$lib/state';
	import IcoCirclePlus from '$shell/icons/IcoCirclePlus.svelte';
	import Button from '$shell/Button.svelte';
	import ModalAdd from '$shell/modals/ModalAdd.svelte';
	import GridControls from './GridControls.svelte';
	import GridImage from './GridImage.svelte';
	import GridImages from './GridImages.svelte';
	import GridHtml from './GridHtml.svelte';
	import GridText from './GridText.svelte';
	import GridYoutube from './GridYoutube.svelte';
	import GridIframe from './GridIframe.svelte';
	import GridVideo from './GridVideo.svelte';

	type Props = {
		field: GridField;
		data: GridItem[];
		node: string;
		cols?: number;
	};

	let { field, data = $bindable(), node, cols = 12 }: Props = $props();
	let { open, close } = getContext<ModalFunctions>('modal');

	const controls = {
		image: GridImage,
		html: GridHtml,
		text: GridText,
		youtube: GridYoutube,
		images: GridImages,
		video: GridVideo,
		iframe: GridIframe,
	};
	const types = [
		{ id: 'html', label: 'Formatierter Text (wysiwyg)' },
		{ id: 'text', label: 'Einfacher Text' },
		{ id: 'image', label: 'Einzelbild' },
		{ id: 'youtube', label: 'Youtube-Video' },
		{ id: 'images', label: 'Mehrere Bilder' },
		{ id: 'video', label: 'Video' },
		{ id: 'iframe', label: 'Iframe' },
	];

	function add(index: number | null, before: boolean, type: GridType) {
		let content: GridBase = {
			type,
			colspan: 12,
			rowspan: 1,
			colstart: null,
		};
		if (type === 'html') {
			(content as GridHtmlData).value = '';
		} else if (type === 'text') {
			(content as GridTextData).value = '';
		} else if (type === 'image' || type === 'images') {
			(content as GridImageData).files = [];
		} else if (type === 'youtube') {
			(content as GridYoutubeData).value = '';
			(content as GridYoutubeData).aspectRatioX = 16;
			(content as GridYoutubeData).aspectRatioY = 9;
		} else if (type === 'iframe') {
			(content as GridIframeData).value = '';
		}

		if (!data) {
			data = [];
		}

		if (index === null) {
			data.push(content as GridItem);
		} else {
			if (before) {
				data.splice(index, 0, content as GridItem);
			} else {
				if (data.length - 1 === index) {
					data.push(content as GridItem);
				} else {
					data.splice(index + 1, 0, content as GridItem);
				}
			}
		}

		setDirty();
	}

	function openAddModal(index: number | null) {
		return () => {
			open(
				ModalAdd,
				{
					index,
					add,
					close,
					types,
				},
				{},
			);
		};
	}

	function resizeCell(item: GridItem) {
		return (element: HTMLElement) => (item.width = element.clientWidth);
	}

	function gridStyle(columns: number): string {
		return `grid-template-columns: repeat(${columns}, minmax(0, 1fr));`;
	}

	function gridItemStyle(item: GridItem): string {
		const column = item.colstart
			? `${item.colstart} / span ${item.colspan}`
			: `span ${item.colspan} / span ${item.colspan}`;

		return `grid-column: ${column}; grid-row: span ${item.rowspan} / span ${item.rowspan};`;
	}
</script>

<div
	class="grid-field cms-grid-field"
	style={gridStyle(cols)}>
	{#if data && data.length > 0}
		{#each data as item, index (item)}
			{@const Control = controls[item.type]}
			<div
				class="cms-grid-item"
				style={gridItemStyle(item)}
				animate:flip={{ duration: 300 }}
				use:resize={resizeCell(item)}>
				<Control
					{item}
					{node}
					{index}
					{field}>
					{#snippet children({ edit })}
						<GridControls
							bind:data
							{item}
							{index}
							{field}
							{edit}
							add={openAddModal(index)} />
					{/snippet}
				</Control>
			</div>
		{/each}
	{:else}
		<div class="cms-grid-empty">
			<Button
				class="secondary"
				onclick={openAddModal(null)}>
				<span class="cms-grid-empty-icon">
					<IcoCirclePlus />
				</span>
				{_('Inhalt hinzfügen')}
			</Button>
		</div>
	{/if}
</div>

<style lang="postcss">
	.cms-grid-field {
		display: grid;
		gap: var(--s-3);
		padding: var(--s-3);
		border-radius: var(--radius);
		border: var(--border);
		background-color: var(--gray-200);
	}

	.cms-grid-item {
		position: relative;
		display: flex;
		flex-direction: column;
		border-radius: var(--radius);
		border: var(--border);
		background-color: var(--white);
		padding: 0 var(--s-2) var(--s-2);
	}

	.cms-grid-empty {
		grid-column: 1 / -1;
		display: flex;
		justify-content: center;
		padding: var(--s-4);
	}

	.cms-grid-empty-icon {
		display: inline-flex;
		width: 1.25rem;
		height: 1.25rem;
		margin-right: var(--s-2);
	}
</style>
