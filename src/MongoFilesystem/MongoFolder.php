/*
* Copyright (c) 2014 Alexander Mitov
* 
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
* 
*  http://www.apache.org/licenses/LICENSE-2.0
* 
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/
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
