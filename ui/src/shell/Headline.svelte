<script lang="ts">
	import type { Snippet } from 'svelte';

	import { dirty } from '$lib/state';
	import { _ } from '$lib/locale';
	import Published from '$shell/Published.svelte';

	type Props = {
		showPublished?: boolean;
		published?: boolean;
		children: Snippet;
	};

	let { showPublished = false, published = false, children }: Props = $props();
</script>

<h1 class="cms-headline">
	<span class="cms-headline-title">
		{@render children()}
	</span>
	<div class="status-bar cms-headline-status">
		{#if $dirty}
			<span class="dirty-indicator cms-headline-dirty-indicator">!</span>
		{/if}
		{#if showPublished}
			<span class="cms-headline-published">
				<Published
					{published}
					large />
			</span>
		{/if}
	</div>
</h1>

<style lang="postcss">
	.cms-headline {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: flex-start;
		margin-bottom: var(--s-6);
		font-size: var(--text-3xl);
		font-weight: 600;
		line-height: 36px;
	}

	.cms-headline-title {
		display: flex;
		align-items: center;
	}

	.cms-headline-status {
		display: flex;
		flex: 1 1 auto;
		flex-direction: row;
		align-items: center;
		justify-content: flex-end;
	}

	.cms-headline-dirty-indicator {
		margin-left: var(--s-4);
		border-radius: var(--radius-full);
		background-color: var(--color-rose-700);
		padding: 0 var(--s-2) var(--s-px);
		font-size: var(--font-size-sm);
		font-weight: 700;
		color: var(--white);
	}

	.cms-headline-published {
		margin-left: var(--s-3);
		display: inline-flex;
		align-items: center;
	}
</style>
