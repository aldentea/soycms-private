ALTER TABLE Administrator ADD COLUMN token VARCHAR(255) UNIQUE;
ALTER TABLE Administrator ADD COLUMN token_issued_date INTEGER;