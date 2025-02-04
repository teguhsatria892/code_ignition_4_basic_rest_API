<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends BaseModel
{
    // Get list with pagination, keyword filter, and order
    public function get_list($paginationData)
    {
        $keywordColumnList = ["u.username"];
        $ordersColumnList = ["u.username"];

        $mappingColumnNameFEandBE = [
            "u.username" => "username",
        ];

        $paginationData->keywordColumnList = $keywordColumnList;
        $paginationData->ordersColumnList = $ordersColumnList;
        $paginationData->mappingColumnNameFEandBE = $mappingColumnNameFEandBE;

        $query = $this->db->table("user u")->select("*");

        return $this->getPaginationResult($query, $paginationData);
    }

    // Create a new user
    public function create_user($data)
    {
        // Make sure the data array is valid
        if ($this->validate_user_data($data)) {
            return $this->db->table('user')->insert($data);
        }
        return false; // Return false if validation fails
    }

    // Update an existing user
    public function update_user($id, $data)
    {
        // Make sure the data array is valid
        if ($this->validate_user_data($data)) {
            return $this->db->table('user')
                            ->where('id', $id)
                            ->update($data);
        }
        return false; // Return false if validation fails
    }

    // Delete a user by ID
    public function delete_user($id)
    {
        return $this->db->table('user')
                        ->where('id', $id)
                        ->delete();
    }

    // Validate user data (basic example)
    private function validate_user_data($data)
    {
        // Example validation, you can add more rules
        return isset($data['username']) && isset($data['email']);
    }
}
