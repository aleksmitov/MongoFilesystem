MongoFilesystem
===============

An implementation in PHP of a hierarchical file system using MongoDB as a storage engine. The library uses the MongoDB GridFS programming interface for storing the files and a standard collection for the folder information. There is an Object-oriented representation of the folders and files in the MongoFilesystem and rich API for performing operations on them. There are file/folder renderers for JSON/HTML/XML as well. 

Requirements
------------
* PHP >= 5.4.0
* MongoDB PHP Driver >= 1.4.0
* PHP SimpleXML extension
* You need the PHP Zip extension >= 1.1.0 in order to use the folder zipping functionality

Installation
------------
1. Install Composer https://getcomposer.org/doc/00-intro.md.
2. Add to your composer.json:
    ```"require": {
        "alexander-mitov/mongo-filesystem": "1.0.*"
    }```
3. Run the `composer install` command.

Basic usage
-----------
Look at the demo project: https://github.com/AlexanderMitov/Demo_Project_Of_MongoFilesystem

Tips
-----------
Getting a folder from the MongoFilesystem (or from the local fs using \MongoFilesystem\File) means that all its subfolders and subfolders of subfolders and so on are recursively/iteratively traversed. This is a nice convinience when performing upload/update/delete oprations on a folder or a certain action to its subfolders but it won't be optimal for cases when you need just the name or other property of the folder. In such cases set the second parameter of `\MongoFilesytem::getFolder()` to false in order to avoid traversing subfolders. This also means that you should NOT perform upload/update/delete operations on a folder with unset subfolders for obvious reasons.

API
----------------
* MongoFilesystem::__construct(MongoDB)
* MongoFilesystem::createFolder(string, string)
* MongoFilesystem::deleteFile(MongoFile)
* MongoFilesystem::deleteFolder(MongoFolder)
* MongoFilesystem::downloadAndOutputFile(MongoFile)
* MongoFilesystem::downloadAndOutputFolder(MongoFolder, boolean)
* MongoFilesystem::downloadFile(MongoFile)
* MongoFilesystem::downloadFileInFile(MongoFile)
* MongoFilesystem::downloadFileInFolder(MongoFile, string)
* MongoFilesystem::downloadFolderInFile(MongoFolder, string)
* MongoFilesystem::downloadFolderInFolder(MongoFolder, string)
* MongoFilesystem::fileWithIDExists(MongoId)
* MongoFilesystem::fileWithNameExistsInFolder(string, string, MongoId)
* MongoFilesystem::fileWithPathExists(string, string)
* MongoFilesystem::filesAreIdentical(MongoId, File)
* MongoFilesystem::folderWithIDExists(MongoId)
* MongoFilesystem::folderWithNameAndParentFolderIDExists(string, MongoId)
* MongoFilesystem::folderWithNameExistsInFolder(string, MongoId)
* MongoFilesystem::folderWithPathExists(string, string)
* MongoFilesystem::getFile(MongoId)
* MongoFilesystem::getFileByNameAndParentFolderID(string, string, MongoId)
* MongoFilesystem::getFileByPath(string, string)
* MongoFilesystem::getFileIDByNameAndParentFolderID(string, string, MongoId)
* MongoFilesystem::getFileIDByPath(string, string)
* MongoFilesystem::getFilePath(MongoFile, boolean, string)
* MongoFilesystem::getFileResourceStream(MongoId)
* MongoFilesystem::getFolder(MongoId, boolean)
* MongoFilesystem::getFolderByNameAndParentFolderID(string, MongoId)
* MongoFilesystem::getFolderByPath(string, string)
* MongoFilesystem::getFolderByPath(string, string)
* MongoFilesystem::getFolderFiles(MongoId)
* MongoFilesystem::getFolderIDByNameAndParentFolderID(string, MongoId)
* MongoFilesystem::getFolderIDByPath(string, string)
* MongoFilesystem::getFolderParentFolderID(MongoId)
* MongoFilesystem::getFolderPath(MongoId, boolean, string)
* MongoFilesystem::getFolderSubfolders(MongoId)
* MongoFilesystem::getParentFolder(mixed)
* MongoFilesystem::moveFileInFolder(MongoId, MongoId)
* MongoFilesystem::moveFolderInFolder(MongoId, MongoId)
* MongoFilesystem::renameFile(MongoId, string, string)
* MongoFilesystem::renameFolder(MongoId, stirng)
* MongoFilesystem::updateFile(MongoId, File)
* MongoFilesystem::updateFolder(MongoId, Folder)
* MongoFilesystem::uploadFile(File, MongoId, boolean)
* MongoFilesystem::uploadFolder(Folder, MongoId)
* MongoFilesystem::uploadRecursivelyFolder(Folder, MongoId)

