-- Migration: Rename field type 'html' to 'richtext' in JSONB content

-- Update nodes.content
UPDATE cms.nodes 
SET content = regexp_replace(
    content::text, 
    '("type"\s*:\s*)"html"', 
    '\1"richtext"', 
    'g'
)::jsonb
WHERE content::text ~ '("type"\s*:\s*)"html"';

-- Update drafts.content
UPDATE cms.drafts 
SET content = regexp_replace(
    content::text, 
    '("type"\s*:\s*)"html"', 
    '\1"richtext"', 
    'g'
)::jsonb
WHERE content::text ~ '("type"\s*:\s*)"html"';

-- Update audit.nodes.content
UPDATE audit.nodes 
SET content = regexp_replace(
    content::text, 
    '("type"\s*:\s*)"html"', 
    '\1"richtext"', 
    'g'
)::jsonb
WHERE content::text ~ '("type"\s*:\s*)"html"';

-- Update audit.drafts.content
UPDATE audit.drafts 
SET content = regexp_replace(
    content::text, 
    '("type"\s*:\s*)"html"', 
    '\1"richtext"', 
    'g'
)::jsonb
WHERE content::text ~ '("type"\s*:\s*)"html"';
