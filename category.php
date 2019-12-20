<?php

/**
 * 
 * Retrieve All Categories & Subcategories from DB then creates them in WooCommerce
 * 
 */


require_once __DIR__ . '/controller/db.php';
require_once __DIR__ . '/controller/api.php';

class Categories
{
    public function createCategories()
    {
        $db         = new Database;
        $api        = new API;

        $sql = "SELECT category, subcategory FROM products WHERE Category IS NOT NULL";

        $db->query($sql);
        $db->execute();

        $results = $db->resultSet();

        $catArray = array();

        foreach ($results as $key => $value) {
            $catArray[$value->category][] = $value->subcategory;
        }

        foreach ($catArray as $key => $value) {
            $catArray[$key] = array_unique($value);
        }


        $wooCats = $api->listCats();

        $savedCats = array();

        foreach ($wooCats as $key => $value) {
            $savedCats[] = [
                'CategoryName' => $value->name,
                'ID' => $value->id,
                'Parent' => $value->parent
            ];
        }

        foreach ($catArray as $key => $value) {

            $catName = ucwords(strtolower($key));
            $data = [
                'name' => $catName
            ];
            if ($api->returnCatid($catName)) {
            } else {
                $results = $api->addCat($data);
                $sql = "INSERT INTO categories (name, woo_id) VALUES (:name, :woo_id)";
                $db->query($sql);
                $db->bind(':name', $catName);
                $db->bind(':woo_id', $results->id);
                $db->execute();
            }

            foreach ($value as $data) {
                $catName = ucwords(strtolower($data));
                $dataSub = [
                    'name' => $catName
                ];
                if ($catName == 'Blank') {
                    continue;
                }

                if ($api->returnCatId($catName)) {
                } else {
                    $resultsSub = $api->addCat($dataSub);
                    $sql = "INSERT INTO categories (name, woo_id, parent_id) VALUES (:name, :woo_id, :parent_id)";
                    $db->query($sql);
                    $db->bind(':name', $catName);
                    $db->bind(':woo_id', $resultsSub->id);
                    $db->bind(':parent_id', $results->id);
                    $db->execute();
                }
            }
            echo "\n";
        }
    }
}
