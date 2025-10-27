-- Adiciona coluna EAN/GTIN à tabela products, se não existir
ALTER TABLE products ADD COLUMN IF NOT EXISTS ean VARCHAR(20) NULL;

-- Opcional: para registros sem GTIN, padroniza valor textual 'Sem GTIN'
UPDATE products SET ean = 'Sem GTIN' WHERE (ean IS NULL OR TRIM(ean) = '');


