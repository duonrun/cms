<script lang="ts">
	import type { PageData } from './$types';
	import { create } from '$lib/node';
	import Node from '$shell/Node.svelte';

	type Props = {
		data: PageData;
	};

	type CollectionState = {
		name: string;
		slug: string;
		q?: string;
		offset?: number;
		limit?: number;
		sort?: string;
		dir?: string;
	};

	let { data }: Props = $props();
	let collection = data.collection as CollectionState;
	let node = $state(data.node);

	function collectionPath() {
		const params = new URLSearchParams();

		if (collection.q) {
			params.set('q', collection.q);
		}

		params.set('offset', String(collection.offset ?? 0));
		params.set('limit', String(collection.limit ?? 50));

		if (collection.sort) {
			params.set('sort', collection.sort);
		}

		if (collection.dir) {
			params.set('dir', collection.dir);
		}

		const query = params.toString();

		if (query === '') {
			return `collection/${collection.slug}`;
		}

		return `collection/${collection.slug}?${query}`;
	}

	async function save(publish: boolean) {
		if (publish) {
			node.published = true;
		}

		create(node, node.type.handle, collectionPath());
	}
</script>

<Node
	bind:node
	{collection}
	{save} />
