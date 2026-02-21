<script lang="ts">
	import type { Node } from '$types/data';
	import type { Locale } from '$lib/sys';

	import { setDirty } from '$lib/state';
	import { _ } from '$lib/locale';
	import { system } from '$lib/sys';
	import ToggleLine from '$shell/ToggleLine.svelte';

	type Props = {
		node: Node;
	};

	let { node = $bindable() }: Props = $props();

	function getPathPlaceholder(locale: Locale) {
		let localLocale: Locale | undefined = locale;

		if (!node.generatedPaths) {
			return '';
		}

		const localeId = locale.id;

		while (localLocale !== undefined) {
			const value = node.paths[locale.id];

			if (value) {
				return value;
			}

			localLocale = $system.locales.find(l => l.id === localLocale?.fallback);
		}

		const value = node.generatedPaths[localeId];

		if (value) {
			return value;
		}

		return '';
	}

	function oninput() {
		setDirty();
	}
</script>

<div class="p-4 sm:p-6 md:p-8">
	{#if node.type.routable}
		<div class="paths mb-8">
			{#each $system.locales as locale (locale)}
				<div class="path">
					<div class="cms-field-label">{locale.title}:</div>
					<div class="value">
						<input
							type="text"
							bind:value={node.paths[locale.id]}
							placeholder={getPathPlaceholder(locale)}
							required={locale.id === $system.defaultLocale}
							{oninput} />
					</div>
				</div>
			{/each}
		</div>
	{/if}
	{#if node.type.renderable}
		<div class="max-w-xl">
			<div class="mb-4">
				<ToggleLine
					title={_('Veröffentlicht')}
					subtitle={_('Legt fest, ob die Seite für alle Besucher erreichbar ist.')}
					bind:value={node.published} />
			</div>
			<!--<div class="my-4">
                <ToggleLine
                    title={_('Gesperrt')}
                    subtitle={_(
                        'Seiten die gesperrt sind, können nicht verändert werden.',
                    )}
                    bind:value={node.locked} />
            </div>-->
			<div class="mt-4">
				<ToggleLine
					title={_('Versteckt')}
					subtitle={_('Versteckte Seiten werden in Auflistungen ignoriert.')}
					bind:value={node.hidden} />
			</div>
			<div class="mt-4 flex flex-row">
				<div class="cms-field-label">{_('Internal Document-ID')}:</div>
				<div class="value pl-4">{node.uid}</div>
			</div>
		</div>
	{/if}
</div>

<style lang="postcss">
	.paths {
		display: table;
		width: 100%;
	}

	.path {
		display: table-row;

		& > div {
			padding: var(--s-2);
			display: table-cell;
		}

		.value {
			width: 100%;
		}
	}
</style>
