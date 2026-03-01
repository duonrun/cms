import type { LayoutLoad } from './$types';
import req from '$lib/req';
import type { Collection } from '$types/data';
import { currentNode, currentFields } from '$lib/state';

function intParam(value: string | null, fallback: number, min: number, max: number) {
	if (value === null || value === '') {
		return fallback;
	}

	const parsed = Number.parseInt(value, 10);

	if (!Number.isFinite(parsed)) {
		return fallback;
	}

	if (parsed < min) {
		return min;
	}

	if (parsed > max) {
		return max;
	}

	return parsed;
}

export const load: LayoutLoad = async ({ params, fetch, url }) => {
	currentNode.set(null);
	currentFields.set(null);

	const limit = intParam(url.searchParams.get('limit'), 50, 1, 250);
	const offset = intParam(url.searchParams.get('offset'), 0, 0, Number.MAX_SAFE_INTEGER);
	const q = (url.searchParams.get('q') ?? '').trim();
	const sort = (url.searchParams.get('sort') ?? '').trim();
	const dir = (url.searchParams.get('dir') ?? '').trim().toLowerCase();
	const query: Record<string, string> = {
		limit: String(limit),
		offset: String(offset),
	};

	if (q !== '') {
		query.q = q;
	}

	if (sort !== '') {
		query.sort = sort;
	}

	if (dir === 'asc' || dir === 'desc') {
		query.dir = dir;
	}

	const response = await req.get(`collection/${params.collection}`, query, fetch);

	return (response?.ok ? response.data : {}) as Collection;
};
