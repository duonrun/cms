<script lang="ts">
	import type { Data, MatrixItemData } from '$types/data';
	import type { MatrixField, Field } from '$types/fields';
	import type { Component } from 'svelte';

	import controls from '$lib/controls';
	import MatrixControls from './MatrixControls.svelte';

	type Props = {
		field: MatrixField;
		data: MatrixItemData[];
		item: MatrixItemData;
		node: string;
		index: number;
	};

	let { field, data = $bindable(), item = $bindable(), node, index }: Props = $props();

	let collapsed = $state(false);

	function toggleCollapse() {
		collapsed = !collapsed;
	}

	function getItemTitle(): string {
		// Try to get a meaningful title from the first text-like subfield
		for (const subfield of field.subfields) {
			const subfieldData = item[subfield.name] as Data | undefined;
			if (subfieldData && 'value' in subfieldData) {
				const value = subfieldData.value;
				if (typeof value === 'string' && value.trim()) {
					return value.substring(0, 50) + (value.length > 50 ? '...' : '');
				}
				if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
					// Handle translated fields - get first non-empty value
					const record = value as Record<string, unknown>;
					for (const locale of Object.keys(record)) {
						const localeValue = record[locale];
						if (typeof localeValue === 'string' && localeValue.trim()) {
							return (
								localeValue.substring(0, 50) +
								(localeValue.length > 50 ? '...' : '')
							);
						}
					}
				}
			}
		}
		return `Item ${index + 1}`;
	}

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	type AnyComponent = Component<any, any, any>;
</script>

<div class="matrix-item">
	<div class="matrix-item-header">
		<button
			type="button"
			class="item-title"
			onclick={toggleCollapse}>
			<span class="item-number">{index + 1}.</span>
			<span class="item-label">{getItemTitle()}</span>
		</button>
		<MatrixControls
			bind:data
			{item}
			{index}
			{collapsed}
			{toggleCollapse} />
	</div>

	{#if !collapsed}
		<div class="matrix-item-body">
			{#each field.subfields as subfield (subfield.name)}
				{#if !subfield.hidden && item[subfield.name]}
					{@const SvelteComponent = controls[subfield.type as keyof typeof controls] as
						| AnyComponent
						| undefined}
					{@const widthStyle = subfield.width
						? `width: calc(${subfield.width}% - 0.5rem)`
						: 'width: 100%'}
					{#if SvelteComponent}
						<div
							class="matrix-subfield"
							style={widthStyle}>
							<SvelteComponent
								field={subfield}
								{node}
								bind:data={item[subfield.name]} />
						</div>
					{:else}
						<div
							class="matrix-subfield matrix-subfield-note"
							style={widthStyle}>
							Unknown field type: {subfield.type}
						</div>
					{/if}
				{/if}
			{/each}
		</div>
	{/if}
</div>

<style lang="postcss">
	.matrix-item {
		background: white;
		border: 1px solid var(--color-gray-300);
		border-radius: 0.375rem;
		overflow: hidden;
	}

	.matrix-item-header {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: space-between;
		padding: 0.5rem 0.75rem;
		background: var(--color-gray-50);
		border-bottom: 1px solid var(--color-gray-200);
	}

	.item-title {
		display: flex;
		flex-direction: row;
		align-items: center;
		gap: 0.5rem;
		flex-grow: 1;
		text-align: left;
		font-size: 0.875rem;
		cursor: pointer;

		&:hover {
			color: var(--color-sky-700);
		}
	}

	.item-number {
		font-weight: 600;
		color: var(--color-gray-500);
	}

	.item-label {
		color: var(--color-gray-700);
	}

	.matrix-item-body {
		padding: 1rem;
		display: flex;
		flex-wrap: wrap;
		gap: 1rem;
	}

	.matrix-subfield {
		flex-shrink: 0;
		min-width: 0;
	}

	.matrix-subfield-note {
		color: var(--color-gray-500);
	}
</style>
