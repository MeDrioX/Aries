<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 16/08/2019
 * Time: 12:27
 */

namespace App\Controllers;

use Core\Controller\AbstractController;

class FrontController extends AbstractController{

    public function index() {
        $this->render('index', []);
    }

}