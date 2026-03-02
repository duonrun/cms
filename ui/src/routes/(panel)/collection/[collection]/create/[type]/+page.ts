import type { PageLoad } from './$types';
import type { Node } from '$types/data';
import { error } from '@sveltejs/kit';
import req from '$lib/req';

export const load: PageLoad = async ({ params, fetch, parent, route, url }) => {
	const collection = await parent();
	let uri = `blueprint/${params.type}`;
	const defaults = url.searchParams.get('content');
	const parentUid = (url.searchParams.get('parent') ?? '').trim();

	if (defaults) {
		uri = `${uri}?content=${encodeURI(defaults)}`;
	}

	const response = await req.get(uri, {}, fetch);

	if (response?.ok) {
		const node = response.data as Node;

		if (parentUid !== '') {
			node.parent = parentUid;
		}

		return {
			collection,
			node,
			parent: parentUid || null,
			route,
		};
	}

	error(404, 'Not Found');
};
