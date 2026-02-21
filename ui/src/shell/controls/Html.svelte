<script lang="ts">
	import { system, systemLocale } from '$lib/sys';
	import Field from '$shell/Field.svelte';
	import Wysiwyg from '$shell/wysiwyg/Wysiwyg.svelte';
	import LabelDiv from '$shell/LabelDiv.svelte';
	import type { TextData } from '$types/data';
	import type { SimpleField } from '$types/fields';

	type Props = {
		field: SimpleField;
		data: TextData;
	};

	let { field, data = $bindable() }: Props = $props();

	let lang = $state(systemLocale($system));
</script>

<Field {field}>
	<LabelDiv
		translate={field.translate}
		bind:lang>
		{field.label}
	</LabelDiv>
	{#if field.description}
		<div class="cms-field-description">
			{field.description}
		</div>
	{/if}
	<div class="cms-field-control">
		{#if field.translate}
			{#each $system.locales as locale (locale)}
				{#if locale.id === lang}
					<Wysiwyg
						name={field.name}
						required={field.required}
						bind:value={data.value[locale.id]} />
				{/if}
			{/each}
		{:else}
			<Wysiwyg
				name={field.name}
				required={field.required}
				bind:value={data.value} />
		{/if}
	</div>
</Field>
