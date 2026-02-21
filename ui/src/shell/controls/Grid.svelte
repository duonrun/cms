<script lang="ts">
	import { system, systemLocale } from '$lib/sys';
	import Field from '$shell/Field.svelte';
	import LabelDiv from '$shell/LabelDiv.svelte';
	import GridPanel from './GridPanel.svelte';
	import type { GridData } from '$types/data';
	import type { GridField } from '$types/fields';

	type Props = {
		field: GridField;
		data: GridData;
		node: string;
	};

	let { field, data = $bindable(), node }: Props = $props();

	let lang = $state(systemLocale($system));
</script>

<Field {field}>
	<LabelDiv
		translate={field.translate}
		bind:lang>
		{field.label}
	</LabelDiv>
	<div class="cms-field-content">
		{#if data.value}
			{#if field.translate}
				{#each $system.locales as locale}
					{#if locale.id === lang}
						<GridPanel
							bind:data={data.value[lang]}
							{field}
							{node} />
					{/if}
				{/each}
			{:else}
				<GridPanel
					bind:data={data.value}
					{field}
					{node} />
			{/if}
		{/if}
	</div>
</Field>
