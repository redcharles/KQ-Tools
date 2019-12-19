<?php 

use Automattic\WooCommerce\Client;

/**
 * 
 *  Methods for handling POST/GET request and pushing to woocommerce API 
 * 
 */

class API {
    
    public function POST($data = NULL){
        
        $response = [
            'Error' => 'No data.'
        ];

        if(is_null($data)) {
            return json_encode($response);
        }
        
    }
}