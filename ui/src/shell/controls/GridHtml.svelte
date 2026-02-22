<script lang="ts">
	import type { Snippet } from 'svelte';
	import type { GridHtml } from '$types/data';
	import type { GridField } from '$types/fields';

	import RichTextEditor from '$shell/richtext/RichTextEditor.svelte';

	type Props = {
		field: GridField;
		item: GridHtml;
		index: number;
		children: Snippet<[{ edit: () => void }]>;
	};

	let { field, item = $bindable(), index, children }: Props = $props();

	let showSettings = $state(false);
</script>

<div class="grid-cell-header">
	{@render children({ edit: () => (showSettings = !showSettings) })}
</div>
<div class="grid-cell-body">
	{#if showSettings}
		<div>Keine Einstellungsmöglichkeiten vorhanden</div>
	{:else}
		<RichTextEditor
			required={false}
			name={field.name + '_' + index}
			bind:value={item.value} />
	{/if}
</div>
