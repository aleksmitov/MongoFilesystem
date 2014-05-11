<?php
namespace MongoFilesystem\Renderer;
use MongoFilesystem\MongoFolder;
use MongoFilesystem\MongoFile;
use MongoFilesystem\Renderer\FolderRenderer;
use MongoFilesystem\Renderer\XMLFileRenderer;
class XMLFolderRenderer extends FolderRenderer
{
    public function __construct(MongoFolder $folder, $pathToViews)
    {
        parent::__construct($folder, $pathToViews, 'xml');
    }

    protected function getFileRenderer(MongoFile $file) {
        return new XMLFileRenderer($file, $this->pathToViews);
    }
    protected function getFolderRenderer(MongoFolder $folder) {
        return new XMLFolderRenderer($folder, $this->pathToViews);
    }
}