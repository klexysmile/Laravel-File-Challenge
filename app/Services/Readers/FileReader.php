<?php


namespace App\Services\Readers;


use Illuminate\Support\Facades\Storage;

class FileReader
{
    private $name;
    private $asString;

    /**
     * FileReader constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getExtension() {
        // find the last dot
        $dotParts = explode(".", $this->getName());
        return $dotParts[count($dotParts) - 1];
    }

    /**
     * @return mixed
     */
    public function getAsString()
    {
        return $this->asString;
    }


    public function readAsString() {
        $this->asString = Storage::get($this->name);
        return $this;
    }

    public function parseJson() {
        return json_decode($this->asString, true);
    }

    public function parseXML() {
        return null;
    }

    public function parseCSV() {
        return null;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function parse() {
        $ext = $this->getExtension();
        switch ($ext) {
            case "csv":
                return $this->parseCSV();
            case "xml":
                return $this->parseXML();
            case "json":
            default:
                return $this->parseJson();
        }
    }



}
