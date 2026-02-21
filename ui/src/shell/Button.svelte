<script lang="ts">
	import type { Snippet } from 'svelte';
	import type { HTMLButtonAttributes } from 'svelte/elements';
	import type { Component } from 'svelte';

	type Props = {
		class?: string;
		variant?: 'primary' | 'secondary' | 'danger';
		icon?: Component;
		disabled?: boolean;
		type?: 'submit' | 'button' | 'reset';
		small?: boolean;
		children: Snippet;
	};

	let {
		class: cls = 'primary',
		variant = 'primary',
		icon = null,
		disabled = false,
		type = 'button',
		small = false,
		children,
		...attributes
	}: Props & HTMLButtonAttributes = $props();
</script>

<button
	class="cms-button cms-button-{variant} {small ? 'cms-button-small' : ''} {cls}"
	{type}
	{...attributes}
	{disabled}>
	{#if icon}
		{@const Icon = icon}
		<span class="h-5 w-5">
			<Icon />
		</span>
	{/if}
	{@render children()}
</button>
