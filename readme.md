#delete all woo products

DELETE relations.*, taxes.*, terms.*
FROM wp_term_relationships AS relations
INNER JOIN wp_term_taxonomy AS taxes
ON relations.term_taxonomy_id=taxes.term_taxonomy_id
INNER JOIN wp_terms AS terms
ON taxes.term_id=terms.term_id
WHERE object_id IN (SELECT ID FROM wp_posts WHERE post_type='product');
 
DELETE FROM wp_postmeta WHERE post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'product');
DELETE FROM wp_posts WHERE post_type = 'product';

#delete all woo categories


DELETE a,c FROM wp_terms AS a 
LEFT JOIN wp_term_taxonomy AS c ON a.term_id = c.term_id
LEFT JOIN wp_term_relationships AS b ON b.term_taxonomy_id = c.term_taxonomy_id
WHERE c.taxonomy = 'product_tag';

DELETE a,c FROM wp_terms AS a
LEFT JOIN wp_term_taxonomy AS c ON a.term_id = c.term_id
LEFT JOIN wp_term_relationships AS b ON b.term_taxonomy_id = c.term_taxonomy_id
WHERE c.taxonomy = 'product_cat'