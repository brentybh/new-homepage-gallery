<?php

namespace App\Repository\LeanCloud;

trait LeanObjectTrait
{
    abstract protected function toJSON();

    public function getData()
    {
        $data = $this->toJSON();

        unset(
            $data['objectId'],
            $data['createdAt'],
            $data['updatedAt'],
            $data['ACL']
        );

        return $data;
    }
}
