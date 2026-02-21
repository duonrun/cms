<script lang="ts">
	import { setDirty } from '$lib/state';
	import Field from '$shell/Field.svelte';
	import type { BooleanData } from '$types/data';
	import type { SimpleField } from '$types/fields';

	type Props = {
		field: SimpleField;
		data: BooleanData;
	};

	let { field, data = $bindable() }: Props = $props();

	function onchange() {
		setDirty();
	}
</script>

<Field {field}>
	<div class="cms-field-control relative flex items-start">
		<div class="flex h-6 items-center">
			<input
				id={field.name}
				name={field.name}
				type="checkbox"
				class="cms-checkbox"
				disabled={field.immutable}
				bind:checked={data.value}
				{onchange} />
		</div>
		<div class="ml-3 text-sm leading-6">
			<label
				for={field.name}
				class="cms-checkbox-label">
				{field.label}
			</label>
			{#if field.description}
				<p class="cms-field-help">{field.description}</p>
			{/if}
		</div>
	</div>
</Field>

<style lang="postcss">
	input[type='checkbox'] {
		border-width: 1.5px;
	}
</style>
