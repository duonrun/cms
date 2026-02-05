WITH RECURSIVE nav AS (
   SELECT
	   menu,
	   item AS path,
	   printf('%08d', displayorder) AS sort,
	   1 AS level,
	   item,
	   parent,
	   data
   FROM
	   cms_menuitems
   WHERE
	   parent IS NULL
	   AND menu = :menu

   UNION ALL

   SELECT
	   m.menu,
	   nav.path || '.' || m.item AS path,
	   nav.sort || '.' || printf('%08d', m.displayorder) AS sort,
	   nav.level + 1 AS level,
	   m.item,
	   m.parent,
	   m.data
   FROM
	   cms_menuitems m
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
