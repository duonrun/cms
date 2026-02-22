<script lang="ts">
	import { _ } from '$lib/locale';
	import { logoutUser } from '$lib/user';
	import { navVisible } from '$lib/ui';
	import { collections } from '$lib/collections';
	import NavLogo from '$shell/NavLogo.svelte';
	import Link from '$shell/Link.svelte';
</script>

<div
	id="nav"
	class:open={$navVisible}>
	<NavLogo />

	{#each $collections as item (item)}
		{#if item.type === 'section'}
			<h2 class="cms-nav-section-title">{item.name}</h2>
		{:else}
			<div class="cms-nav-link-row">
				<Link href="collection/{item.slug}">
					{item.name}
				</Link>
			</div>
		{/if}
	{/each}
	<h2 class="cms-nav-section-title">{_('Benutzer')}</h2>
	<div class="cms-nav-link-row"><Link href="userprofile">{_('Mein Benutzerprofil')}</Link></div>
	<div class="cms-nav-link-row"><button onclick={logoutUser}>{_('Abmelden')}</button></div>
</div>

<style lang="postcss">
	#nav {
		width: 16rem;
		margin-left: -16rem;
		display: flex;
		flex-direction: column;
		height: 100vh;
		background-color: var(--cms-color-white);
		border-right: var(--cms-border);
		padding: 0 var(--cms-space-6) var(--cms-space-6);
		box-sizing: border-box;
		transition: all 0.15s ease-in-out;

		&.open {
			margin-left: 0;
		}

		:global(a),
		button {
			font-size: var(--cms-font-size-sm);
			color: var(--cms-color-high);
		}

		button {
			text-align: left;
		}
	}

	.cms-nav-section-title {
		margin-top: var(--cms-space-6);
		font-size: var(--cms-font-size-sm);
		font-weight: 600;
	}

	.cms-nav-link-row {
		margin-top: var(--cms-space-1);
		padding-left: var(--cms-space-4);
	}
</style>
