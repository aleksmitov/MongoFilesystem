<?php
namespace MongoFilesystem\Exceptions;
use \MongoId;
use \ErrorException;
class MongoFileNotFoundException extends ErrorException
{
    /**
     * @param string $name
     * @param string $extension
     * @param MongoId $ID
     */
    public function __construct(MongoId $ID, $name = NULL, $extension = NULL) {
        
        $message = "A MongoFile ";
        if($name !== NULL)
        {
            $message .= $name;
            if($extension != NULL && $extension != "")
            {
                $message .= "." . $extension;
            }
        }
        $message .= " with MongoId: " . $ID . " does NOT exist.";
        parent::__construct($message);
    }
}

