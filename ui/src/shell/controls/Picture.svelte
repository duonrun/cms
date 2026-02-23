<script lang="ts">
	import type { FileData } from '$types/data';
	import type { ImageField } from '$types/fields';

	import { system, systemLocale } from '$lib/sys';
	import Field from '$shell/Field.svelte';
	import Upload from '$shell/Upload.svelte';
	import LabelDiv from '$shell/LabelDiv.svelte';

	type Props = {
		field: ImageField;
		data: FileData;
		node: string;
	};

	let { field, data = $bindable(), node }: Props = $props();

	let lang = $state(systemLocale($system));
	let limitMax = $derived(field.limit?.max ?? 999);
	let isMultiple = $derived(limitMax > 1);
</script>

<Field {field}>
	<LabelDiv
		translate={field.translate}
		bind:lang>
		{field.label}
	</LabelDiv>
	<div class="cms-field-content">
		<Upload
			type="image"
			multiple={isMultiple}
			{limitMax}
			path="{$system.prefix}/media/image/node/{node}"
			name={field.name}
			translate={field.translateFile ? false : field.translate}
			bind:assets={data.files} />
		<!-- As picture tags show only one image, we need only one alt definition
            {#if i === 0}
                {#if field.translate}
                    {#each $system.locales as locale}
                        {#if locale.id === lang}
                            <input
                                type="text"
                                name="{field.name}_alt_{locale.id}"
                                bind:value={file.alt[locale.id]} />
                        {/if}
                    {/each}
                {:else}
                    <input
                        type="text"
                        name="{field.name}_alt"
                        bind:value={file.alt} />
                {/if}
            {/if -->
	</div>
</Field>
