DROP TRIGGER IF EXISTS authtokens_trigger_01_change ON cms.users;
DROP TRIGGER IF EXISTS authtokens_trigger_01_change ON cms.authtokens;
CREATE TRIGGER authtokens_trigger_01_change BEFORE UPDATE ON cms.authtokens
	FOR EACH ROW EXECUTE FUNCTION cms.update_changed_column();
