<?php
namespace MongoFilesystem;
use \SplDoublyLinkedList;
/**
 * API for representation of folders in the MongoDB
 */
class MongoFolder
{
    /**
     * @var MongoId
     */
    protected $ID;
    /**
     * @var string
     */
    protected $folderName;
    /**
     * @var int
     */
    protected $owner;
    /**
     * @var int
     */
    protected $group;
    /**
     * @var int
     */
    protected $size;
    /**
     * @var int
     */
    protected $permissions;
    /**
     * @var MongoId
     */
    protected $parentFolderID;
    /**
     * @var int
     */
    protected $level;
    /**
     * @var SplDoublyLinkedList
     */
    protected $files;
    /**
     * @var SplDoublyLinkedList
     */
    protected $subfolders;
    /**
     * Constucts MongoFolder by the metadata fetched from MongoDB
     * @param array holding all the folder metadata
     * @param boolean Used to prevent traversal of all subfolders
     */
    public function __construct(array $metadata)
    {
        $this->files = new SplDoublyLinkedList();
        $this->subfolders = new SplDoublyLinkedList();
        $this->folderName = $metadata["folderName"];
        $this->owner = $metadata["owner"];
        $this->group = $metadata["group"];
        $this->size = $metadata["size"];
        $this->parentFolderID = $metadata["parentFolderID"];
        $this->permissions = $metadata["permissions"];
        $this->level = $metadata["level"];
        $this->subfolders = $metadata["subfolders"];
        $this->files = $metadata["files"];
        $this->ID = $metadata["_id"];
    }
    /**
     * Returns the files in the folder
     * @return SplDoublyLinkedList<MongoFile>
     */
    public function getFiles()
    {
        return $this->files;
    }
    /**
     * Returns all the subfolders
     * @return SplDoublyLinkedList<MongoFolder>
     */
    public function getSubfolders()
    {
        return $this->subfolders;
    }
    /**
     * Return the ID of the folder
     * @return MongoId
     */
    public function getID()
    {
        return $this->ID;
    }
    /**
     * @return string
     */
    public function getFoldername()
    {
        return $this->folderName;
    }
    /**
     * @return int
     */
    public function getOwner()
    {
        return $this->owner;
    }
    /**
     * @return int
     */
    public function getGroup()
    {
        return $this->group;
    }
    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }
    /**
     * @return int
     */
    public function getPermissions()
    {
        return $this->permissions;
    }
    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }
    /**
     * @return MongoId
     */
    public function getParentFolderID()
    {
        return $this->parentFolderID;
    }
    
}
