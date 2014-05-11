<?php
namespace MongoFilesystem\Exceptions;
use \ErrorException;
use \MongoId;

class MongoFolderNotFoundException extends ErrorException
{
    /**
     * @param string $name The foldername
     * @param MongoId $ID
     */
    public function __construct(MongoId $ID, $name = "")
    {
        $message = "A MongoFolder with ";
        if($name != "")
        {
            $message .= "name: '" . $name . "' and ";
        }
        $message .= "MongoId: " . $ID . " does NOT exist.";
        parent::__construct($message);
    }
}
