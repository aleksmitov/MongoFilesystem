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
use \SplFileInfo;
use \SplDoublyLinkedList;
use \InvalidArgumentException;
use \ErrorException;
use \SplStack;
use \SplQueue;
/**
 * An Object-oriented representation of a folder in the file system
 */
class Folder
{
    /**
     * Instance of SplFileInfo which is used for implementing our class API
     * @var SplFileInfo
     */
    protected $folder;
    /**
     * Hold all the files in the folder
     * @var SplDoublyLinkedList<File>
     */
    protected $files;
    /**
     * Hold all the subfolders in the current folder
     * @var SplDoublyLinkedList holding instances of Folder
     */
    protected $subfolders;
    /**
     * Holds all summed up size in bytes of the files in the folder and subfolders
     * @var int
     */
    protected $size = 0;
    /**
     * The constructor gets the directory path of the folder
     * so it can be used later by using recursion
     * @throws InvalidArgumentException
     * @throws ErrorException
     */
    
    public function __construct($folderPath)
    {
        $this->folder = new SplFileInfo($folderPath);
        if(!$this->folder->isDir()) throw new
            InvalidArgumentException("The specified file path: "
                    . $folderPath . " isn't a directory.");
        if(!$this->folder->isReadable()) throw new
            ErrorException("The file path: " . $filePath . " isn't readable");
        
        $this->subfolders = new SplDoublyLinkedList(); // holding instances of Folder
        $this->files = new SplDoublyLinkedList(); //holding instances of Folder
        
        //now recursively adding the files and subfolders
        foreach (scandir($folderPath) as $key => $fileName)
        {
            if (!in_array($fileName ,array(".",".."))) 
            {
               $file = new SplFileInfo($folderPath . DIRECTORY_SEPARATOR . $fileName);
               if ($file->isDir()) 
               {
                   $folderToAdd = new Folder($file->getRealPath());
                   $this->subfolders->push($folderToAdd);
                   $this->size += $folderToAdd->getSize();
               } 
               else if($file->isFile())
               { 
                  $this->files->push(new File($file->getRealPath()));
                  $this->size += $file->getSize();
               }
            } 
        }
    }
    
    /**
     * The constructor gets the directory path of the folder
     * and does reverse Breadth First Search(BFS) by using and additional stack
     * 
     * @param string $folderPath the path to the desired folder
     * @param boolean $iterate - Used to prevent recursion
     * @throws InvalidArgumentException
     * @throws ErrorException
     */
    
    private function iterativeConstructor($folderPath, $iterate = true)
    {
        $this->folder = new SplFileInfo($folderPath);
        if(!$this->folder->isDir()) throw new
            InvalidArgumentException("The specified file path: "
                    . $folderPath . " isn't a directory.");
        if(!$this->folder->isReadable()) throw new
            ErrorException("The file path: " . $filePath . " isn't readable");
        $this->subfolders = new SplDoublyLinkedList(); // holding instances of Folder
        $this->files = new SplDoublyLinkedList(); //holding instances of Folder
        if(!$iterate) return; //If we dont break here, we will start a recursion
        
        /**
         * @var SplQueue<Folder>
         */
        $queue = new SplQueue();
        /**
         * @var SplStack<Folder>
         */
        
        $stack = new SplStack();
        /**
         * Holding references to the parent folder of the current folder
         * @var array<string>
         */
        $parent = array();
        $parent[$this->folder->getRealPath()] = NULL;
        $queue->push($this);
        while(!$queue->isEmpty())
        {
            $currentFolder = $queue->top(); //taking the next folder in line
            $queue->pop(); //popping the folder
            foreach (scandir($currentFolder->getAbsolutePath()) as $key => $fileName)
            {
                if (!in_array($fileName ,array(".",".."))) 
                {
                   $file = new SplFileInfo($currentFolder->getAbsolutePath() . DIRECTORY_SEPARATOR . $fileName);
                   if ($file->isDir()) 
                   {
                       $folderToAdd = new Folder($file->getRealPath(), false);
                       $parent[$folderToAdd->getAbsolutePath()] = $currentFolder;
                       $queue->push($folderToAdd);
                       $stack->push($folderToAdd);
                   }
                   else if($file->isFile())
                   { 
                      $currentFolder->addFile(new File($file->getRealPath()));
                   }
                }
            }
        }
        // adding the folders in reversed order - from bottom to top
        while(!$stack->isEmpty())
        {
            $currentFolder = $stack->top();
            $stack->pop();
            $parent[$currentFolder->getAbsolutePath()]->addSubfolder($currentFolder);
        }
        
    }
    
    /**
     * Gets the file group. The group ID is returned in numerical format.
     * 
     * @return int
     * @throw RuntimeException
     */
    public function getGroup()
    {
        return $this->folder->getGroup();
    }
    /**
     * Gets the file owner. The owner ID is returned in numerical format.
     *
     *  @return int
     * @throws RuntimeException
     */
    public function getOwner()
    {
        return $this->folder->getOwner();
    }
    /**
     * Returns the path to the folder, omitting the folder name and any trailing slash.
     * 
     * @return string
     */
    public function getPath()
    {
        return $this->folder->getPath();
    }
    /**
     * This method return the relative path to the folder
     * @return string
     */
    public function getRelativePath()
    {
        return $this->folder->getPathname();
    }
    /**
     * This method expands all symbolic links, resolves relative references and
     * returns the real path to the folder.
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->folder->getRealPath();
    }
    /**
     * Gets the value of $this->size which holds the size in bytes of the folder
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }
    /**
     * Gets the folder permissions for the folder.
     * @return int
     */
    public function getPermissions()
    {
        return $this->folder->getPerms();
    }
    /**
     * Returns all the files in the folder
     * @return SplDoublyLinkedList<File>
     */
    public function getFilesInFolder()
    {
        return $this->files;
    }
    /**
     * returns the name of the folder
     * 
     * @return string
     */
    public function getName()
    {
        return $this->folder->getFilename();
    }
    /*
     * Returns a list of the subfolders
     * @return DoublyLinkedList<Folder>
     */
    public function getSubfolders()
    {
        return $this->subfolders;
    }
    /**
     * Adds a folder the subfolders list
     * @param Folder $subfolder
     */
    public function addSubfolder(Folder $subfolder)
    {
        $this->size += $subfolder->getSize();
        return $this->subfolders->push($subfolder);
    }
    /**
     * Adds a file to the files list
     * @param File $file 
     */
    public function addFile(File $file)
    {
        $this->size += $file->getSize();
        return $this->files->push($file);
    }
}
