<script lang="ts">
	import { _ } from '$lib/locale';
	import { ModalHeader, ModalBody, ModalFooter } from '$shell/modal';
	import { currentNode as node, currentFields as fields } from '$lib/state';
	import IcoDocument from '$shell/icons/IcoDocument.svelte';
	import IcoImage from '$shell/icons/IcoImage.svelte';
	import IcoLink from '$shell/icons/IcoLink.svelte';
	import File from './ModalLinkFile.svelte';
	import Image from './ModalLinkImage.svelte';
	import Button from '$shell/Button.svelte';

	let { close, add, value = $bindable(), blank = $bindable() } = $props();

	let currentTab = $state('manually');

	function clickAdd() {
		if (value) {
			add(value, blank);
			close();
		}
	}

	function clickFile(path: string) {
		value = path;
	}

	function changeTab(tab) {
		return () => (currentTab = tab);
	}
</script>

<ModalHeader class="text-xl font-bold">{_('Add link')}</ModalHeader>
<ModalBody>
	<div class="flex flex-col gap-4">
		<div class="tabs">
			<div class="border-b border-gray-200">
				<nav aria-label="Tabs">
					<button
						class="tab"
						class:active={currentTab === 'manually'}
						onclick={changeTab('manually')}>
						<IcoLink />
						<span>{_('Manueller Link')}</span>
					</button>
					<button
						class="tab"
						class:active={currentTab === 'images'}
						onclick={changeTab('images')}>
						<IcoImage />
						<span>{_('Bilder')}</span>
					</button>
					<button
						class="tab"
						class:active={currentTab === 'files'}
						onclick={changeTab('files')}>
						<IcoDocument />
						<span>{_('Dateien/Dokumente')}</span>
					</button>
				</nav>
			</div>
		</div>
		<div class="files overflow-y-auto">
			{#if currentTab === 'images'}
				{#if $fields}
					<div class="flex flex-row flex-wrap gap-2">
						{#each $fields as field (field)}
							{#if field.type === 'Duon\\Cms\\Field\\Image'}
								{#if $node.content[field.name] && $node.content[field.name].files}
									{#each $node.content[field.name].files as file}
										{#if file.file}
											<Image
												node={$node.uid}
												file={file.file}
												{clickFile}
												bind:current={value} />
										{/if}
									{/each}
								{/if}
							{/if}
						{/each}
					</div>
				{/if}
			{:else if currentTab === 'files'}
				{#if $fields}
					<div>
						{#each $fields as field (field)}
							{#if field.type === 'Duon\\Cms\\Field\\File'}
								{#if $node.content[field.name] && $node.content[field.name].files}
									{#each $node.content[field.name].files as file}
										{#if file.file}
											<File
												node={$node.uid}
												file={file.file}
												{clickFile}
												bind:current={value} />
										{/if}
									{/each}
								{/if}
							{/if}
						{/each}
					</div>
				{/if}
			{:else}
				<div>
					<div class="mt-4">
						{_('Bitte eine gültige URL eingeben')}
					</div>
					<div class="mt-4">
						<input
							class="cms-input"
							type="text"
							bind:value />
					</div>
				</div>
			{/if}
		</div>
	</div>
	<div class="mt-4">
		<div class="relative flex items-start">
			<div class="flex h-6 items-center">
				<input
					id="modallink_target"
					aria-describedby="comments-description"
					name="modallink_target"
					type="checkbox"
					bind:checked={blank}
					class="cms-checkbox" />
			</div>
			<div class="ml-3 text-sm leading-6">
				<label
					for="modallink_target"
					class="cms-checkbox-label">
					{_('In neuem Fenster öffnen')}
				</label>
			</div>
		</div>
	</div>
</ModalBody>
<ModalFooter>
	<div class="controls">
		<Button
			class="danger"
			onclick={close}>
			{_('Abbrechen')}
		</Button>
		<Button
			class="primary"
			onclick={clickAdd}
			disabled={!value}>
			{_('Link hinzufügen')}
		</Button>
	</div>
</ModalFooter>

<style lang="postcss">
	.files {
		max-height: 50vh;
	}
</style>
