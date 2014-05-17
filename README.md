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
