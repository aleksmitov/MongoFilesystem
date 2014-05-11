<?php
namespace MongoFilesystem\Renderer;
use MongoFilesystem\Renderer\FileRenderer;
use MongoFilesystem\MongoFile;
class HTMLFileRenderer extends FileRenderer
{
    /**
     * The costructor
     * @param MongoFile $file A MongoFile intstance of the file for rendering
     * @param string $pathToViews - The directory of the template to render
     */
    public function __construct(MongoFile $file, $pathToViews)
    {
        parent::__construct($file, $pathToViews, 'html');
    }
}