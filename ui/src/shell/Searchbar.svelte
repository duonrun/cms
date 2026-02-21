<script lang="ts">
	import type { Blueprint } from '$types/data';
	import type { ModalFunctions } from '$shell/modal';

	import { _ } from '$lib/locale';
	import { getContext } from 'svelte';
	import { base } from '$lib/req';
	import { goto } from '$app/navigation';
	import NavToggle from './NavToggle.svelte';
	import ModalCreate from '$shell/modals/ModalCreate.svelte';

	type Props = {
		searchTerm: string;
		blueprints: Blueprint[];
		collectionSlug: string;
	};

	let { searchTerm = $bindable(), blueprints, collectionSlug }: Props = $props();
	let { open, close } = getContext<ModalFunctions>('modal');

	async function create() {
		if (blueprints.length > 1) {
			open(
				ModalCreate,
				{
					blueprints,
					collectionSlug,
					close,
				},
				{},
			);
		} else {
			goto(`${base}collection/${collectionSlug}/create/${blueprints[0].slug}`);
		}
	}
</script>

<div class="headerbar cms-searchbar">
	<NavToggle />
	<div class="cms-searchbar-controls">
		<div class="cms-searchbar-input-wrap">
			<svg
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 20 20"
				fill="currentColor">
				<path
					fill-rule="evenodd"
					d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
					clip-rule="evenodd" />
			</svg>
			<input
				type="text"
				placeholder={_('Suche')}
				bind:value={searchTerm} />
		</div>
		{#if blueprints.length > 0}
			<button
				class="cms-searchbar-create"
				onclick={create}
				aria-label="create">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 20 20"
					fill="currentColor">
					<path
						fill-rule="evenodd"
						d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
						clip-rule="evenodd" />
				</svg>
			</button>
		{/if}
	</div>
</div>

<style lang="postcss">
	.cms-searchbar {
		justify-content: center;
	}

	.cms-searchbar-controls {
		display: flex;
		width: 100%;
		max-width: var(--s-6xl);
		margin: 0 var(--s-4) 0 var(--s-14);
	}

	.cms-searchbar-input-wrap {
		display: flex;
		flex-grow: 1;
		align-items: center;

		border: var(--border);
		border-radius: var(--radius-full);

		svg {
			height: var(--s-5);
			width: var(--s-5);
			margin-left: var(--s-3);
			color: var(--gray-400);
		}

		input {
			flex-grow: 1;
			height: var(--s-10);
			box-shadow: none;
			border: 0;
			border-radius: 0 var(--radius-full) var(--radius-full) 0;

			&:focus {
				outline: none;
			}
		}
	}

	.cms-searchbar-create {
		display: flex;
		justify-content: center;
		align-items: center;
		height: var(--s-10);
		width: var(--s-10);
		padding: 0;
		margin-left: var(--s-2);

		border-radius: var(--radius-full);
		border: 1px solid var(--color-gray-400);
		color: var(--color-gray-500);

		svg {
			margin: 0;
			box-shadow: none;
			height: var(--s-6);
			width: var(--s-6);
		}
	}

	@media (min-width: 1024px) {
		.cms-searchbar-controls {
			margin: 0 var(--s-16);
		}
	}
</style>
