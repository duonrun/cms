<script lang="ts">
	import { _ } from '$lib/locale';
	import { ModalHeader, ModalBody, ModalFooter } from '$shell/modal';
	import Button from '$shell/Button.svelte';

	type Props = {
		add: (index: number, before: boolean, type: string) => void;
		close: () => void;
		index: number | null;
		types: { id: string; label: string }[];
	};

	let { add, close, index, types }: Props = $props();

	let type: string | null = $state(null);
	let disabled = $derived(type === null);

	function addContent(before: boolean) {
		return () => {
			if (!disabled) {
				add(index, before, type);
				close();
			}
		};
	}

	function setType(t: string) {
		return () => (type = t);
	}
</script>

<ModalHeader>
	{_('Inhaltstyp hinzufügen')}
</ModalHeader>
<ModalBody>
	<div class="cms-modal-add-types">
		{#if types.length > 0}
			{#each types as t}
				<Button
					class="cms-modal-add-type {t.id === type ? 'is-selected' : ''}"
					onclick={setType(t.id)}>
					<span>
						{t.label}
					</span>
				</Button>
			{/each}
		{/if}
	</div>
</ModalBody>
<ModalFooter>
	<div class="controls">
		<Button
			class="danger"
			onclick={close}>
			{_('Abbrechen')}
		</Button>
		<Button
			class="primary"
			onclick={addContent(true)}
			{disabled}>
			{index === null ? _('Einfügen') : _('Davor einfügen')}
		</Button>
		{#if index !== null}
			<Button
				class="primary"
				onclick={addContent(false)}
				{disabled}>
				{_('Danach einfügen')}
			</Button>
		{/if}
	</div>
</ModalFooter>

<style lang="postcss">
	.cms-modal-add-types {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: var(--cms-space-4);
		margin-bottom: var(--cms-space-8);
	}

	:global(.cms-modal-add-type) {
		border: 1px solid var(--cms-color-info-700);
		background-color: var(--cms-color-white);
		color: var(--cms-color-info-700);
	}

	:global(.cms-modal-add-type.is-selected) {
		background-color: var(--cms-color-info-700);
		color: var(--cms-color-white);
	}
</style>
