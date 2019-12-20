<?php 
require __DIR__ . '/../vendor/autoload.php';
require_once 'db.php';
use Automattic\WooCommerce\Client;

/**
 * 
 *  Methods for handling POST/GET request and pushing to woocommerce API 
 * 
 */

class API {
    

    function Create($data = NULL){
        $woocommerce = new Client(
            'http://142.93.61.155/', 
            'ck_0974d5f1edbcffc57a3486b8956f0d85520bc4da', 
            'cs_bfaa938e5772028fa90c83866930a016a035945d',
            [
                'wp_api' => true, 
                'version' => 'wc/v3',
                'verify_ssl' => false
            ]
        );        
        $response = $woocommerce->post('products', $data);
        return json_decode($response);
    }

    public function listCats(){
        $woocommerce = new Client(
            'http://142.93.61.155/', 
            'ck_0974d5f1edbcffc57a3486b8956f0d85520bc4da', 
            'cs_bfaa938e5772028fa90c83866930a016a035945d',
            [
                'wp_api' => true, 
                'version' => 'wc/v3',
                'verify_ssl' => false
            ]
        );        

        $response = $woocommerce->get('products/categories');
        return $response;
    }
    public function addCat($data){
        $woocommerce = new Client(
            'http://142.93.61.155/', 
            'ck_0974d5f1edbcffc57a3486b8956f0d85520bc4da', 
            'cs_bfaa938e5772028fa90c83866930a016a035945d',
            [
                'wp_api' => true, 
                'version' => 'wc/v3',
                'verify_ssl' => false
            ]
        );        
        $results = $woocommerce->post('products/categories', $data);
        return $results;
    }

    public function returnCatId($name){
        $db = new Database;
        $sql = "SELECT woo_id FROM categories WHERE name = :name";
        $db->query($sql);
        $db->bind(':name', $name);
        $db->execute();
        if($db->rowCount() != 0){
            return $db->single();
        } else {
            return false;
        }
        
    }
}