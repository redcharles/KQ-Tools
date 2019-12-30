<?php 
require __DIR__ . '/../vendor/autoload.php';
require_once 'db.php';
use Automattic\WooCommerce\Client;

define("site_url", "http://testwp.reddresssolutions.com");
define("public_key", "ck_e8e9ec7d5c3bef32ad0915699ef576c5e6d704f4");
define("secret_key", "cs_916133d78ed2185ced99181e07b0000d7468257d");

/**
 * 
 *  Methods for handling POST/GET request and pushing to woocommerce API 
 * 
 */

class API {
    

    function Create($data = NULL){
        $woocommerce = new Client(
            site_url, 
            public_key, 
            secret_key,
            [
                'wp_api' => true, 
                'version' => 'wc/v3',
                'verify_ssl' => false
            ]
        );        
        $response = $woocommerce->post('products', $data);
        return $response;
    }

    function batchCreate($data = null){
        $woocommerce = new Client(
            site_url, 
            public_key, 
            secret_key,
            [
                'wp_api' => true, 
                'version' => 'wc/v3',
                'verify_ssl' => false
            ]
        );        
        $response = $woocommerce->post('products/batch', $data);
        return $response;
    }
    
    function Update($data = NULL, $WooId = NULL){
        $woocommerce = new Client(
            site_url, 
            public_key, 
            secret_key,
            [
                'wp_api' => true, 
                'version' => 'wc/v3',
                'verify_ssl' => false
            ]
        );        
        $response = $woocommerce->put("products/$WooId", $data);
        return $response;
    }

    public function listCats(){
        $woocommerce = new Client(
            site_url, 
            public_key, 
            secret_key,
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
            site_url, 
            public_key, 
            secret_key,
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
