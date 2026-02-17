<script lang="ts">
	import type { Node } from '$types/data';

	import { _ } from '$lib/locale';
	import { system } from '$lib/sys';
	import { broadcastCancel, dirty } from '$lib/state';
	import { generatePaths } from '$lib/urlpaths';
	import Document from '$shell/Document.svelte';
	import Pane from '$shell/Pane.svelte';
	import Tabs from '$shell/Tabs.svelte';
	import ButtonMenu from '$shell/ButtonMenu.svelte';
	import Button from '$shell/Button.svelte';
	import IcoSave from '$shell/icons/IcoSave.svelte';
	import ButtonMenuEntry from '$shell/ButtonMenuEntry.svelte';
	import Content from '$shell/Content.svelte';
	import Settings from '$shell/Settings.svelte';
	import Published from '$shell/Published.svelte';

	type Props = {
		node: Node;
		save: (published: boolean) => Promise<void>;
		saveAndClose: () => Promise<void>;
		fields: string[];
	};

	let { node = $bindable(), save, saveAndClose, fields }: Props = $props();

	let activeTab = $state('content');

	function changeTab(tab: string) {
		return () => {
			activeTab = tab;
		};
	}

	function cancel() {
		broadcastCancel();
	}

	$effect(() => {
		if (node.route) {
			node.generatedPaths = generatePaths(node, node.route, $system);
		}
	});
</script>

<div class="flex h-screen flex-col">
	{#if fields.length === 0}
		<div class="embed-control-bar sticky border-b border-gray-300 bg-white py-4">
			<div class="mx-auto flex w-full max-w-7xl flex-row px-8">
				<div class="embed-status-bar flex flex-grow flex-row items-center justify-start">
					<span class="inline-flex items-center">
						<Published
							published={node.published}
							large />
					</span>
					{#if $dirty}
						<span
							class="dirty-indicator ml-3 rounded-full bg-rose-600 px-2 pb-px text-sm font-bold text-white">
							!
						</span>
					{/if}
				</div>
				<ButtonMenu
					class="primary"
					icon={IcoSave}
					onclick={() => save(false)}
					label={_('Speichern')}>
					{#snippet children(closeMenu)}
						<ButtonMenuEntry
							onclick={() => {
								save(true);
								closeMenu();
							}}>
							{_('Speichern und veröffentlichen')}
						</ButtonMenuEntry>
					{/snippet}
				</ButtonMenu>
			</div>
		</div>
	{/if}
	<Document>
		{#if fields.length > 0}
			<Pane class="mt-6">
				<Content
					bind:fields={node.fields}
					visibleFields={fields}
					bind:node />
			</Pane>
			<div class="-mt-4 flex justify-end gap-2">
				<Button
					onclick={cancel}
					class="danger">
					{_('Abbrechen')}
				</Button>
				<Button onclick={saveAndClose}>
					{_('Speichern')}
				</Button>
			</div>
		{:else}
			<Tabs>
				<button
					onclick={changeTab('content')}
					class:active={activeTab === 'content'}
					class="tab">
					{_('Inhalt')}
				</button>
				{#if node.type.routable || node.type.renderable}
					<button
						onclick={changeTab('settings')}
						class:active={activeTab === 'settings'}
						class="tab">
						{_('Einstellungen')}
					</button>
				{/if}
			</Tabs>
			<Pane>
				{#if activeTab === 'content'}
					<Content
						bind:fields={node.fields}
						bind:node />
				{:else}
					<Settings bind:node />
				{/if}
			</Pane>
		{/if}
	</Document>
</div>
