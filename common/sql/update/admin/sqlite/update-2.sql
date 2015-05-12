ALTER TABLE Administrator ADD COLUMN token VARCHAR UNIQUE;
ALTER TABLE Administrator ADD COLUMN token_issued_date INTEGER;