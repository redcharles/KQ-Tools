<?php 
require __DIR__ . '/../vendor/autoload.php';
require_once 'db.php';
use Automattic\WooCommerce\Client;

define("site_url", "https://kennyqueenhardware.com");
define("public_key", "ck_79cae6f16fca6ced19ff779b113c3fe84abba896");
define("secret_key", "cs_f53ed0976bdc9a8a9fffdd685b66fc820069ad72");

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

    public function getSingle($sku){
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
        $results = $woocommerce->get('products/'.$sku);
        return $results;
    }
    public function getAll($data){
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
        $results = $woocommerce->get('products', $data);
        return $results;
    }
}
