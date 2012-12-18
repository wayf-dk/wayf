<?php

namespace WAYF;

class EntityMapper
{
    private $db;
    private $feed;

    public function __construct($db, $feed)
    {
        $this->db   = $db;
        $this->feed = $feed;
    }

    public function getById($id)
    {
        $query = "SELECT * FROM `entity` WHERE `id` = :id AND `feed` = :feed;";

        $res = $this->db->fetchOne($query, array('id' => $id, 'feed' => $this->feed));
    
        return $this->newEntity($res);
    }

    public function getByEntityId($entityid)
    {
        $query = "SELECT * FROM `entity` WHERE `entityid` = :entityid AND `feed` = :feed;";

        $res = $this->db->fetchAll($query, array('entityid' => $entityid, 'feed' => $this->feed));

        $result = array();
       
        foreach ($res AS $entity) {
            $result[] = $this->newEntity($entity);
        }
       
        return $result;
    }

    public function findAll()
    {
        $query = "SELECT * FROM `entity` WHERE `feed` = :feed;";

        $res = $this->db->fetchAll($query, array('feed' => $this->feed));

        $result = array();
       
        foreach ($res AS $entity) {
            $result[] = $this->newEntity($entity);
        }
       
        return $result;
    }

    public function save(\WAYF\Entity $entity)
    {
        if (!is_null($entity->id)) {
            return $this->update($entity);
        } else {
            return $this->insert($entity);
        }
    }
    
    public function insert(\WAYF\Entity $entity)
    {
        $query = 'INSERT INTO `entity` (`id`, `entityid`, `name`, `purpose`, `attributes`, `feed`, `created`, `user`)
            VALUES (NULL , :entityid, :name, :purpose, :attributes, :feed, CURRENT_TIMESTAMP, :user);';

        $res = $this->db->insert($query, array(
            'entityid' => $entity->entityid,
            'name' => $entity->name,
            'purpose' => $entity->purpose,
            'attributes' => serialize($entity->attributes),
            'feed' => $this->feed,
            'user' => $entity->user,
        ));

        return $res;
    }

    public function update(\WAYF\Entity $entity)
    {
        $query = "UPDATE `entity` SET `entityid` = :entityid, `name` = :name, `purpose` = :purpose, `attributes` = :attributes, `user` = :user WHERE `id` = :id AND `feed` = :feed;";

        $res = $this->db->modify($query, array(
            'entityid' => $entity->entityid,
            'name' => $entity->name,
            'purpose' => $entity->purpose,
            'attributes' => $entity->attributes,
            'user' => $entity->user,
            'id' => $entity->id,
            'feed' => $this->feed,
        ));

        return $res;
    }

    public function delete($id)
    {}

    public function deleteByEntityId($entityid)
    {
        $query = "DELETE FROM `entity` WHERE `entityid` = :entityid AND `feed` = :feed;";

        $res = $this->db->modify($query, array('entityid' => $entityid, 'feed' => $this->feed));

        return $res;
    }

    private function newEntity(\stdClass $res)
    {
        $entity = new \WAYF\Entity();

        $entity->id         = $res->id;
        $entity->entityid   = $res->entityid;
        $entity->name       = $res->name;
        $entity->purpose    = $res->purpose;
        $entity->attributes = unserialize($res->attributes);
        $entity->created    = $res->created;
        $entity->user       = $res->user;

        return $entity;
    }
}
