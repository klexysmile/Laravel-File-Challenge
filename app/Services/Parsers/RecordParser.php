<?php


namespace App\Services\Parsers;


class RecordParser
{
    private $record = [];
    private $fieldNames = [];
    private $fieldTypes = [];
    private static $instance = null;

    /**
     * RecordParser constructor.
     * @param array $record
     *
     */
    private function __construct($record = [])
    {
        $this->record = $record;
    }

    /**
     * @param array $record
     * @return RecordParser|null
     * @throws \Exception
     */
    public static function getInstance($record = [])
    {
        self::$instance = new RecordParser($record);
        self::$instance->prepareFieldNames();
        return self::$instance;
    }

    /**
     * @return array|mixed
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @return array
     */
    public function getFieldNames(): array
    {
        return $this->fieldNames;
    }

    /**
     * @throws \Exception
     */
    private function prepareFieldNames() {
        if (self::$instance == null) {
            throw new \Exception("Null exception: Cannot prepare field names for null instance");
        }
        if (count($this->record) > 0) {
            foreach ($this->record as $key => $item) {
                array_push($this->fieldNames, $key);
                $this->fieldTypes[$key] = $this->getFieldType($key);
            }
        }
    }

    /**
     *
     */
    public function getFieldValue($fieldName) {
        if (in_array($fieldName, $this->record)) {
            return $this->record[$fieldName];
        }
        return null;
    }

    /**
     * @param $fieldName
     * @return string|null
     */
    public function getFieldType($fieldName) {
        if (in_array($fieldName, $this->fieldNames)) {
            $type = gettype($this->record[$fieldName]);
            if ($type == "string") {
                return "LONGTEXT ";
            } else if ($type == "boolean") {
                return "BOOLEAN ";
            } else if ($type == "integer") {
                return "VARCHAR(256) ";
            } else if ($type == "double") {
                return "VARCHAR(256) ";
            } else if ($type == "NULL"){
                return "VARCHAR (256) ";
            } else {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param $index
     * @return mixed|null
     */
    public function get($index) {
        if (array_key_exists($index, $this->fieldNames)) {
            return $this->record[$this->fieldNames[$index]];
        }
        return null;
    }

    /**
     * returns true if the field in question is an object that should be stored on a separate table
     * @param $fieldName
     * @return bool
     */
    public function isTable($fieldName) {
        if (in_array($fieldName, $this->fieldNames)) {
            return is_array($this->record[$fieldName]);
        }
        return false;
    }

    /**
     * @return array
     */
    public function getFieldTypes()
    {
        return $this->fieldTypes;
    }



}
