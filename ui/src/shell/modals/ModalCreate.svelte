<script lang="ts">
	import type { Blueprint } from '$types/data';
	import { ModalHeader, ModalBody, ModalFooter } from '$shell/modal';
	import { goto } from '$app/navigation';
	import { _ } from '$lib/locale';
	import { base } from '$lib/req';
	import Button from '$shell/Button.svelte';

	type Props = {
		close: () => void;
		collectionSlug: string;
		blueprints: Blueprint[];
		query?: string;
	};

	let { close, collectionSlug, blueprints, query = '' }: Props = $props();

	function suffix() {
		return query ? `?${query}` : '';
	}

	function createNode(slug: string) {
		return () => {
			goto(`${base}collection/${collectionSlug}/create/${slug}${suffix()}`);
			close();
		};
	}
</script>

<ModalHeader>{_('Inhaltstyp hinzufügen')}</ModalHeader>
<ModalBody>
	<div class="cms-modal-create-grid">
		{#if blueprints.length > 0}
			{#each blueprints as blueprint}
				<Button
					class="secondary"
					onclick={createNode(blueprint.slug)}>
					<span class="cms-modal-create-label">
						{blueprint.name}
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
	</div>
</ModalFooter>

<style lang="postcss">
	.cms-modal-create-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: var(--cms-space-4);
		margin-bottom: var(--cms-space-8);
	}

	.cms-modal-create-label {
		margin-left: var(--cms-space-2);
	}
</style>
