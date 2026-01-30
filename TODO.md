# TODO

- Fallback URLs respectively parameterized URLs in the framework
  Like /url/path/...remainder
       /url/path/{id}/{name}
- Add field type reference: reference a single node or a list of nodes
- Initialize Nodes only once, feed the instance with data after it is initialized.
  This way iteration over a list of nodes with the same type would be more performant.
- Insert overwriting in Boiler
- Improve Html::balanceTags. This is a very naive implementation and does not
  handle singlular tags like `<br>` or `<hr class="whatever">`
- Improve Html::excerpt. Check if we're in the middle of an opening tag at the end.
- Add fulltext to builtin page query fields.
- Check JSON values with json schema
- Move docs from mkdocs to vitepress?
- Check entropy algorithm. See: <https://github.com/mvhenten/string-entropy/blob/master/index.js>
  The example works in another way. We count character classes, the example adds alphabet lengths.
- Update menu items when the path of an page changes
