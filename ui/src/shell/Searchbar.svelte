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
		query: string;
		search: () => void;
	};

	let { searchTerm = $bindable(), blueprints, collectionSlug, query, search }: Props = $props();
	let { open, close } = getContext<ModalFunctions>('modal');

	function suffix() {
		return query ? `?${query}` : '';
	}

	function submit(event: SubmitEvent) {
		event.preventDefault();
		search();
	}

	async function create() {
		if (blueprints.length > 1) {
			open(
				ModalCreate,
				{
					blueprints,
					collectionSlug,
					query,
					close,
				},
				{},
			);
		} else {
			goto(`${base}collection/${collectionSlug}/create/${blueprints[0].slug}${suffix()}`);
		}
	}
</script>

<div class="headerbar cms-searchbar">
	<NavToggle />
	<div class="cms-searchbar-controls">
		<form
			onsubmit={submit}
			class="cms-searchbar-input-wrap">
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
		</form>
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
		max-width: var(--cms-size-6xl);
		margin: 0 var(--cms-space-4) 0 var(--cms-space-14);
	}

	.cms-searchbar-input-wrap {
		display: flex;
		flex-grow: 1;
		align-items: center;

		border: var(--cms-border);
		border-radius: var(--cms-radius-full);

		svg {
			height: var(--cms-space-5);
			width: var(--cms-space-5);
			margin-left: var(--cms-space-3);
			color: var(--cms-color-neutral-400);
		}

		input {
			flex-grow: 1;
			height: var(--cms-space-10);
			box-shadow: none;
			border: 0;
			border-radius: 0 var(--cms-radius-full) var(--cms-radius-full) 0;

			&:focus {
				outline: none;
			}
		}
	}

	.cms-searchbar-create {
		display: flex;
		justify-content: center;
		align-items: center;
		height: var(--cms-space-10);
		width: var(--cms-space-10);
		padding: 0;
		margin-left: var(--cms-space-2);

		border-radius: var(--cms-radius-full);
		border: 1px solid var(--cms-color-neutral-400);
		color: var(--cms-color-neutral-500);

		svg {
			margin: 0;
			box-shadow: none;
			height: var(--cms-space-6);
			width: var(--cms-space-6);
		}
	}

	@media (min-width: 1024px) {
		.cms-searchbar-controls {
			margin: 0 var(--cms-space-16);
		}
	}
</style>
