-- Update document types to include new officer document types

ALTER TABLE documents 
MODIFY COLUMN document_type ENUM(
    'sia_license', 
    'id_proof', 
    'address_proof', 
    'contract', 
    'passport',
    'sia_badge_front',
    'sia_badge_back', 
    'full_body_photo',
    'proof_of_address_1',
    'proof_of_address_2',
    'other'
) NOT NULL;