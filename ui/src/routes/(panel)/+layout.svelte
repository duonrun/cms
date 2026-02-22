<script lang="ts">
	import type { Snippet } from 'svelte';

	import Modal from '$shell/modal/Modal.svelte';
	import Nav from '$shell/Nav.svelte';
	import Toasts from '$shell/Toasts.svelte';

	type Props = {
		data: any;
		children: Snippet;
	};

	let { data, children }: Props = $props();
</script>

<svelte:head>
	{#each data?.system?.theme ?? [] as href (href)}
		<link
			rel="stylesheet"
			{href} />
	{/each}
</svelte:head>

<Modal>
	<div class="cms-panel-layout">
		<Nav collections={data.system.collections} />
		<main class="cms-panel-main">
			{@render children()}
		</main>
	</div>
	<Toasts />
</Modal>

<style lang="postcss">
	.cms-panel-layout {
		position: relative;
		display: flex;
		flex-direction: row;
	}

	.cms-panel-main {
		flex-grow: 1;
		height: 100vh;
		overflow: hidden;
	}
</style>
