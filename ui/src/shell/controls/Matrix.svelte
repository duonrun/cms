<script lang="ts">
	import type { GenericFieldData, MatrixData, MatrixItemData } from '$types/data';
	import type { Field as FieldType, MatrixField } from '$types/fields';

	import { _ } from '$lib/locale';
	import { setDirty } from '$lib/state';
	import { system } from '$lib/sys';
	import { get } from 'svelte/store';
	import { flip } from 'svelte/animate';
	import Field from '$shell/Field.svelte';
	import LabelDiv from '$shell/LabelDiv.svelte';
	import Button from '$shell/Button.svelte';
	import IcoCirclePlus from '$shell/icons/IcoCirclePlus.svelte';
	import MatrixItem from './MatrixItem.svelte';

	type Props = {
		field: MatrixField;
		data: MatrixData;
		node: string;
	};

	let { field, data = $bindable(), node }: Props = $props();

	function createEmptyItem(): MatrixItemData {
		const item: MatrixItemData = {};

		for (const subfield of field.subfields) {
			// Create default structure for each subfield based on its type
			item[subfield.name] = createDefaultValue(subfield);
		}

		return item;
	}

	function createTranslatableValue(): Record<string, null> {
		const sys = get(system);
		const value: Record<string, null> = {};

		for (const locale of sys.locales) {
			value[locale.id] = null;
		}

		return value;
	}

	function createDefaultValue(subfield: FieldType): GenericFieldData {
		const isTranslatable = subfield.translate === true;

		// Return appropriate default structure based on field type
		const typeMap: Record<string, () => GenericFieldData> = {
			'Duon\\Cms\\Field\\Text': () => ({
				type: 'text',
				value: isTranslatable ? createTranslatableValue() : '',
			}),
			'Duon\\Cms\\Field\\Textarea': () => ({
				type: 'text',
				value: isTranslatable ? createTranslatableValue() : '',
			}),
			'Duon\\Cms\\Field\\Html': () => ({
				type: 'html',
				value: isTranslatable ? createTranslatableValue() : '',
			}),
			'Duon\\Cms\\Field\\Checkbox': () => ({ type: 'checkbox', value: false }),
			'Duon\\Cms\\Field\\Number': () => ({ type: 'number', value: 0 }),
			'Duon\\Cms\\Field\\Date': () => ({ type: 'date', value: '' }),
			'Duon\\Cms\\Field\\Time': () => ({ type: 'time', value: '' }),
			'Duon\\Cms\\Field\\Image': () => ({ type: 'image', files: [] }),
			'Duon\\Cms\\Field\\Picture': () => ({ type: 'picture', files: [] }),
			'Duon\\Cms\\Field\\File': () => ({ type: 'file', files: [] }),
			'Duon\\Cms\\Field\\Video': () => ({ type: 'video', files: [] }),
			'Duon\\Cms\\Field\\Grid': () => ({
				type: 'grid',
				columns: 12,
				value: isTranslatable ? createTranslatableValue() : [],
			}),
			'Duon\\Cms\\Field\\Option': () => ({ type: 'option', value: '' }),
			'Duon\\Cms\\Field\\Iframe': () => ({ type: 'iframe', value: '' }),
			'Duon\\Cms\\Field\\Hidden': () => ({ type: 'hidden', value: '' }),
		};

		const factory = typeMap[subfield.type];
		if (factory) {
			return factory();
		}

		// Default fallback for unknown types
		return { type: 'text', value: isTranslatable ? createTranslatableValue() : '' };
	}

	function addItem() {
		if (!data.value) {
			data.value = [];
		}

		data.value.push(createEmptyItem());
		data.value = data.value;
		setDirty();
	}
</script>

<Field {field}>
	<LabelDiv translate={false}>
		{field.label}
	</LabelDiv>
	<div class="matrix-field">
		{#if data.value && data.value.length > 0}
			<div class="matrix-items">
				{#each data.value as item, index (item)}
					<div animate:flip={{ duration: 300 }}>
						<MatrixItem
							{field}
							bind:data={data.value}
							bind:item={data.value[index]}
							{node}
							{index} />
					</div>
				{/each}
			</div>
			<div class="matrix-add">
				<Button
					class="secondary"
					onclick={addItem}>
					<span class="cms-button-icon">
						<IcoCirclePlus />
					</span>
					{_('Eintrag hinzufügen')}
				</Button>
			</div>
		{:else}
			<div class="matrix-empty">
				<Button
					class="secondary"
					onclick={addItem}>
					<span class="cms-button-icon">
						<IcoCirclePlus />
					</span>
					{_('Ersten Eintrag hinzufügen')}
				</Button>
			</div>
		{/if}
	</div>
</Field>

<style lang="postcss">
	.matrix-field {
		margin-top: 0.5rem;
		border: 1px solid var(--cms-color-neutral-300);
		border-radius: 0.375rem;
		background: var(--cms-color-neutral-200);
		padding: 0.75rem;
	}

	.matrix-items {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.matrix-add {
		display: flex;
		justify-content: center;
		margin-top: 0.75rem;
		padding-top: 0.75rem;
		border-top: 1px dashed var(--cms-color-neutral-300);
	}

	.matrix-empty {
		display: flex;
		justify-content: center;
		padding: 1rem;
	}
</style>
