WITH RECURSIVE nav AS (
   SELECT
	   menu,
	   item AS path,
	   array[displayorder] AS sort,
	   1 AS level,
	   item,
	   parent,
	   data
   FROM
	   cms.menuitems
   WHERE
	   parent IS NULL
	   AND menu = :menu

   UNION ALL

   SELECT
	   m.menu,
	   path || '.' || m.item AS path,
	   sort || m.displayorder AS sort,
	   nav.level + 1 AS level,
	   m.item,
	   m.parent,
	   m.data
   FROM
	   cms.menuitems m
   JOIN
		   nav ON m.parent = nav.item
)
SELECT
	menu,
	item,
	sort,
	path,
	parent,
	level,
	data
FROM
	nav
ORDER BY
	menu,
	sort,
	item;
