<script lang="ts">
	import type { CodeData } from '$types/data';
	import type { CodeField } from '$types/fields';

	import { _ } from '$lib/locale';
	import { setDirty } from '$lib/state';
	import { system, systemLocale } from '$lib/sys';
	import { DEFAULT_CODE_SYNTAX, normalizeCodeSyntax } from '$shell/code/languages';
	import CodeEditor from '$shell/code/CodeEditor.svelte';
	import Field from '$shell/Field.svelte';
	import LabelDiv from '$shell/LabelDiv.svelte';

	type Props = {
		field: CodeField;
		data: CodeData;
	};

	let { field, data = $bindable() }: Props = $props();
	let lang = $state(systemLocale($system));

	const syntaxOptions = $derived(
		field.syntaxes && field.syntaxes.length > 0 ? field.syntaxes : [DEFAULT_CODE_SYNTAX],
	);

	$effect(() => {
		if (field.translate) {
			if (!data.value || typeof data.value === 'string') {
				data.value = {};
			}

			const translated = data.value as Record<string, string>;

			for (const locale of $system.locales) {
				if (translated[locale.id] === undefined || translated[locale.id] === null) {
					translated[locale.id] = '';
				}
			}
		} else if (typeof data.value !== 'string') {
			data.value = '';
		}
	});

	$effect(() => {
		const normalized = normalizeCodeSyntax(
			data.syntax ?? syntaxOptions[0] ?? DEFAULT_CODE_SYNTAX,
		);

		if (!syntaxOptions.includes(normalized)) {
			data.syntax = syntaxOptions[0] ?? DEFAULT_CODE_SYNTAX;
			return;
		}

		if (data.syntax !== normalized) {
			data.syntax = normalized;
		}
	});

	function onSyntaxChange() {
		setDirty();
	}
</script>

<Field {field}>
	<LabelDiv
		translate={field.translate}
		bind:lang>
		{field.label}
	</LabelDiv>
	<div class="cms-field-control">
		<div class="cms-code-control-toolbar">
			<label
				class="cms-code-control-syntax-label"
				for={`${field.name}-syntax`}>
				{_('Syntax')}
			</label>
			<select
				class="cms-select cms-code-control-syntax-select"
				id={`${field.name}-syntax`}
				bind:value={data.syntax}
				onchange={onSyntaxChange}>
				{#each syntaxOptions as syntaxOption}
					<option value={syntaxOption}>{syntaxOption}</option>
				{/each}
			</select>
		</div>

		{#if field.translate}
			{#each $system.locales as locale (locale.id)}
				{#if locale.id === lang}
					<CodeEditor
						name={field.name}
						required={field.required}
						bind:syntax={data.syntax}
						bind:value={(data.value as Record<string, string>)[locale.id]} />
				{/if}
			{/each}
		{:else}
			<CodeEditor
				name={field.name}
				required={field.required}
				bind:syntax={data.syntax}
				bind:value={data.value as string} />
		{/if}
	</div>
</Field>
