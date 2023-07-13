UPDATE messages_template SET code = uuid;
UPDATE messages_template SET messages_template.uuid = REPLACE(UUID(), '-', '')