DROP TRIGGER IF EXISTS nodes_trigger_03_audit;

CREATE TRIGGER nodes_trigger_03_audit AFTER UPDATE ON cms_nodes
FOR EACH ROW BEGIN
	INSERT INTO audit_nodes (
		node, parent, changed, published, hidden, locked,
		type, editor, deleted, content
	)
	SELECT
		OLD.node, OLD.parent, OLD.changed, OLD.published, OLD.hidden, OLD.locked,
		OLD.type, OLD.editor, OLD.deleted, OLD.content
	WHERE NOT EXISTS (
		SELECT 1
		FROM audit_nodes
		WHERE node = OLD.node AND changed = OLD.changed
	);
END;
