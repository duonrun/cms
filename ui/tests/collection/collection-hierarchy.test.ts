import { describe, expect, test } from 'vitest';
import { flattenCollectionNodes } from '$lib/collection-hierarchy';
import type { ListedNode } from '$types/data';

function node(uid: string): ListedNode {
	return {
		uid,
		published: true,
		hidden: false,
		locked: false,
		parent: null,
		hasChildren: false,
		childBlueprints: [],
		columns: [],
	};
}

describe('flattenCollectionNodes', () => {
	test('keeps only root rows when nothing is expanded', () => {
		const roots = [node('root-a'), node('root-b')];
		const rows = flattenCollectionNodes(
			roots,
			{
				'root-a': [node('child-a')],
			},
			{},
		);

		expect(rows.map(row => row.node.uid)).toEqual(['root-a', 'root-b']);
		expect(rows.map(row => row.depth)).toEqual([0, 0]);
	});

	test('adds direct children for expanded parents', () => {
		const roots = [node('root-a')];
		const rows = flattenCollectionNodes(
			roots,
			{
				'root-a': [node('child-a'), node('child-b')],
			},
			{ 'root-a': true },
		);

		expect(rows.map(row => row.node.uid)).toEqual(['root-a', 'child-a', 'child-b']);
		expect(rows.map(row => row.depth)).toEqual([0, 1, 1]);
	});

	test('shows grandchildren only when the intermediate child is expanded', () => {
		const roots = [node('root')];

		const collapsedGrandchildren = flattenCollectionNodes(
			roots,
			{
				root: [node('child')],
				child: [node('grandchild')],
			},
			{ root: true },
		);

		expect(collapsedGrandchildren.map(row => row.node.uid)).toEqual(['root', 'child']);

		const expandedGrandchildren = flattenCollectionNodes(
			roots,
			{
				root: [node('child')],
				child: [node('grandchild')],
			},
			{ root: true, child: true },
		);

		expect(expandedGrandchildren.map(row => row.node.uid)).toEqual([
			'root',
			'child',
			'grandchild',
		]);
		expect(expandedGrandchildren.map(row => row.depth)).toEqual([0, 1, 2]);
	});
});
