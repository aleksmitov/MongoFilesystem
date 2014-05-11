<?php
//autoloading the classes
$vendorName = "AlexanderMitov";
$autoLoadClasses = array(
    'MongoFilesystem\\File',
    'MongoFilesystem\\Folder',
    'MongoFilesystem\\MongoFilesystem',
    'MongoFilesystem\\MongoFile',
    'MongoFilesystem\\MongoFolder',
    'MongoFilesystem\\Exceptions\\MongoFileNotFoundException',
    'MongoFilesystem\\Exceptions\\MongoFolderNotFoundException',
    'MongoFilesystem\\Renderer\\FolderRenderer',
    'MongoFilesystem\\Renderer\\FileRenderer',
    'MongoFilesystem\\Renderer\\JSONFileRenderer',
    'MongoFilesystem\\Renderer\\JSONFolderRenderer',
    'MongoFilesystem\\Renderer\\HTMLFileRenderer',
    'MongoFilesystem\\Renderer\\HTMLFolderRenderer',
    'MongoFilesystem\\Renderer\\XMLFileRenderer',
    'MongoFilesystem\\Renderer\\XMLFolderRenderer',
);
//personal modification
$autoloadLibraries = array(
	'libraries\\ZipStream',
);
foreach($autoloadLibraries as $library)
{
	autoload($library);
}
//end
foreach ($autoLoadClasses as $className)
{
    autoload($vendorName . "\\" .$className);   
}

function autoload($className)
{
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    require $fileName;
}
