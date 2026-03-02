import type { ListedNode } from '$types/data';

export interface VisibleCollectionRow {
	node: ListedNode;
	depth: number;
}

export function flattenCollectionNodes(
	nodes: ListedNode[],
	childrenByParent: Record<string, ListedNode[]>,
	expanded: Record<string, boolean>,
): VisibleCollectionRow[] {
	const rows: VisibleCollectionRow[] = [];

	const walk = (items: ListedNode[], depth: number) => {
		for (const node of items) {
			rows.push({ node, depth });

			if (!expanded[node.uid]) {
				continue;
			}

			walk(childrenByParent[node.uid] ?? [], depth + 1);
		}
	};

	walk(nodes, 0);

	return rows;
}
