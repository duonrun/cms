<script lang="ts">
	import type { ListedNode } from '$types/data';
	import Searchbar from '$shell/Searchbar.svelte';
	import Published from '$shell/Published.svelte';
	import Link from '$shell/Link.svelte';

	let { data } = $props();

	let searchTerm = $state('');
	let regex: RegExp | null = null;

	function fmtDate(d: string) {
		const date = new Date(d);

		return date.toLocaleDateString('de-DE', {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
		});
	}

	function search(searchTerm: string) {
		return (node: ListedNode) => {
			if (searchTerm.length > 1) {
				regex = new RegExp(
					`(${escapeRegExp(searchTerm)})`,
					/\p{Lu}/u.test(searchTerm) ? 'g' : 'gi',
				);
				return node.columns.some(col => {
					return regex?.test(col.value.toString() ?? '');
				});
			}

			regex = null;
			return true;
		};
	}

	function highlightSearchterm(value: string) {
		if (searchTerm.length < 2 || !regex) return value;

		return value.replace(regex, `<span class="search-hl">$1</span>`);
	}

	function escapeRegExp(string: string) {
		return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	let nodes = $derived(data.nodes.filter(search(searchTerm)));
</script>

<div class="cms-collection-page">
	<Searchbar
		bind:searchTerm
		collectionSlug={data.slug}
		blueprints={data.blueprints} />
	<h1 class="cms-collection-title">
		{data.name}
	</h1>
	<div class="cms-collection-scroll">
		<div class="cms-collection-flow">
			<div class="cms-collection-wrap">
				<div class="cms-collection-ring">
					<div class="cms-collection-table-wrap">
						<table class="cms-collection-table">
							<thead>
								<tr>
									{#if data.showPublished}
										<th class="published"></th>
									{/if}
									{#each data.header as column, i (i)}
										<th scope="col">{column}</th>
									{/each}
								</tr>
							</thead>
							<tbody>
								{#each nodes as node (node)}
									<tr>
										{#if data.showPublished}
											<td class="published cms-published-cell">
												<span class="cms-published-value">
													<Published published={node.published} />
												</span>
											</td>
										{/if}
										{#each node.columns as column (column)}
											<td
												class:cms-cell-bold={column.bold}
												class:cms-cell-italic={column.italic}>
												<Link href="collection/{data.slug}/{node.uid}">
													{#if column.date}
														{fmtDate(column.value.toString())}
													{:else}
														{@html highlightSearchterm(column.value)}
													{/if}
												</Link>
											</td>
										{/each}
									</tr>
								{/each}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style lang="postcss">
	.cms-collection-page {
		display: flex;
		height: 100%;
		flex-direction: column;
	}

	.cms-collection-title {
		padding: var(--cms-space-4) var(--cms-space-8);
		font-size: var(--cms-font-size-xl);
		font-weight: 600;
	}

	.cms-collection-scroll {
		flex: 1 1 auto;
		overflow-y: auto;
		padding: 0 var(--cms-space-4);
	}

	.cms-collection-flow {
		display: flow-root;
	}

	.cms-collection-wrap {
		margin: 0 var(--cms-space-8) var(--cms-space-8);
	}

	.cms-collection-ring {
		margin: 0 calc(var(--cms-space-4) * -1);
		border: 1px solid color-mix(in srgb, var(--cms-color-black) 5%, transparent);
	}

	.cms-collection-table-wrap {
		display: inline-block;
		min-width: 100%;
		vertical-align: middle;
		box-shadow: var(--cms-shadow);
	}

	.cms-collection-table {
		min-width: 100%;
		border-collapse: separate;
		border-spacing: 0;
		background-color: var(--cms-color-white);
	}

	th,
	td {
		padding-left: var(--cms-space-3);
		padding-right: var(--cms-space-3);
		padding-top: var(--cms-space-4);
		padding-bottom: var(--cms-space-4);
	}

	@media (min-width: var(--cms-breakpoint-sm)) {
		.cms-collection-scroll {
			padding: 0 var(--cms-space-6);
		}

		.cms-collection-ring {
			margin: 0 calc(var(--cms-space-6) * -1);
		}

		th,
		td {
			padding-left: var(--cms-space-4);
			padding-right: var(--cms-space-4);
		}
	}

	@media (min-width: var(--cms-breakpoint-lg)) {
		.cms-collection-scroll {
			padding: 0 var(--cms-space-8);
		}

		.cms-collection-ring {
			margin: 0 calc(var(--cms-space-8) * -1);
		}

		th,
		td {
			padding-left: var(--cms-space-6);
			padding-right: var(--cms-space-6);
		}
	}

	th {
		position: sticky;
		top: 0;
		z-index: 10;
		border-top-width: 1px;
		border-bottom-width: 1px;
		border-color: var(--cms-color-neutral-300);
		background-color: color-mix(in srgb, var(--cms-color-neutral-100) 75%, transparent);
		text-align: left;
		font-size: var(--cms-font-size-sm);
		font-weight: 600;
		color: var(--cms-color-neutral-900);
		backdrop-filter: blur(var(--cms-blur-sm));
	}

	td {
		border-bottom: 1px solid var(--cms-color-neutral-200);
		white-space: nowrap;
		font-size: var(--cms-font-size-sm);
		color: var(--cms-color-neutral-900);
	}

	.published {
		padding-right: 0;
	}

	.cms-published-value {
		display: inline-block;
		padding-bottom: var(--cms-space-1);
	}

	.cms-published-cell {
		text-align: center;
		vertical-align: middle;
	}

	.cms-cell-bold {
		font-weight: 600;
	}

	.cms-cell-italic {
		font-style: italic;
	}

	:global(.search-hl) {
		background: #ffea50;
		border: 1px solid #ffb100;
		border-radius: 0.25rem;
	}

	tr:hover {
		td,
		:global(td a) {
			background-color: var(--cms-color-success-100);
		}
	}
</style>
