<?php
/**
 *
 * Retrieve Latest Data from Database & Push to Woocommerce
 *
 */

require_once __DIR__ . '/classes/db.php';
require_once __DIR__ . '/classes/api.php';
require_once __DIR__ . '/classes/scrape.php';
require_once __DIR__ . '/category.php';



class WooMethods
{
    
    public function addProducts()
    {
        $db = new Database;
        $api = new API;
        $cat = new Categories;
        $scraper = new scraper;

        $sql = "SELECT * FROM products WHERE ImageURL IS NOT NULL AND WooId IS NULL AND SKU IS NOT NULL";

        $db->query($sql);
        $db->execute();

        $results = $db->resultSet();

        foreach ($results as $key => $value) {
            $catId = ( is_object($api->returnCatId($value->Category) )  ? $api->returnCatId($value->Category)->woo_id : "0" );
            $subCatId = ( is_object($api->returnCatId($value->Subcategory) )  ? $api->returnCatId($value->Subcategory)->woo_id : "0" );

            $data = [
                'name' => $value->Description,
                'type' => 'simple',
                'regular_price' => $value->RetailPrice,
                'sku' => $value->SKU,
                'description' => 'Test',
                'short_description' => 'Test',
                'categories' => [
                    [
                        'id' => $catId,
                    ],
                    [
                        'id' => $subCatId,
                    ],
                ],
                'images' => [
                    [
                        'src' => $scraper->downloadImage($value->ImageURL),
                    ],
                ],
            ];
            $caught = false;
            try { 
                $createProduct = $api->Create($data);
            } catch (Exception $e){
                $caught = true;
                echo $e->getMessage(), "\n";
                echo "SKU: ", $value->SKU, "\n";
            } 
            
            if(!$caught) {
                $updateSQL = "UPDATE products SET WooId=:id WHERE SKU=:sku ";
                $db->query($updateSQL);
    
                $db->bind(':sku', $value->SKU);
                $db->bind(':id', $createProduct->id);
    
                $db->execute();
                echo "Adding Product with SKU: $value->SKU \n";
            }

        }
        return True;

    }

    public function updateProducts()
    {
        $db = new Database;
        $api = new API;
        $cat = new Categories;
        $scraper = new scraper;

        $returnArr = array();
        $returnArr['Status'] = 'Updating';
        $sql = "SELECT * FROM `products` WHERE `date_updated` >= NOW() - INTERVAL 12 HOUR AND `ImageURL` IS NOT NULL AND `WooId` IS NOT NULL";

        $db->query($sql);
        $db->execute();

        $results = $db->resultSet();
        $updateCount = 0;
        
        foreach ($results as $key => $value) {
            

            $catId = ( is_object($api->returnCatId($value->Category) )  ? $api->returnCatId($value->Category)->woo_id : "0" );
            $subCatId = ( is_object($api->returnCatId($value->Subcategory) )  ? $api->returnCatId($value->Subcategory)->woo_id : "0" );
            $data = [
                'name' => $value->Description,
                'type' => 'simple',
                'regular_price' => $value->RetailPrice,
                'sku' => $value->SKU,
                'description' => 'Test',
                'short_description' => 'Test',
                'categories' => [
                    [
                        'id' => $catId,
                    ],
                    [
                        'id' => $subCatId,
                    ],
                ],
                'images' => [
                    [
                        'src' => $scraper->downloadImage($value->ImageURL),
                    ],
                ],
            ];
            
            $createProduct = $api->Update($data, $value->WooId);
            $updateSQL = "UPDATE products SET WooId=:id WHERE SKU=:sku ";
            $db->query($updateSQL);

            $db->bind(':sku', $value->SKU);
            $db->bind(':id', $createProduct->id);
            $caught = false;
            try {
                $db->execute();
            } catch(Exception $e){
                $caught = true;
                $returnArr['errors'][] = $e->getMessage();
            }
            if($caught === false){
                $updateCount++;
            }
            $returnArr['UpdatedList'][] = $value->SKU;
            $returnArr['UpdateCount'] = $updateCount;
        }
        return $returnArr;
    }

    public function updateDb(){
        $db = new Database;
        $api = new API;
        

        print_r($wooList);

        exit;
    }

    public function batchAdd($updateCount = null){
        $defaultCount = 10;
        $updateCount = (is_null($updateCount) ? $defaultCount : $updateCount);

        $db = new Database;
        $api = new API;
        $cat = new Categories;
        $scraper = new scraper;

        $sql = "SELECT count(*) as count FROM products WHERE ImageURL IS NOT NULL AND WooId IS NULL";

        $db->query($sql);
        $db->execute();

        $results = $db->single();
        
        $count = (int) $results->count;
        
        for($x = 0; $x < $count; $x+=$updateCount){            
            $sql = "SELECT * FROM products WHERE ImageUrl IS NOT NULL AND WooId IS NULL LIMIT $defaultCount";
            $db->query($sql);
            $results = $db->resultSet();
            $data = [];
            foreach($results as $key => $value){
                $catId = ( is_object($api->returnCatId($value->Category) )  ? $api->returnCatId($value->Category)->woo_id : "0" );
                $subCatId = ( is_object($api->returnCatId($value->Subcategory) )  ? $api->returnCatId($value->Subcategory)->woo_id : "0" );   
                $data['create'][] =  [
                            'name' => $value->Description,
                            'type' => 'simple',
                            'regular_price' => $value->RetailPrice,
                            'sku' => $value->SKU,
                            'description' => $value->ProductDescription,
                            'short_description' => $value->Description,
                            'categories' => [
                                [
                                    'id' => $catId,
                                ],
                                [
                                    'id' => $subCatId,
                                ],
                            ],
                            'images' => [
                                [
                                    'src' => $scraper->downloadImage($value->ImageURL),
                                ],
                            ]
                        ];
                
            } // end foreach
            $caught = false;
            $skuList = array();
            foreach($data['create'] as $key => $value){
                $sku = $value['sku'];
                $skuList[] = $sku;
            }

            try { 
                $batchUpdate = (object) $api->batchCreate($data);
            } catch (Exception $e){
                $caught = true;
                $returnArr['error'][] = $e->getMessage();
            } 
            if(!$caught){
                foreach($skuList as $key => $value){
                    $data = [
                        'sku' => $value
                    ];
                    $wooList = $api->getAll($data);
                    $id = $wooList[0]->id;
                    $returnArr['NewSkus'][] = $value;

                    $updateSQL = "UPDATE products SET WooId=:id WHERE SKU=:sku ";
                    $db->query($updateSQL);
                    $db->bind(':sku', $value);
                    $db->bind(':id', $id);
                    $db->execute();
                }
            }
            
        }//end for
        $returnArr['success'] = true;
        $returnArr['count'] = $updateCount;
        
        return $returnArr;
    }
}


