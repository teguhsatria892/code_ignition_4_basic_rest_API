<?php

namespace App\Controllers;

use App\Models\UserModel;

class User extends BaseController
{
    protected $user_model = null;

    // Get all users

    public function __construct()
    {
        $this->user_model = new UserModel();
    }
    

    public function get_list()
    {
        $paginationData = $this->getPaginationParams('get');

        $result = $this->user_model->get_list($paginationData);

        $this->returnJsonPagination($result);
    }
}
