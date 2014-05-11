<?php
namespace MongoFilesystem;
use MongoFilesystem\File;
use MongoFilesystem\Folder;
use MongoFilesystem\MongoFile;
use MongoFilesystem\MongoFolder;
use MongoFilesystem\Exceptions\MongoFileNotFoundException;
use MongoFilesystem\Exceptions\MongoFolderNotFoundException;
use \MongoId;
use \MongoDB;
use \MongoGridFS;
use \MongoCollection;
use \SplStack;
use \SplQueue;
use \SplDoublyLinkedList;
use \InvalidArgumentException;
use \ErrorException;
use \DateTime;
use \ZipArchive;
use \ZipStream; //external class
/**
 * The API through which interactions with the DB are going to be made
 */
class MongoFilesystem
{
    const ROOT_FOLDER_NAME = "ROOT";
    /**
     * 
     * @var MongoDB
     */
    protected $db;
    /**
     * Hold the collections containing the folders
     * @var MongoCollection
     */
    protected $folderCollection;
    /**
     * Holds the GridFS instance
     * @param MongoGridFS
     */
    protected $gridFS;
    /**
     * Path to the configuration file for MongoDB in XML format
     * @var string
     */
    protected $configurationPath = "configuration.xml";
    /**
     * @var MongoId
     */
    protected $rootFolderID;
    /**
     * Constructor
     * @param MongoDB $mongoDB
     */
    /**
     * Name of the GridFS collection
     * @var string
     */
    protected $gridFSCollectionName;
    /*
     * @param MongoDB
     */
    public function __construct(MongoDB $mongoDB) {
        $this->db = $mongoDB;
        
        //making an instance of SimpleXMLElement
        $configuration = simplexml_load_file(__DIR__ . DIRECTORY_SEPARATOR . $this->configurationPath);
        $folderCollectionName = $configuration->folderCollection;
        $this->folderCollection = $this->db->selectCollection($folderCollectionName);
        $this->gridFSCollectionName = (string)$configuration->gridFS;
        $this->gridFS = $this->db->getGridFS((string)$configuration->gridFS);
        $this->assignRootFolder();
    }
    /**
     * Creates a root folder if it hasn't been created already
     * @return void
     */
    protected function assignRootFolder()
    {
        $metadata = array();
        $metadata["parentFolderID"] = NULL;
        $metadata["folderName"] = self::ROOT_FOLDER_NAME;
        $result = $this->folderCollection->findOne($metadata);
        if($result !== NULL)
        {
            $this->rootFolderID = $result["_id"];
            return;
        }
        //Root fodler doesn't exist so we create it
        $metadata["parentFolderID"] = NULL;
        $metadata["folderName"] = self::ROOT_FOLDER_NAME;
        $metadata["level"] = 0;
        $metadata["owner"] = 0;
        $metadata["group"] = 0;
        $metadata["permissions"] = 7777;
        $metadata["size"] = 0;
        $this->folderCollection->insert($metadata);
        $this->rootFolderID = $metadata["_id"];
    }

    /**
     * Recursively creates folder and subfolders
     * and uploads files inside them to MongoDB
     * 
     * @param \MongoFilesystem\Folder $folder
     * @param int $level
     * @param MongoId $parentID
     * @return void
     * @throws MongoException
     * @throws MongoCursorException
     * @throws MongoCursorTimeoutException
     */
    public function uploadRecursivelyFolder(Folder $folder, MongoId $parentID = NULL)
    {
       if($parentID === NULL) $parentID = $this->rootFolderID;
       //getting the level
       $criteria = array("_id" => $parentID);
       $parentFolder = $this->folderCollection->findOne($criteria, array("level" => true));
       $level = $parentFolder["level"] + 1;
       
       $metadata = array();
       $metadata["folderName"] = $folder->getName();
       $metadata["owner"] = $folder->getOwner();
       $metadata["group"] = $folder->getGroup();
       $metadata["size"] = $folder->getSize();
       $metadata["permissions"] = $folder->getPermissions();
       $metadata["parentFolderID"] = $parentID;
       $metadata["level"] = $level;
       //$metadata["_id"]; //will be added after the insert
       $nameToCheck = $metadata["folderName"];
       $counter = 1;
       while($this->folderWithNameExistsInFolder($nameToCheck, $metadata["parentFolderID"]))
       {
            $nameToCheck = $metadata["folderName"] . " (" . $counter . ")";
            ++$counter;
       }
       $metadata["folderName"] = $nameToCheck;
       $this->folderCollection->insert($metadata); //inserting the current folder
       foreach($folder->getFilesInFolder() as $currentFile)
       {
           $this->uploadFile($currentFile, $metadata["_id"], false);
       }
       //now recursively insert all the subfolders
       foreach($folder->getSubfolders() as $currentSubfolder)
       {
           $this->uploadRecursivelyFolder($currentSubfolder, $metadata["_id"]);
       }
    }
    /**
     * Iteratively creates folder and subfolders
     * and uploads files inside them to MongoDB
     * 
     * @param \MongoFilesystem\Folder $folder
     * @param MongoId $parentID
     * @return void
     * @throws MongoException
     * @throws MongoCursorException
     * @throws MongoCursorTimeoutException
     */
    public function uploadFolder(Folder $folder, MongoId $parentID = NULL)
    {
        if($parentID === NULL) $parentID = $this->rootFolderID;
        //getting the level
        $criteria = array("_id" => $parentID);
        $parentFolder = $this->folderCollection->findOne($criteria, array("level" => true));
        $level = $parentFolder["level"] + 1;
        /**
        * Will be used to map folders paths to their parent folder's MongoId
        * @var array<MongoId>
        */
       $parent = array();
       /**
        * Will be used to map folders paths to their depth level
        * @var array<int>
        */
       $depthLevel = array();
       /**
        * @var SplStack<Folder>
        */
       $stack = new SplStack(); //Holds Folder instances
       
       //pushing the first folder onto the stack
       $stack->push($folder);
       $parent[$folder->getAbsolutePath()] = $parentID; //we are using as key the absolute path to the folder
       $depthLevel[$folder->getAbsolutePath()] = $level; //we are using as key the absolute path to the folder
       while(!$stack->isEmpty())
       {
           $currentFolder = $stack->top(); //getting the element at the top
           $stack->pop(); //popping the top element from the stack
           
           $metadata = array();
           $metadata["folderName"] = $currentFolder->getName();
           $metadata["owner"] = $currentFolder->getOwner();
           $metadata["group"] = $currentFolder->getGroup();
           $metadata["size"] = 0; //the uploadFile method does the job
           $metadata["permissions"] = $currentFolder->getPermissions();
           $metadata["parentFolderID"] = $parent[$currentFolder->getAbsolutePath()];
           $metadata["level"] = $depthLevel[$currentFolder->getAbsolutePath()];
           //$metadata["_id"]; //will be added after the insert
           $counter = 1;
           $nameToCheck = $metadata["folderName"];
           while($this->folderWithNameExistsInFolder($nameToCheck, $metadata["parentFolderID"]))
           {
               $nameToCheck = $metadata["folderName"] . " (" . $counter . ")";
               ++$counter;
           }
           $metadata["folderName"] = $nameToCheck;
           $this->folderCollection->insert($metadata); //inserting the current folder
           //inserting all the files in the current folder into the GridFS
           foreach($currentFolder->getFilesInFolder() as $currentFile)
           {
                $this->uploadFile($currentFile, $metadata["_id"]);
           }
           //pushing the subfolders onto the stack
           foreach($currentFolder->getSubfolders() as $currentSubfolder)
           {
               $stack->push($currentSubfolder);
               $parent[$currentSubfolder->getAbsolutePath()] = $metadata["_id"];
               $depthLevel[$currentSubfolder->getAbsolutePath()] = $metadata["level"] + 1;
           }
       }
    }
    /**
     * Uploads the passed file to Mongo's GridFS
     * The third parameter should always be left to true, otherwise parent folders
     * sizes won't have their actual values. The third parameter is designed for
     * the uploadFolder method in order to avoid redundant queries
     * @param File $file - the file to be uploaded
     * @param MongoId $parentFolderID The ID of the folder the file's in
     * @param boolean $updateParentFolderSizes Whether to add the file's size to all parent folders' sizes.
     */
    public function uploadFile(File $file, MongoId $parentFolderID = NULL, $updateParentFolderSizes = true)
    {
        if($parentFolderID === NULL) $parentFolderID = $this->rootFolderID;
        $metadata = array();
        $metadata["fileName"] = $file->getName();
        $metadata["extension"] = $file->getExtension();
        $metadata["permissions"] = $file->getPermissions();
        $metadata["owner"] = $file->getOwner();
        $metadata["group"] = $file->getGroup();
        $metadata["lastModified"] = $file->getLastModifiedDate()->getTimeStamp(); //Unix timestamp
        $metadata["size"] = $file->getSize();
        $metadata["parentFolderID"] = $parentFolderID;
        $counter = 1;
        $nameToCheck = $metadata["fileName"];
        while($this->fileWithNameExistsInFolder($nameToCheck, $metadata["extension"], $parentFolderID))
        {
            $nameToCheck = $metadata["fileName"] . " (" . $counter . ")";
            ++$counter;
        }
        $metadata["fileName"] = $nameToCheck;
        $this->gridFS->storeFile($file->getAbsolutePath(),
                array("metadata" => $metadata, "filename" => $metadata["fileName"]));
        /*
         * The only reason this condition exists
         * is to reuse this method in the uploadFolder method
         */
        if($updateParentFolderSizes)
        {
            //now increment all the previous folders' sizes
            //with the size of the uploaded folder
            /**
             * @var MongoId
             */
            $currentParentFolderID = $parentFolderID;
            $reachedRoot = false;
            while(!$reachedRoot)
            {
                if($currentParentFolderID == $this->rootFolderID) $reachedRoot = true;
                $criteria = array("_id" => $currentParentFolderID);
                $rules = array("size" => $file->getSize());
                $this->folderCollection->update($criteria, array('$inc' => $rules));
                $criteria = array("_id" => $currentParentFolderID);
                $parentOfParent = $this->folderCollection->findOne($criteria, array("parentFolderID" => true));
                $currentParentFolderID = $parentOfParent["parentFolderID"];
            }
        }
    }
    /**
     * Returns the resource stream of the current file in the GridFS
     * @param MongoId $fileID
     * @return stream
     * @throws MongoFileNotFoundException
     */
    public function getFileResourceStream(MongoId $fileID)
    {
        $criteria = array("_id" => $fileID);
        /**
         * @var MongoGridfsFile
         */
        $file = $this->gridFS->findOne($criteria);
        if($file === NULL) throw new MongoFileNotFoundException($fileID);
        return $file->getResource();
    }
    /**
     * Returns an instance of MongoFile for the current file
     * @return MongoFile
     * @throws MongoFileNotFoundException
     */
    public function getFile(MongoId $fileID)
    {
        //get() returns an instance of MongoGridFSFile or NULL
        $gridFSFile = $this->gridFS->get($fileID);
        if($gridFSFile === NULL) throw new MongoFileNotFoundException($fileID);
        $metadata = array();
        $metadata["_id"] = $gridFSFile->file["_id"];
        $metadata["fileName"] = $gridFSFile->file["metadata"]["fileName"];
        $metadata["extension"] = $gridFSFile->file["metadata"]["extension"];
        $metadata["permissions"] = $gridFSFile->file["metadata"]["permissions"];
        $metadata["owner"] = $gridFSFile->file["metadata"]["owner"];
        $metadata["group"] = $gridFSFile->file["metadata"]["group"];
        $metadata["size"] = $gridFSFile->file["metadata"]["size"];
        $metadata["lastModified"] = $gridFSFile->file["metadata"]["lastModified"]; //Unix timestamp
        $metadata["parentFolderID"] = $gridFSFile->file["metadata"]["parentFolderID"];
        $metadata["checksum"] = $gridFSFile->file["md5"];
        $metadata["uploadDate"] = $gridFSFile->file["uploadDate"];
        
        unset($gridFSFile);//destroying the object. Its sie is ~3KB
        return new MongoFile($metadata);
    }
    /**
     * Returns an instance of MongoFolder for the current folder
     * Potential problem when trying to delete a folder with empty subfolders
     * (when passing false as second parameter).
     * Might lead to unnecessary files and folders stuck in the DB. TODO
     * @param MongoId
     * @param boolean
     * @return MongoFolder
     * @throws MongoFolderNotFoundException
     */
    public function getFolder(MongoId $folderID, $findSubfoldersAndFiles = true)
    {
        $criteria = array("_id" => $folderID);
        $folderAsArray = $this->folderCollection->findOne($criteria);
        if($folderAsArray === NULL) throw new MongoFolderNotFoundException($folderID);
        $folderAsArray["files"] = new SplDoublyLinkedList();
        $folderAsArray["subfolders"] = new SplDoublyLinkedList();
        //if false recursion will be stopped
        if($findSubfoldersAndFiles)
        {
            $folderAsArray["files"] = $this->getFolderFiles($folderID);
            $folderAsArray["subfolders"] = $this->getFolderSubfolders($folderID);
        }
        return new MongoFolder($folderAsArray);
    }
    /**
     * Gets the MongoId instance of the file
     * with specified name and parent folder
     * @param string $filename
     * @param string $extension
     * @param MongoId $parentFolderID
     * @return MongoId
     * @throws ErrorException
     */
    public function getFileIDByNameAndParentFolderID($filename, $extension, MongoId $parentFolderID)
    {
        $criteria = array("metadata.fileName" => $filename, "metadata.parentFolderID" => $parentFolderID, "metadata.extension" => $extension);
        /**
         * @var MongoGridFSFile|NULL
         */
        $file = $this->gridFS->findOne($criteria, array("_id" => true));
        if($file === NULL) throw new
            ErrorException("File with name: " . $filename . ", extension: ".$extension." and parentFolderID: " . $parentFolderID . " doesn't exist.");
        return $file->file["_id"];
    }

    /** 
     * Returns a MongFile instance of the file
     * @param string filename
     * @param string $extension
     * @param MongoId $parentFolderID
     * @return MongoFile
     */
    public function getFileByNameAndParentFolderID($filename, $extension, MongoId $parentFolderID)
    {
        return $this->getFile($this->getFileIDByNameAndParentFolderID($filename, $extension, $parentFolderID));
    }
    /**
     * Gets a folder's MongoId instance by its name and parent folder
     * @param string $folderName
     * @param MongoId $parentFolderID
     * @return MongoId
     */
    public function getFolderIDByNameAndParentFolderID($folderName, MongoId $parentFolderID)
    {
        $resultArray = $this->folderCollection->findOne(
                array("parentFolderID" => $parentFolderID, 'folderName' => $folderName));
        if($resultArray == null) throw new ErrorException("Folder with such criteria doesn't exist");
        return $resultArray["_id"];
    }
    /**
     * Gets a folder by its name and parent folder
     * @param string $folderName
     * @param MongoId $parentFolderID
     * @return MongoFolder
     */
    public function getFolderByNameAndParentFolderID($folderName, MongoId $parentFolderID)
    {
        return $this->getFolder($this->getFolderIDByNameAndParentFolderID($folderName, $parentFolderID));
    }
    /**
     * Returns the specified file's MongoId in the DB
     * @param string $filePath
     * @param string $delimiter
     * @return MongoId
     * @throws ErrorException
     */
    public function getFileIDByPath($filePath, $delimiter = '/')
    {
        $filePath = trim($filePath, $delimiter);
        $i = strlen($filePath)-1;
        while($i >= 0 && $filePath[$i] != $delimiter) --$i;
        //now either at $i there is a delimiter or $i is <= 0
        /**
         * @var MongoId
         */
        $parentFolderID = $this->rootFolderID;
        if($i-1 >= 0)
        {
            $folderPath = substr($filePath, 0, $i);
            $parentFolderID = $this->getFolderIDByPath($folderPath, $delimiter);
        }
        //now we got the parentFolderID of the file
        //extract filename and file's extension
        $fileAndExtension = $this->getFilenameAndExtensionFromFilePath($filePath, $delimiter);
        $fileName = $fileAndExtension[0];
        $extension = $fileAndExtension[1];
        //extracted
        
        //check if file with name and parentFolderID exists
        $criteria = array("metadata.parentFolderID" => $parentFolderID, "metadata.fileName" => $fileName, "metadata.extension" => $extension);
        /**
         * @var MongoGridFSFile|NULL
         */
        $file = $this->gridFS->findOne($criteria, array("_id" => true));
        if($file === NULL) throw new
            ErrorException("File with name: " . $fileName . '.' . $extension . ' and parentFolderID: ' . $parentFolderID . ' does not exist.');
        return $file->file["_id"];
    }
    /**
     * Returns a MongoFile instance of the file
     * @param string $filePath
     * @param string $delimiter
     * @return MongoFile
     */
    public function getFileByPath($filePath, $delimiter = '/')
    {
        return $this->getFile($this->getFileIDByPath($filePath, $delimiter));
    }
    /**
     * Gets the folder ID by its path in the DB
     * @param string $pathToFolder
     * @param string $delimiter
     * @return MongoId
     */
    public function getFolderIDByPath($pathToFolder, $delimiter = "/")
    {
        $pathToFolder = trim($pathToFolder, '/');
        $segments = explode($delimiter, $pathToFolder);
        /**
         * @var MongoId
         */
        $currentFolderID = $this->rootFolderID;
        foreach($segments as $foldername)
        {
            $criteria = array("folderName" => $foldername, "parentFolderID" => $currentFolderID);
            $folder = $this->folderCollection->findOne($criteria, array("_id" => true));
            if($folder === NULL) throw new
                ErrorException("Folder with name: " . $foldername . " and parentFolderID: " . $currentFolderID . " doesn't exist.");
            $currentFolderID = $folder["_id"];
        }
        return $currentFolderID;
    }
    /**
     * Returns a MongoFolder instance
     * @param string $pathToFolder
     * @param string $delimiter
     * @return MongoFolder
     */
    public function getFolderByPath($pathToFolder, $delimiter = '/')
    {
        return $this->getFolder($this->getFolderIDByPath($pathToFolder, $delimiter));
    }
    /**
     * Returns a list of the files in the folder
     * @param MongoId $folderID
     * @return SplDoublyLinkedList<MongoFile>
     */
    public function getFolderFiles(MongoId $folderID)
    {
        /**
         * @var SplDoublyLinkedList<MongoFile>
         */
        $filesList = new SplDoublyLinkedList();
        
        $criteria = array("metadata.parentFolderID" => $folderID);
        /**
        * @var MongoGridFSCursor
        */
        $files = $this->gridFS->find($criteria, array("_id" => true)); //MongoCursor instance
        foreach($files as $file)
        {
            $mongoFile = $this->getFile($file->file['_id']);
            $filesList->push($mongoFile);
        }
        return $filesList;
    }
    /**
     * Gets the folder's subfolders as a list
     * @param MongoId $folderID
     * @return SplDoublyLinkedList<MongoFolder>
     */
    public function getFolderSubfolders(MongoId $folderID)
    {
        $subfoldersList = new SplDoublyLinkedList();
        /**
        * @var MongoCursor
        */
        $subfolders = $this->folderCollection->find(
            array('parentFolderID' => $folderID)); //MongoCursor instance
        foreach($subfolders as $subfolder)
        {
            //avoiding redundant queries by not calling recursively ::getFolder()
            $subfolder["files"] = $this->getFolderFiles($subfolder["_id"]);
            $subfolder["subfolders"] = $this->getFolderSubfolders($subfolder["_id"]);
            $mongoFolder = new MongoFolder($subfolder);
            $subfoldersList->push($mongoFolder);
        }
        return $subfoldersList;
    }
    /**
     * Deletes the passed MongoFile from the DB
     * @param MongoFile
     * @return void
     */
    public function deleteFile(MongoFile $file)
    {
        $this->gridFS->delete($file->getID());//deleting the file from the bucket
        //now decrement all the previous folders' sizes
        //with the size of the uploaded folder
        /**
        * @var MongoId
        */
        $currentParentFolderID = $file->getParentFolderID();
        $reachedRoot = false;
        while(!$reachedRoot)
        {
            if($currentParentFolderID == $this->rootFolderID) $reachedRoot = true;
            $criteria = array("_id" => $currentParentFolderID);
            $rules = array("size" => -$file->getSize());
            $this->folderCollection->update($criteria, array('$inc' => $rules));
            $criteria = array("_id" => $currentParentFolderID);
            $parentOfParent = $this->folderCollection->findOne($criteria, array("parentFolderID" => true));
            $currentParentFolderID = $parentOfParent["parentFolderID"];
        }
    }
    /**
     * Deletes the specified MongoFolder
     * @param MongoFolder
     * @return void
     */
    public function deleteFolder(MongoFolder $folder)
    {
        if($folder->getID() == $this->rootFolderID) throw new InvalidArgumentException("Can't delete root folder");
        foreach($folder->getFiles() as $file)
        {
            $this->deleteFile($file);
        }
        foreach($folder->getSubfolders() as $subfolder)
        {
            $this->deleteFolder($subfolder);
        }
        //removing the current folder from the folder collection
        $this->folderCollection->remove(array("_id" => $folder->getID()), array('justOne' => true));
    }
    /**
     * Makes a MongoFolder instance of the parent folder 
     * @param MongoFile|MongoFolder $fileOrFolder
     * @return MongoFolder
     */
    public function getParentFolder($fileOrFolder)
    {
        return $this->getFolder($fileOrFolder->getParentFolderID());
    }
    /**
     * Returuns the absolute path of the folder in the DB
     * @param MongoId $folderID
     * @param string
     * @param string
     * @return SplDoublyLinkedList<string>|string
     */
    public function getFolderPath(MongoId $folderID, $returnAsString = false, $delimiter = '/')
    {
        $stack = new SplStack();
        $currentFolderID = $folderID;
        $currentFolderName;
        while($currentFolderID != $this->rootFolderID)
        {
            $res = $this->folderCollection->findOne(array("_id" => $currentFolderID),
                array("parentFolderID" => true, "folderName" => true));
            $currentFolderID = $res["parentFolderID"];
            $currentFolderName = $res["folderName"];
            $stack->push($currentFolderName);
        }
        /**
         * @var SplDoublyLinkedList<string>
         */
        $output = new SplDoublyLinkedList();
        while(!$stack->isEmpty())
        {
            $currentFolderName = $stack->top();
            $stack->pop();
            $output->push($currentFolderName);
        }
        if(!$returnAsString) return $output;
        $outputAsString = "";
        foreach($output as $currentSegment)
        {
            $outputAsString .= $delimiter . $currentSegment;
        }
        $outputAsString = trim($outputAsString, $delimiter);
        return $outputAsString;
    }
    /**
     * Returns the absolute path of the file in the DB
     * @param MongoFile $file
     * @param boolean
     * @param char
     * @return SplDoublyLinkedList<string>|string
     */
    public function getFilePath(MongoFile $file, $returnAsString = false, $delimiter = '/')
    {
        $path = $this->getFolderPath($file->getParentFolderID(), false, $delimiter);
        $path->push($file->getFilename() . '.' . $file->getExtension());
        if(!$returnAsString) return $path;
        $outputAsString = "";
        foreach($path as $currentSegment)
        {
            $outputAsString .= $delimiter . $currentSegment;
        }
        $outputAsString = trim($outputAsString, $delimiter);
        return $outputAsString;
    }
    /**
     * Checks if a folder with a specified path exists in the DB
     * @param string $folderPath
     * @param string $delimiter
     * @return boolean
     */
    public function folderWithPathExists($folderPath, $delimiter = '/')
    {
        $folderPath = trim($folderPath, $delimiter);
        $segments = explode($delimiter, $folderPath);
        /**
         * @var MongoId
         */
        $currentFolderID = $this->rootFolderID;
        foreach($segments as $foldername)
        {
            $criteria = array("folderName" => $foldername, "parentFolderID" => $currentFolderID);
            $folder = $this->folderCollection->findOne($criteria, array("_id" => true));
            if($folder === NULL) return false;
            $currentFolderID = $folder["_id"];
        }
        return true;
    }
    /**
     * Checks if a folder with specified ID exists
     * @param MongoId $folderID
     * @return boolean
     */
    public function folderWithIDExists(MongoId $folderID)
    {
        $criteria = array("_id" => $folderID);
        $folder = $this->folderCollection->findOne($criteria, array("_id" => true));
        return $folder !== NULL;
    }
    /**
     * Checks if a file with a specified filepath exists in the DB
     * @param string The path of the file in the DB
     * @param string Delimiter seperating the path segments
     * @return boolean
     */
    public function fileWithPathExists($filePath, $delimiter = '/')
    {
        $filePath = trim($filePath, $delimiter);
        $i = strlen($filePath)-1;
        while($i >= 0 && $filePath[$i] != $delimiter) --$i;
        //now either at $i there is a delimiter or $i is <= 0
        /**
         * @var MongoId
         */
        $parentFolderID = $this->rootFolderID;
        if($i-1 >= 0)
        {
            $folderPath = substr($filePath, 0, $i);
            if(!$this->folderWithPathExists($folderPath, $delimiter)) return false;
            $parentFolderID = $this->getFolderIDByPath($folderPath, $delimiter);
        }
        //now we got the parentFolderID of the file
        //extract filename and file's extension
        $fileAndExtension = $this->getFilenameAndExtensionFromFilePath($filePath, $delimiter);
        $fileName = $fileAndExtension[0];
        $extension = $fileAndExtension[1];
        //extracted
        
        //check if file with name and parentFolderID exists
        $criteria = array("metadata.parentFolderID" => $parentFolderID, "metadata.fileName" => $fileName, "metadata.extension" => $extension);
        /**
         * @var MongoGridFSFile|NULL
         */
        $file = $this->gridFS->findOne($criteria, array("_id" => true));
        return $file !== NULL;
    }
    /**
     * Checks if a file with the given ID exists in the GridFS
     * @param \MongoFilesystem\MongoID $fileID
     * @return boolean
     */
    public function fileWithIDExists(MongoID $fileID)
    {
        $criteria = array("_id" => $fileID);
        /**
         * @var MongoGridFSFile|NULL
         */
        $file = $this->gridFS->findOne($criteria, array("_id" => true));
        return $file !== NULL;
    }
    /*
     * Checks if a file with the specified metadata already exists
     * @param string $filename
     * @param string $extension
     * @param MongoId $folderID
     * @return  boolean
     */
    public function fileWithNameExistsInFolder($filename, $extension, MongoId $folderID)
    {
        /**
         * @var NULL|MongoGridFSFile
         */
        $mongoGridFSFile = $this->gridFS->findOne(array("metadata.fileName" => $filename,
            "metadata.extension" => $extension, 'metadata.parentFolderID' => $folderID));
        return $mongoGridFSFile !== NULL;
    }
    /**
     * Checks if a folder with the specified metadata already exists
     * @param string $foldername
     * @param MongoId $parentID
     * @return boolean
     */
    public function folderWithNameExistsInFolder($foldername, MongoId $parentID)
    {
        /**
         * @var NULL|array
         */
        $result = $this->folderCollection->findOne(array("parentFolderID" => $parentID,
            'folderName' => $foldername));
        return $result !== NULL;
    }
    /**
     * Returns the file as a string
     * @param MongoFile
     * @return string the File contents
     */
    public function downloadFile(MongoFile $file)
    {
        $content = '';
        $stream = $this->getFileResourceStream($file->getID());
        while(!feof($stream))
        {
            $content .= fread($stream, 8192);
        }
        fclose($stream);
        return $content;
    }
    /**
     * Downloads the file to a directory on the local file system
     * @param MongoFile $file
     * @param string $destinationDirectory
     * @return void
     */
    public function downloadFileInFolder(MongoFile $file, $destinationDirectory)
    {
        $folder = new Folder($destinationDirectory);
        $path = $folder->getAbsolutePath() . '/' . $file->getFilename() . '.' . $file->getExtension();
        $path = trim($path, '.');
        $handle = fopen($path, 'w'); //creating the file
        fclose($handle);
        $this->downloadFileInFile($file, $path);
    }
    /**
     * Downloads the file to the specified file in the local filesystem
     * @param MongoFile The file to download
     * @param string The path the file to be downloaded into
     */
    public function downloadFileInFile(MongoFile $file, $destinationPath)
    {
        $destinationFile = new File($destinationPath);
        $stream = $this->getFileResourceStream($file->getID());
        $path = $destinationFile->getAbsolutePath();
        $handler = fopen($path, 'a');
        while(!feof($stream))
        {
            fwrite($handler, fread($stream, 8192));
        }
        fclose($handler);
        fclose($stream);
    }
    /**
     * Outputs the specified file to the client
     * @param MongoFile $file The file to output
     * @return void
     */
    public function downloadAndOutputFile(MongoFile $file)
    {
        $stream = $this->getFileResourceStream($file->getID()); 
        //and outputing the file
        //now setting the proper headers for the output
        //http://www.richnetapps.com/the-right-way-to-handle-file-downloads-in-php/ Documentation
        set_time_limit(0);
        header('Content-disposition: attachment; filename="' . $file->getFilename() . '"');
        header('Content-Type: application/octet-stream');
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        while (!feof($stream)) {
            echo fread($stream, 8192);
            ob_flush();
            flush();
        }
        fclose($stream);
    }
    /**
     * Downloads a folder as a zip to a specified file in the local filesystem
     * Only dependency is the zip extension
     * Might lead to timeouts and insufficient memory
     * because it downloads the files and zips them
     * @param \MongoFilesystem\MongoFolder $folder Which folder to download
     * @param string $destinationFilePath The location where the zip will be downloaded into
     * @throws ErrorException
     * @return void
     */
    public function downloadFolderInFile(MongoFolder $folder, $destinationFilePath)
    {
        $destinationFile = new File($destinationFilePath);
        $zipPath = $destinationFile->getAbsolutePath();
        $zipPath = trim($zipPath, '.');
        /*
         * @var DateTime
         */
        $currentDate = new DateTime();
        $zip = new ZipArchive;
        $res = $zip->open($zipPath, ZipArchive::CREATE); //creating the zip
        if($res === false) throw new ErrorException("Couldn't create zip archive");
        $zip->setArchiveComment("Archive of folder " . $this->getFolderPath($folder->getID(), true) . " on " . $currentDate->format('Y-m-d H:i:s'));
        //Now get all the files in the folder and its subfolders
        /**
         * all the folders to be checked for files
         * @var SplQueue<MongoFolder>
         */
        $folderQueue = new SplQueue();
        /**
         * all the files to be put in the archive
         * @var SplQueue<MongoFile>
         */
        $fileQueue = new SplQueue();
        /**
         * Temporary files to delete
         * @var SplQueue<string>
         */
        $toDeleteFileQueue = new SplQueue();
        $folderQueue->push($folder); //pushing the passed as paramater folder to be checked first
        while(!$folderQueue->isEmpty())
        {
            /**
             * @var MongoFolder
             */
            $currentFolder = $folderQueue->top();
            $folderQueue->pop();
            foreach($currentFolder->getFiles() as $file)
            {
                $fileQueue->push($file);
            }
            foreach($currentFolder->getSubfolders() as $subfolder)
            {
                $folderQueue->push($subfolder);
            }
        }
        //all the files are pushed on the queue, now we gotta put them in the archive
        while(!$fileQueue->isEmpty())
        {
            /**
             * @var MongoFile
             */
            $currentFile = $fileQueue->top();
            $fileQueue->pop();
            
            $currentTemporaryName = tempnam(sys_get_temp_dir(), 'file');//temp file for the current file
            if($currentTemporaryName === false) throw new ErrorException("Couldn't create temporary file");
            $this->downloadFileInFile($currentFile, $currentTemporaryName);//downloading it to the temp file
            //Generate file path
            $path = $currentFile->getFilename() . '.' . $currentFile->getExtension();
            $currentFolderID = $currentFile->getParentFolderID();
            $currentFolderName;
            while($currentFolderID != $folder->getID())
            {
                $res = $this->folderCollection->findOne(array("_id" => $currentFolderID),
                        array("parentFolderID" => true, "folderName" => true));
                $currentFolderID = $res["parentFolderID"];
                $currentFolderName = $res["folderName"];
                $path = $currentFolderName . '/' . $path;//we imitate a stack
            }
            $path = trim($path, '/');
            //File path generated
            $zip->addFile($currentTemporaryName, $path);
            $toDeleteFileQueue->push($currentTemporaryName);
        }
        $zip->close();
        //got them
        /*
         * now we can delete all the temp files
         * was forced to put them in a queue because apparently
         * if I delete them before I close the zip
         * nothing will be saved on the zip
         */
        while(!$toDeleteFileQueue->isEmpty())
        {
            $tempFile = $toDeleteFileQueue->top();
            $toDeleteFileQueue->pop();
            unlink($tempFile);
        }
    }
    /**
     * Downloads the folder as a zip to a specified directory
     * @param \MongoFilesystem\MongoFoler $folder
     * @param string $destinationPath The directory to be downloaded to
     * @return void
     */
    public function downloadFolderInFolder(MongoFolder $folder, $destinationDirectory)
    {
        $destinationFolder = new Folder($destinationDirectory);
        $zipPath = $destinationFolder->getAbsolutePath() . '/' . $folder->getFoldername() . '.' . 'zip';
        $handle = fopen($zipPath, 'w'); //creating the file
        fclose($handle);
        $this->downloadFolderInFile($folder, $zipPath);
    }
    /**
     * Choose how to create and output the zip
     * Use with caution streaming as it is consuming excessive amounts of RAM = max size of file to download
     * @param MongoFolder $folder
     * @param boolean $useStreaming Whether or not to directly output the zip while creating it
     */
    public function downloadAndOutputFolder(MongoFolder $folder, $useStreaming = false)
    {
        if($useStreaming) $this->downloadAndOutputFolderWithStream($folder);
        else $this->downloadAndOutputFolderWithoutStream($folder);
    }
    /**
     * Depends on libraries/ZipStream class
     * Uses RAM = the max size of file to zip
     * Doesn't download any of the files nor creates the zip on the server
     * And avoids timeouts by outputting data directly to the client 
     * @param MongoFolder $folder
     * @return void
     */
    protected function downloadAndOutputFolderWithStream(MongoFolder $folder)
    {
        ini_set('max_execution_time', 6000);
        ini_set('memory_limit', 600);
        /*
         * @var DateTime
         */
        $currentDate = new DateTime();
        $zip = new ZipStream($folder->getFoldername());
        $zip->setComment("Archive of folder " . $this->getFolderPath($folder->getID(), true) . " on " . $currentDate->format('Y-m-d H:i:s'));
        //get all the folders to traverse and all the files to archive
        /**
         * @var SplQueue<MongoFolder>
         */
        $folderQueue = new SplQueue();
        /**
         * @var SplQueue<MongoFile>
         */
        $fileQueue = new SplQueue();
        $folderQueue->push($folder);
        while(!$folderQueue->isEmpty())
        {
            /**
             * @var MongoFolder
             */
            $currentFolder = $folderQueue->top();
            $folderQueue->pop();
            foreach($currentFolder->getFiles() as $currentFile)
            {
                //Generate file path
                $path = $currentFile->getFilename() . '.' . $currentFile->getExtension();
                $currentFolderID = $currentFile->getParentFolderID();
                $currentFolderName;
                while($currentFolderID != $folder->getID())
                {
                    $res = $this->folderCollection->findOne(array("_id" => $currentFolderID),
                            array("parentFolderID" => true, "folderName" => true));
                    $currentFolderID = $res["parentFolderID"];
                    $currentFolderName = $res["folderName"];
                    $path = $currentFolderName . '/' . $path;//we imitate a stack
                }
                $path = trim($path, '/');
                //File path generated
                $stream = $this->getFileResourceStream($currentFile->getID());
                $zip->addLargeFile($stream, $path);
                fclose($stream);
            }
            foreach($currentFolder->getSubfolders() as $subfolder)
            {
                $folderQueue->push($subfolder);
            }
        }
        //got them
        $zip->finalize();
    }
    /**
     * Downloads the specified folder to a temporary file
     * outputs the file to the client and deletes the file
     * @param \MongoFilesystem\MongoFolder $folder Which folder to output
     * @throws ErrorException
     * @return void
     */
    protected function downloadAndOutputFolderWithoutStream(MongoFolder $folder)
    {
        $temp_file = tempnam(sys_get_temp_dir(), "zip"); //the temp file where we will store the zip file
        if($temp_file === false) throw new ErrorException("Couldn't create temporary file");
        $this->downloadFolderInFile($folder, $temp_file);
        //now just output the archive
        $stream = fopen($temp_file, 'r');
        //now setting the proper headers for the output
        //http://www.richnetapps.com/the-right-way-to-handle-file-downloads-in-php/ Documentation
        set_time_limit(0);
        header('Content-disposition: attachment; filename="' . $folder->getFoldername() . '.zip"');
        header('Content-Type: application/octet-stream');
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        while (!feof($stream)) {
            echo fread($stream, 8192);
            ob_flush();
            flush();
        }
        fclose($stream);
        //now delete the temp file
        unlink($temp_file);
    }
    /**
     * Extracts the filename and extension of a file from its filepath
     * returns an array whose first element is the file name
     * and second element is the extension
     * @param string $filePath
     * @param string $delimiter
     * @return array
     */
    protected function getFilenameAndExtensionFromFilePath($filePath, $delimiter = '/')
    {
        $filePath = trim($filePath, $delimiter);
        $i = strlen($filePath)-1;
        while($i >= 0 && $filePath[$i] != $delimiter) --$i;
        //now either at $i there is a delimiter or $i is <= 0
        //extract filename and file's extension
        $i = ($i > 0 ? $i : 0);
        if($filePath[$i] == $delimiter) ++$i; //if there is a delimiter at that position
        $fileNameAndExtension = substr($filePath, $i);
        $segments = explode('.', $fileNameAndExtension);
        $fileName = "";
        $i = 0;
        do{
            $fileName .= $segments[$i] . '.';
            ++$i;
        } while($i < count($segments)-1);
        $fileName = trim($fileName, '.');
        $extension = "";
        if(count($segments) > 1) $extension = $segments[count($segments)-1];
        //extracted
        return array($fileName, $extension);
    }
    /**
     * Checks if a folder with a certain name and given parentFolderID exists
     * @param string $foldername
     * @param MongoId $parentFolderID
     * @return boolean
     */
    public function folderWithNameAndParentFolderIDExists($foldername, MongoId $parentFolderID)
    {
        $criteria = array("folderName" => $foldername, "parentFolderID: " => $parentFolderID);
        /**
         * @var array|NULL
         */
        $folder = $this->folderCollection->findOne($criteria, array("_id" => true));
        return $folder !== NULL;
    }
    /**
     * Creates a folder with the given folder path
     * @param stirng $folderPath
     * @param string $delimiter Delimiter separating the folder names
     * @return void
     */
    public function createFolder($folderPath, $delimiter = '/')
    {
        $folderPath = trim($folderPath, $delimiter);
        $segments = explode($delimiter, $folderPath);
        $currentParentFolderID = $this->rootFolderID;
        foreach($segments as $folder)
        {
            if(!$this->folderWithNameAndParentFolderIDExists($folder, $currentParentFolderID))
            {
                $criteria = array("_id" => $currentParentFolderID);
                $parentFolder = $this->folderCollection->findOne($criteria);
                $metadata = array();
                $metadata["parentFolderID"] = $currentParentFolderID;
                $metadata["folderName"] = $folder;
                $metadata["level"] = $parentFolder["level"] + 1;
                $metadata["owner"] = $parentFolder["owner"];
                $metadata["group"] = $parentFolder["group"];
                $metadata["permissions"] = $parentFolder["permissions"];
                $metadata["size"] = 0;
                $this->folderCollection->insert($metadata);
                $currentParentFolderID = $metadata["_id"];
            }
            else
            {
                $currentParentFolderID = $this->getFolderIDByNameAndParentFolderID($folder, $currentParentFolderID);
            }
        }
    }
    /**
     * Returns the parent folder's ID of the current folder
     * @param MongoId $folderID
     * @return MongoId
     */
    public function getFolderParentFolderID(MongoId $folderID)
    {
        $criteria = array("_id" => $folderID);
        $parentFolder = $this->folderCollection->findOne($criteria, array("parentFolderID" => true));
        return $parentFolder["parentFolderID"];
    }
    /**
     * Changes the folderName of the passed folder
     * @param MongoId $folderID
     * @param string $newName
     * @throws ErrorException
     */
    public function renameFolder(MongoId $folderID, $newName)
    {
        $newName = trim($newName);
        if($newName == '') throw new ErrorException('Folder name cannot be empty');
        if($folderID == $this->rootFolderID) throw new ErrorException("Can't rename the root folder");
        $parentID = $this->getFolderParentFolderID($folderID);
        if($this->folderWithNameExistsInFolder($newName, $parentID))
        {
            throw new ErrorException("Folder with name " . $newName . " and parentFolderID: " . $parentID . " already exists.");
        }
        //now we can update
        $criteria = array("_id" => $folderID);
        $rules = array("folderName" => $newName);
        $this->folderCollection->update($criteria, array('$set' => $rules));
    }
    /**
     * Chenges the file's name to the specified
     * @param MongoId $fileID
     * @param string $newName
     * @param string $extension
     * @throws MongoFileNotFoundException
     * @throes ErrorException
     * @return void
     */
    public function renameFile(MongoId $fileID, $newName, $extension)
    {
        $newName = trim($newName);
        if($newName == '') throw new ErrorException('File name cannot be empty');
        if($this->fileWithNameExistsInFolder($newName, $extension, $fileID))
                throw new MongoFileNotFoundException($fileID, $newName, $extension);
        //we can update now
        //We need to use the MongoCollection API on the fs.files collection
        /**
         * @var MongoCOllection
         */
        $gridFSCollection = $this->db->selectCollection($this->gridFSCollectionName . ".files");
        $criteria = array("_id" => $fileID);
        $rules = array("filename" => $newName, "metadata.fileName" => $newName, "metadata.extension" => $extension);
        $gridFSCollection->update($criteria, array('$set' => $rules));
    }
    /**
     * Changes the parentFolderID to the specified
     * @param MongoId $folderIDtoMove
     * @param MongoId $folderIDToPutIn
     * @throws ErrorException
     * @return void
     */
    public function moveFolderInFolder(MongoId $folderIDtoMove, MongoId $folderIDToPutIn)
    {
        if(!$this->folderWithIDExists($folderIDtoMove) || !$this->folderWithIDExists($folderIDToPutIn))
            throw new ErrorException("Folder with the passed ID doesn't exist.");
        $criteria = array("_id" => $folderIDtoMove);
        $rules = array("parentFolderID" => $folderIDToPutIn);
        $this->folderCollection->update($criteria, array('$set' => $rules));
    }
    /**
     * Moves a file into a different folder
     * By changing the parentFolderID
     * @param MongoId $fileID
     * @param MongoId $parentFolderID
     * @throws ErrorException
     * @return void
     */
    public function moveFileInFolder(MongoId $fileID, MongoId $parentFolderID)
    {
        if(!$this->fileWithIDExists($fileID))
            throw new ErrorException("File with ID: " . $fileID . " doesn't exist.");
        if(!$this->folderWithIDExists($parentFolderID))
            throw new ErrorException("Folder with ID: " . $parentFolderID . " doesn't exist.");
        $criteria = array("_id" => $fileID);
        $rules = array("metadata.parentFolderID" => $parentFolderID);
        $gridFSCollection = $this->db->selectCollection($this->gridFSCollectionName . '.files');
        $gridFSCollection->update($criteria, array('$set' => $rules));
    }
    /**
     * Checks if a file in the GridsFS and a file
     * in the local filesystem are identical
     * @param MongoId $fileInGridFS
     * @param \MongoFilesystem\File $fileInLocalFS
     * @return boolean
     */
    public function filesAreIdentical(MongoId $fileInGridFS, File $fileInLocalFS)
    {
        /*
         * First we check their sizes
         * and if they aren't equal
         * we open read streams to both files
         * and begin comparing them byte by byte
         */
        /**
         * @var MongoFile
         */
        $fileInGridFS = $this->getFile($fileInGridFS);
        if($fileInGridFS->getSize() != $fileInLocalFS->getSize()) return false;
        $fileStream = $this->getFileResourceStream($fileInGridFS->getID());
        $fileStream2 = fopen($fileInLocalFS->getAbsolutePath(), 'rb');
        $equal = true;
        $bytes = 8192;
        while(!feof($fileStream) && !feof($fileStream2))
        {
            if(fread($fileStream, $bytes) !== fread($fileStream2, $bytes))
            {
                $equal = false;
                break;
            }
        }
        if(feof($fileStream) != feof($fileStream2))
            $equal = false;
        fclose($fileStream);
        fclose($fileStream2);
        return $equal;
    }
    /**
     * The update funciton creates and uploads files/folders that do not exist
     * and replaces files that already exist and are modified.
     * @param MongoId $folderToUpdate The folder ID to update
     * @param Folder $folderToBeUpdatedWith The folder to update with
     * @return void
     */
    public function updateFolder(MongoId $folderToUpdate, Folder $folderToBeUpdatedWith)
    {
        //handling files
        foreach($folderToBeUpdatedWith->getFilesInFolder() as $file)
        {
            if($this->fileWithNameExistsInFolder($file->getName(), $file->getExtension(), $folderToUpdate))
            {
                $fileInDB = $this->getFileIDByNameAndParentFolderID($file->getName(), $file->getExtension(), $folderToUpdate);
                if($this->filesAreIdentical($fileInDB, $file))
                        continue; //we skip this file as it is identical to the one in the DB
                /**
                 * @var MongoId
                 */
                $this->updateFile($fileInDB, $file);
            }
            else
            {
                $this->uploadFile($file, $folderToUpdate);
            }
        }
        //handling subfolders
        foreach($folderToBeUpdatedWith->getSubfolders() as $subfolder)
        {
            if(!$this->folderWithNameExistsInFolder($subfolder->getName(), $folderToUpdate))
            {
                $this->uploadFolder($subfolder, $folderToUpdate);
            }
            else
            {
                $folderID = $this->getFolderIDByNameAndParentFolderID($subfolder->getName(), $folderToUpdate);
                $this->updateFolder($folderID, $subfolder);
            }
        }
        
    }
    /**
     * Copies the metadata of the given file ID,
     * uploads the new file with the metadata
     * and deletes the old one
     * @param MongoId $fileInDB
     * @param \MongoFilesystem\File $fileToReplaceWith
     * @throws ErrorException
     */
    public function updateFile(MongoId $fileInDB, File $fileToReplaceWith)
    {
        /**
         * @var MongoGridFSFile|NULL
         */
        $criteria = array("_id" => $fileInDB);
        $originalFile = $this->gridFS->findOne($criteria);
        if($originalFile === NULL) throw new
            ErrorException("File with ID: " . $fileInDB . " doesn't exist.");
        $sizeDifference = (int)$fileToReplaceWith->getSize() - (int)$originalFile->file["metadata"]["size"];
        $metadata["fileName"] = $originalFile->file["metadata"]["fileName"];
        $metadata["extension"] = $originalFile->file["metadata"]["extension"];
        $metadata["permissions"] = $originalFile->file["metadata"]["permissions"];
        $metadata["owner"] = $originalFile->file["metadata"]["owner"];
        $metadata["group"] = $originalFile->file["metadata"]["group"];
        $metadata["size"] = $fileToReplaceWith->getSize();
        $metadata["lastModified"] = $fileToReplaceWith->getLastModifiedDate()->getTimeStamp(); //Unix timestamp
        $metadata["parentFolderID"] = $originalFile->file["metadata"]["parentFolderID"];
        $newFileID = $this->gridFS->storeFile($fileToReplaceWith->getAbsolutePath(),
                array("metadata" => $metadata, "filename" => $metadata["fileName"]));
        //now delete the original file
        $this->gridFS->delete($fileInDB);
        //now we have to update parent folders' sizes
        $currentParentFolderID = $metadata["parentFolderID"];
        $reachedRoot = false;
        while(!$reachedRoot)
        {
            if($currentParentFolderID == $this->rootFolderID)
                $reachedRoot = true;
            $criteria = array("_id" => $currentParentFolderID);
            $rules = array("size" => $sizeDifference);
            $this->folderCollection->update($criteria, array('$inc' => $rules));
            $folder = $this->folderCollection->findOne($criteria, array("parentFolderID" => true));
            $currentParentFolderID = $folder["parentFolderID"];
        }
        //we're done
    }
}