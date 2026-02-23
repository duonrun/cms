<script lang="ts">
	import { system, systemLocale } from '$lib/sys';
	import Field from '$shell/Field.svelte';
	import Upload from '$shell/Upload.svelte';
	import LabelDiv from '$shell/LabelDiv.svelte';
	import type { FileData } from '$types/data';
	import type { ImageField } from '$types/fields';

	type Props = {
		field: ImageField;
		data: FileData;
		node: string;
	};

	let { field, data = $bindable(), node }: Props = $props();

	let lang = $state(systemLocale($system));

	function getLimitMax(): number {
		return Number(field.limit?.max ?? 1);
	}
</script>

<Field
	{field}
	class="cms-field-column">
	<LabelDiv
		translate={field.translate}
		bind:lang>
		{field.label}
	</LabelDiv>
	<div class="cms-field-content">
		{#if field.translateFile}
			{#each $system.locales as locale}
				{#if locale.id === lang}
					<Upload
						type="video"
						multiple={getLimitMax() > 1}
						limitMax={getLimitMax()}
						path="{$system.prefix}/media/video/node/{node}"
						required={field.required}
						name={field.name}
						translate={false}
						bind:assets={data.files[locale.id]} />
				{/if}
			{/each}
		{:else}
			<Upload
				type="video"
				multiple={getLimitMax() > 1}
				limitMax={getLimitMax()}
				path="{$system.prefix}/media/video/node/{node}"
				required={field.required}
				name={field.name}
				translate={field.translate}
				bind:assets={data.files} />
		{/if}
	</div>
</Field>
