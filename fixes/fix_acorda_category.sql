-- Fix Açorda Alentejana category
-- This recipe should be 'Entrada' not 'Prato Principal'

USE siteguedes;

UPDATE recipes 
SET category = 'Entrada',
    subcategory = NULL
WHERE id = 7 
  AND title = 'Açorda Alentejana';

-- Verify the change
SELECT id, title, category, subcategory 
FROM recipes 
WHERE id = 7;
