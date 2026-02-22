<script lang="ts">
	import { fade } from 'svelte/transition';
	import NavLogo from '$shell/NavLogo.svelte';

	let { data } = $props();
</script>

{#key data.pathname}
	<div
		class="logo-wrapper"
		in:fade={{ duration: 300, delay: 400 }}
		out:fade={{ duration: 300 }}>
		<NavLogo />
	</div>
{/key}

<style lang="postcss">
	.logo-wrapper {
		margin-left: auto;
		margin-right: auto;
		max-width: var(--cms-size-xl);
		margin-top: 28vh;
	}
</style>
