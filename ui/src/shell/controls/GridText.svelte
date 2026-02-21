<script lang="ts">
	import type { Snippet } from 'svelte';
	import type { GridText } from '$types/data';
	import type { GridField } from '$types/fields';

	import { setDirty } from '$lib/state';

	type Props = {
		field: GridField;
		item: GridText;
		index: number;
		children: Snippet<[{ edit: () => void }]>;
	};

	let { field, item = $bindable(), index, children }: Props = $props();
	let showSettings = $state(false);

	function oninput() {
		setDirty();
	}
</script>

<div class="grid-cell-header">
	{@render children({ edit: () => (showSettings = !showSettings) })}
</div>
<div class="grid-cell-body cms-grid-text-body">
	{#if showSettings}
		<div>Keine Einstellungsmöglichkeiten vorhanden</div>
	{:else}
		<textarea
			name={field.name + '_' + index}
			bind:value={item.value}
			{oninput}>
		</textarea>
	{/if}
</div>

<style lang="postcss">
	.cms-grid-text-body {
		flex-grow: 1;
	}

	textarea {
		height: 100%;
	}
</style>
