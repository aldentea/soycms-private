create table EntryAttribute(
	entry_id integer,
	entry_field_id VARCHAR(255),
	entry_value TEXT,
	entry_extra_values TEXT,
	unique(entry_id, entry_field_id)
) ENGINE=InnoDB;
