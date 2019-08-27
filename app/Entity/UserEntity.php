<?php
/**
 * Created by PhpStorm.
 * User: MeDrioX
 * Date: 21/08/2019
 * Time: 00:32
 */

namespace App\Entity;

use Core\Entity\Entity;

class UserEntity extends Entity {

    protected $id;

    protected $pseudo;

    protected $pass;


    public function getId(): ?int
    {
        return $this->id;
    }


    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }


    public function setPseudo($pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

}