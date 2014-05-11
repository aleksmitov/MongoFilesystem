<?php
require_once 'autoload.php';
require_once 'vendor/autoload.php'; //loading the composer packages
use MongoFilesystem\File;
use MongoFilesystem\MongoFile;
use MongoFilesystem\Renderer\JSONFolderRenderer;
use MongoFilesystem\Renderer\HTMLFolderRenderer;
use MongoFilesystem\Renderer\XMLFolderRenderer;
error_reporting(E_ALL);
$start = microtime(true);
$connection = new MongoClient("mongodb://127.0.0.1:27017");
$mongoFileSystem = new MongoFilesystem\MongoFilesystem($connection->selectDB("local"));


//$f = new MongoFilesystem\Folder("C:\\xampp\\htdocs\\FileSystem");

//$mongoFileSystem->uploadRecursivelyFolder($f);echo"<br>DONE<br>";
$folder = $mongoFileSystem->getFolderByPath("FileSystem");
$end = microtime(true) - $start;
$start2 = microtime(true);
echo "TIME to fetch from DB: " . $end . ' sedonds <br><br>';
$pathToViews = __DIR__ . DIRECTORY_SEPARATOR . 'views';
$renderer = new JSONFolderRenderer($folder, $pathToViews);
echo $renderer->render();
$end2 = microtime(true)-$start2;
echo "<br>TIME to Render: " . $end2 . ' sedonds <br><br>';
