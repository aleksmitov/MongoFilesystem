<?php
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
namespace MongoFilesystem;
use \DateTime;
/**
 * API for working with files in the MongoFilesystem
 */
class MongoFile
{
    /**
     * @var string
     */
    protected $fileName;
    /**
     * @var string
     */
    protected $extension;
    /**
     * @var int
     */
    protected $permissions;
    /**
     * @var int
     */
    protected $owner;
    /**
     * @var int
     */
    protected $group;
    /**
     * @var DateTime
     */
    protected $lastModified;
    /**
     * @var MongoId
     */
    protected $parentFolderID;
    /**
     * @var MongoId
     */
    protected $ID;
    /**
     * @var string
     */
    protected $checksum;
    /**
     * @var DateTime
     */
    protected $uploadDate;
    /**
     * @var int 
     */
    protected $size;
    public function __construct(array $metadata) {
        $this->ID = $metadata["_id"];
        $this->fileName = $metadata["fileName"];
        $this->extension = $metadata["extension"];
        $this->permissions = $metadata["permissions"];
        $this->owner = $metadata["owner"];
        $this->group = $metadata["group"];
        $this->size = $metadata["size"];
        $this->lastModified = DateTime::createFromFormat('U', $metadata["lastModified"]);
        $this->parentFolderID = $metadata["parentFolderID"];
        $this->checksum = $metadata["checksum"];
        $this->uploadDate = DateTime::createFromFormat('U', $metadata["uploadDate"]->__toString());
    }
    
    /**
     * Returns the file name
     * @return string
     */
    public function getFilename()
    {
        return $this->fileName;
    }
    /**
     * Returns the file's extension type
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }
    /**
     * Return the permissions for accessing the file
     * @return int
     */
    public function getPermissions()
    {
        return $this->permissions;
    }
    /**
     * Returns the owner of the file
     * @return int
     */
    public function getOwner()
    {
        return $this->owner;
    }
    //fileName, extensions, permissions, owner, group, lastModified, parentFolderID, _id
    /**
     * Returns the group having access to the file
     * @return int
     */
    public function getGroup()
    {
        return $this->group;
    }
    /**
     * Returns the last date the file has been modified in
     * @return DateTime
     */
    public function getLastModifiedDate()
    {
        return $this->lastModified;
    }
    /**
     * Return the ID of the parent folder
     * @return MongoId
     */
    public function getParentFolderID()
    {
        return $this->parentFolderID;
    }
    /**
     * Returns the ID of the file
     * @return MongoId
     */
    public function getID()
    {
        return $this->ID;
    }
    /**
     * @return string
     */
    public function getChecksum()
    {
        return $this->checksum;
    }
    /**
     * @return DateTime
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }
    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }
}
