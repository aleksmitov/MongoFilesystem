<?php
namespace MongoFilesystem\Renderer;
use MongoFilesystem\MongoFile;
use \Twig_Loader_Filesystem;
use \Twig_Environment;
abstract class FileRenderer
{
    /**
     * Holds the MongoFile instance
     * @var MongoFolder
     */
    protected $file;
    protected $twig;
    protected $twigLoader;
    /**
     * @var string The path to the views to be rendered
     */
    protected $pathToViews;
    /**
     * The type of the template to render
     * @var string
     */
    protected $typePrefix;
    /**
     * The costructor
     * @param MongoFile $file A MongoFile intstance of the file for rendering
     * @param string $pathToViews
     * @param string $typePrefix The type of the template to render
     */
    public function __construct(MongoFile $file, $pathToViews, $typePrefix)
    {
        $this->file = $file;
        $this->twigLoader = new Twig_Loader_Filesystem($pathToViews);
        $this->twig = new Twig_Environment($this->twigLoader);
        $this->pathToViews = $pathToViews;
        $this->typePrefix = $typePrefix;
    }
    public function render()
    {
         $context = array();
         $context["name"] = $this->file->getFilename();
         $context["extension"] = $this->file->getExtension();
         $context["permissions"] = $this->file->getPermissions();
         $context["owner"] = $this->file->getOwner();
         $context["group"] = $this->file->getGroup();
         /**
          * @var DateTime
          */
         $context["lastModified"] = $this->file->getLastModifiedDate();
         $context["size"] = $this->file->getSize();
         $context["ID"] = (string)$this->file->getID();
         $result = $this->twig->render($this->typePrefix . "_file.phtml", $context);
         return $result;
    }
}