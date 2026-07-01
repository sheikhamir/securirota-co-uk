-- Migration 036: Add company_id to subcontractors
-- Adds tenant scoping for subcontractor records.

ALTER TABLE subcontractors
ADD COLUMN company_id INT NULL AFTER id;

UPDATE subcontractors
SET company_id = 1
WHERE company_id IS NULL OR company_id <> 1;

ALTER TABLE subcontractors
MODIFY COLUMN company_id INT NOT NULL;

ALTER TABLE subcontractors
ADD INDEX idx_subcontractors_company_id (company_id);

ALTER TABLE subcontractors
ADD CONSTRAINT fk_subcontractors_company_id
FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT;
