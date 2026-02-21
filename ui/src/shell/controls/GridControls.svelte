<script lang="ts">
	import type { GridItem } from '$types/data';
	import type { GridField } from '$types/fields';
	import GridSizeButtons from '$shell/controls/GridSizeButtons.svelte';
	import GridCellButtons from '$shell/controls/GridCellButtons.svelte';
	import IcoThreeDots from '$shell/icons/IcoThreeDots.svelte';
	import IcoGear from '$shell/icons/IcoGear.svelte';

	interface Props {
		data: GridItem[];
		item: GridItem;
		field: GridField;
		index: number;
		edit: () => void;
		add: () => void;
	}

	let {
		data = $bindable(),
		item = $bindable(),
		field = $bindable(),
		index = $bindable(),
		edit,
		add,
	}: Props = $props();

	let showDropdown = $state(false);
</script>

<div class="content-actions cms-grid-controls">
	{#if item.width < 350}
		<div class="cms-grid-controls-compact">
			<div class="cms-grid-buttons cms-grid-buttons-dropdown">
				<div>
					<button
						type="button"
						class="cms-grid-buttons-toggle"
						onclick={() => (showDropdown = !showDropdown)}>
						<span class="sr-only">Open options</span>
						<IcoThreeDots />
					</button>
				</div>
				{#if showDropdown}
					<div
						class="cms-grid-buttons-menu"
						role="menu"
						aria-orientation="vertical"
						aria-labelledby="menu-button"
						tabindex="-1">
						<div
							class="cms-grid-buttons-menu-content"
							role="none">
							<GridCellButtons
								bind:data
								bind:item
								bind:index
								{add}
								dropdown />
							<GridSizeButtons
								bind:field
								bind:item
								dropdown />
						</div>
					</div>
				{/if}
			</div>
		</div>
	{:else}
		<div class="cms-grid-buttons cms-grid-buttons-inline">
			<GridSizeButtons
				bind:field
				bind:item />
			<GridCellButtons
				bind:data
				bind:item
				bind:index
				{add} />
		</div>
	{/if}
	<div class="cms-grid-controls-edit">
		<button
			class="edit"
			onclick={edit}>
			<IcoGear />
		</button>
	</div>
</div>

<style lang="postcss">
	div button {
		height: var(--s-4);
		width: var(--s-4);
	}

	.cms-grid-controls {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: flex-end;
	}

	.cms-grid-controls-compact {
		display: flex;
		flex: 1 1 auto;
		flex-direction: row;
		align-items: center;
		justify-content: flex-end;
		gap: var(--s-3);
		padding: var(--s-2) 0;
		margin-right: var(--s-3);
	}

	.cms-grid-controls-edit {
		display: flex;
		flex: 0 1 auto;
		flex-direction: row;
		align-items: center;
		justify-content: flex-end;
	}

	.cms-grid-buttons {
		opacity: 0;
		transition: opacity 0.35s ease;
	}

	.cms-grid-buttons-dropdown {
		position: relative;
		display: inline-block;
		text-align: left;
		opacity: 1;
	}

	.cms-grid-buttons-inline {
		display: flex;
		flex: 1 1 auto;
		flex-direction: row;
		align-items: center;
		justify-content: flex-end;
	}

	.cms-grid-buttons-toggle {
		display: flex;
		align-items: center;
	}

	.cms-grid-buttons-menu {
		position: absolute;
		right: 0;
		z-index: 10;
		margin-top: var(--s-2);
		width: 11rem;
		transform-origin: top right;
		border-radius: var(--radius-md);
		background-color: var(--white);
		padding: 0 var(--s-2);
		box-shadow: var(--shadow-lg);
		outline: none;
		border: 1px solid color-mix(in srgb, var(--color-black) 5%, transparent);
	}

	.cms-grid-buttons-menu-content {
		display: flex;
		flex-direction: column;
		justify-content: center;
		padding: var(--s-1) 0;
	}

	.cms-grid-buttons:hover {
		opacity: 1;
	}

	.cms-grid-buttons :global(button .grid-button-label) {
		opacity: 0;
	}

	.cms-grid-buttons :global(button:hover .grid-button-label) {
		opacity: 1;
	}

	.edit {
		opacity: 1;
	}
</style>
