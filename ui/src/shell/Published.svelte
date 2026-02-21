<script lang="ts">
	import { _ } from '$lib/locale';

	type Props = {
		large?: boolean;
		published?: boolean;
	};

	let { large = false, published = false }: Props = $props();
</script>

<span
	class:large
	class:published
	class="cms-published"
	title={published ? _('veröffentlicht') : _('unveröffentlicht')}>
	{#if large}
		{published ? _('veröffentlicht') : _('unveröffentlicht')}
	{/if}
</span>

<style lang="postcss">
	span.cms-published {
		display: inline-block;
		border-radius: var(--radius-full);
		background-color: var(--gray-300);
		font-size: var(--font-size-sm);
		height: 0.4rem;
		width: 0.4rem;

		&.large {
			padding-left: var(--s-2);
			padding-right: var(--s-2);
			height: auto;
			width: auto;
		}

		&.published {
			background-color: var(--color-emerald-600);
			color: var(--color-white);
		}
	}
</style>
