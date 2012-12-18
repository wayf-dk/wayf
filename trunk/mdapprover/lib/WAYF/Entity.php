<?php

namespace WAYF;

class Entity
{
    public $id = null;
    public $entityid;
    public $name;  
    public $purpose;
    public $attributes = array();
    public $group;
    public $created = null;
    public $user;

    public function isEquivalent(\WAYF\Entity $entity)
    {
        if ($this->entityid !== $entity->entityid) {
            return false;
        } else if ($this->name !== $entity->name) {
            return false;
        } else if ($this->purpose !== $entity->purpose) {
            return false;
        } else {
            $diff = array_diff($this->attributes, $entity->attributes);

            return empty($diff);
        }
    }
}
