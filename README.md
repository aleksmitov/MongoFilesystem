MongoFilesystem
===============

Filesystem implementation on top of MongoDB. It manages a folder hierarchy of folders and files inside them. We use the GridFS as the file storage and a standard collection for the folders. There is an Object-oriented representation of the folders and files in the MongoFilesystem and rich API for executing operation on them. There are file/folder renderers for JSON/HTML/XML as well. 

Requirements
==============
PHP >= 5.4.0;
MongoDB PHP Driver >= 1.4.0;
PHP SimpleXML extension;
You need the PHP Zip extension >= 1.1.0 in order to use the folder zipping functionality;

Installation
==============
Install Composer https://getcomposer.org/doc/00-intro.md.
Add to your composer.json:
    "require": {
    	"alexander-mitov/mongo-filesystem": "1.0.*"
    }
