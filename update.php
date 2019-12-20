<?php
/**
 * 
 * Retrieve Latest Data from Database & Push to Woocommerce
 * 
 */


require_once __DIR__ . '/controller/db.php';
require_once __DIR__ . '/controller/api.php';
require_once __DIR__ . '/controller/scrape.php';
require_once __DIR__ . '/category.php';

$db         = new Database;
$api        = new API;
$cat        = new Categories;
$scraper    = new scraper;
$cat->createCategories();


$sql = "SELECT * FROM products WHERE ImageURL IS NOT NULL";

$db->query($sql);
$db->execute();

$results = $db->resultSet();

foreach($results AS $key => $value){
    $data = [
        'name' => $value->Description,
        'type' => 'simple',
        'regular_price' => $value->RetailPrice,
        'description' => 'Test',
        'short_description' => 'Test',
        'categories' => [
            [
                'id' => $api->returnCatId($value->Category)->woo_id
            ],
            [
                'id' => $api->returnCatId($value->Subcategory)->woo_id
            ]
        ],
        'images' => [
            [
                'src' => $scraper->downloadImage($value->ImageURL)
            ]
        ]
    ];
    
    print_r($api->Create($data));
    exit;
}
