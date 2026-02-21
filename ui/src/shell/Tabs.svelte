<script lang="ts">
	import type { Snippet } from 'svelte';

	type Props = {
		children: Snippet;
	};

	let { children }: Props = $props();
</script>

<div class="tabs cms-tabs">
	<nav aria-label="Tabs">
		{@render children()}
	</nav>
</div>

<style lang="postcss">
	.cms-tabs {
		margin-bottom: var(--s-6);
	}
</style>
