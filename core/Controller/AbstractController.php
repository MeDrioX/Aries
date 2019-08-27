<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 16/08/2019
 * Time: 12:28
 */

namespace Core\Controller;


class AbstractController {

    protected function render($view, $params = []) {
        $path = ROOT . '/views/' . $view . '.pyxis.php';

        if (file_exists($path)) {
            //ob_start();
            extract($params);
            require $path;
            //$content = ob_get_clean();
        } else {
            throw new \Exception("La vue '{$view}' n'a pas été trouvée");
        }
    }

}