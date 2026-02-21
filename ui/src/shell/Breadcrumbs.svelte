<script lang="ts">
	import IcoChevronRight from '$shell/icons/IcoChevronRight.svelte';
	import IcoDocumentTree from '$shell/icons/IcoDocumentTree.svelte';
	import Link from '$shell/Link.svelte';

	type Props = {
		slug: string;
		name: string;
	};

	let { slug, name }: Props = $props();
</script>

<div class="breadcrumbs">
	<IcoDocumentTree />
	<IcoChevronRight />
	<span>
		<Link
			href="collection/{slug}"
			class="cms-breadcrumb-link">
			{name}
		</Link>
	</span>
</div>

<style lang="postcss">
	.breadcrumbs {
		margin-top: var(--s-8);
		margin-bottom: var(--s-4);
		display: flex;
		flex-direction: row;
		align-items: center;
		gap: var(--s-3);
	}

	:global(.cms-breadcrumb-link:hover) {
		text-decoration: underline;
	}

	.breadcrumbs :global(svg) {
		color: var(--gray-400);
		display: inline-block;
	}
</style>
