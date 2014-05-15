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
use \SplFileInfo;
use \DateTime;
use \Exception;
use \InvalidArgumentException;
use \ErrorException;
/**
 * The File class is an Object-oriented representation of a file in the file system
 */
class File
{
    /*
     * Instance of SplFileInfo containting the information about our file
     * @var SplFileInfo
     */
    protected $file;
    /*
     * The constructor takes as a parameter the path to the local file system
     * where the file is located at
     * 
     * @param $filePath - the path to the file as a string
     * @throws InvalidArgumentException
     * @throws ErrorException
     */
    public function __construct($filePath) {
        $this->file = new SplFileInfo($filePath);
        if(!$this->file->isFile()) throw new
            InvalidArgumentException("The file path: " . $filePath . " doesn't lead to a file");
        if(!$this->file->isReadable()) throw new
            ErrorException("The file path: " . $filePath . " isn't readable");
    }
    /*
     * Returns the time when the contents of the file were changed.
     * The time returned is an instance of the DateTime class.
     * 
     * @return DateTime instance
     */
    public function getLastModifiedDate()
    {
        $timestamp = $this->file->getMTime();
        $lastModifiedDate = DateTime::createFromFormat('U', $timestamp);
        return $lastModifiedDate;
    }
    /**
     * Gets the last access time for the file.
     * 
     * @return DateTime instance
     * @throws RuntimeException
     */
    public function getLastAccessTime()
    {
        $timestamp = $this->file->getATime();
        $lastAccessDate = DateTime::createFromFormat('U', $timestamp);
        return $lastAccessDate;
    }
    /**
     * Returns the file name without the file extension
     * 
     * @return string
     */
    public function getName()
    {
        $fileName = $this->file->getFilename();
        $segments = explode('.', $fileName);
        $output = "";
        $i = 0;
        do
        {
            $output .= $segments[$i] . '.';
            ++$i;
        } while($i < count($segments)-1);
        $output = trim($output, '.'); 
        return $output; //we omit the extension part
    }
    /**
     * Retrieves the file extension.
     * 
     * @return string
     */
    public function getExtension()
    {
        return $this->file->getExtension();
    }
    /**
     * Gets the file group. The group ID is returned in numerical format.
     * 
     * @return int
     * @throw RuntimeException
     */
    public function getGroup()
    {
        return $this->file->getGroup();
    }
    /**
     * Gets the file owner. The owner ID is returned in numerical format.
     *
     *  @return int
     * @throws RuntimeException
     */
    public function getOwner()
    {
        return $this->file->getOwner();
    }
    /**
     * Returns the path to the file, omitting the filename and any trailing slash.
     * 
     * @return string
     */
    public function getPath()
    {
        return $this->file->getPath();
    }
    /**
     * This method return the relative path to the file
     * @return string
     */
    public function getRelativePath()
    {
        return $this->file->getPathname();
    }
    /**
     * This method expands all symbolic links, resolves relative references and
     * returns the real path to the file.
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->file->getRealPath();
    }
    /**
     * Returns the filesize in bytes for the file referenced.
     * 
     * @return int
     * @throws RuntimeException
     */
    public function getSize()
    {
        return $this->file->getSize();
    }
    /**
     * Returns the absolute path to the file
     * 
     * @return string
     */
    public function __toString() {
        return $this->getAbsolutePath();
    }
    /**
     * Gets the file permissions for the file.
     * @return int
     */
    public function getPermissions()
    {
        return $this->file->getPerms();
    }
}