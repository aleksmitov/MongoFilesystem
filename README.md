MongoFilesystem
===============

Filesystem implementation on top of MongoDB. It manages a hierarchy of folders and files inside them. The library uses the GridFS for the file storage and a standard collection for the folders. There is an Object-oriented representation of the folders and files in the MongoFilesystem and rich API for performing operations on them. There are file/folder renderers for JSON/HTML/XML as well. 

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

API
----------------
__construct(MongoDB)
createFolder(string, string)
deleteFile(MongoFile)
deleteFolder(MongoFolder)
downloadAndOutputFile(MongoFile)
downloadAndOutputFolder(MongoFolder, boolean)
downloadFile(MongoFile)
downloadFileInFile(MongoFile)
downloadFileInFolder(MongoFile, string)
downloadFolderInFile(MongoFolder, string)
downloadFolderInFolder(MongoFolder, string)
fileWithIDExists(MongoId)
fileWithNameExistsInFolder(string, string, MongoId)
fileWithPathExists(string, string)
filesAreIdentical(MongoId, File)
folderWithIDExists(MongoId)
folderWithNameAndParentFolderIDExists(string, MongoId)
folderWithNameExistsInFolder(string, MongoId)
folderWithPathExists(string, string)
getFile(MongoId)
getFileByNameAndParentFolderID(string, string, MongoId)
getFileByPath(string, string)
getFileIDByNameAndParentFolderID(string, string, MongoId)
getFileIDByPath(string, string)
getFilePath(MongoFile, boolean, string)
getFileResourceStream(MongoId)
getFolder(MongoId, boolean)
getFolderByNameAndParentFolderID(string, MongoId)
getFolderByPath(string, string)
getFolderByPath(string, string)
getFolderFiles(MongoId)
getFolderIDByNameAndParentFolderID(string, MongoId)
getFolderIDByPath(string, string)
getFolderParentFolderID(MongoId)
getFolderPath(MongoId, boolean, string)
getFolderSubfolders(MongoId)
getParentFolder(mixed)
moveFileInFolder(MongoId, MongoId)
moveFolderInFolder(MongoId, MongoId)
renameFile(MongoId, string, string)
renameFolder(MongoId, stirng)
updateFile(MongoId, File)
updateFolder(MongoId, Folder)
uploadFile(File, MongoId, boolean)
uploadFolder(Folder, MongoId)
uploadRecursivelyFolder(Folder, MongoId)

