<?php


namespace App\Services\Persistence;



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Repo
{
    public static $baseTable = 'users_table';
    public static $jobsRecordTable = 'jobs_record_table';
    public static $jobsTable = 'jobs_table';
    private $table = "";
    private $columnNames = [];
    private static $instance = null;
    /**
     * Repo constructor.
     */
    private function __construct($table = "")
    {
        $this->table = $table;
    }

    /**
     * @param string $table
     * @return Repo|null
     * @throws \Exception
     */
    public static function getInstance($table = "") {
        if ($table == "") $table = self::$baseTable;
        self::$instance = new Repo($table);
        if (!self::$instance->tableExists()) {
            self::$instance->createTableWithID();
        }
        // initialize minimal db
        self::$instance->initMinimalSchema();
        // get column names
        self::$instance->setColumnNames();
        return self::$instance;
    }

    /**
     * @throws \Exception
     */
    private function setColumnNames() {
        if (self::$instance == null) {
            throw new \Exception("Null exception: Cannot set column names for null instance");
        }
        $query = DB::getSchemaBuilder()->getColumnListing($this->table);
        $this->columnNames = $query ?: [];
    }

    /**
     * @return mixed|string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return array
     */
    public function getColumnNames()
    {
        return $this->columnNames;
    }

    /**
     * @return bool
     */
    public function tableExists() {
        return Schema::hasTable($this->table);
    }

    /**
     * @param $name
     * @return bool
     */
    public function columnExists($name) {
        return Schema::hasColumn($this->table, $name);
    }

    /**
     * @throws \Exception
     */
    public function createTableWithID() {
        if ($this->table == "") {
            throw new \Exception("Table must have a name");
        }
        Schema::create($this->table, function ($table) {
            $table->bigIncrements('id');
            $table->timestamps();
        });
    }

    public function initMinimalSchema() {
        if (!Schema::hasTable(self::$baseTable)) {
            Schema::create(self::$baseTable, function ($table) {
               $table->bigIncrements('id');
                $table->timestamps();
            });
        }
        if(!Schema::hasTable(self::$jobsRecordTable)) {
            Schema::create(self::$jobsRecordTable, function ($table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('cursor_context');
                $table->string('parent_context')->nullable();
                $table->bigInteger('cursor_pos');
                $table->boolean('status')->default(0);
                $table->string('cursor_context_id')->nullable();
                $table->timestamps();
            });
        }
        if(!Schema::hasTable(self::$jobsTable)) {
            Schema::create(self::$jobsTable, function ($table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->bigInteger('cursor_pos');
                $table->boolean('status')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * @param array $fieldTypes
     */
    public function prepareTable($fieldTypes = []){
        $query = $this->buildTableQuery($fieldTypes);
        if ($query != null) {
            DB::statement($this->buildTableQuery($fieldTypes));
        }
    }

    /**
     * @param array $fieldTypes
     * @return string|null
     */
    public function buildTableQuery($fieldTypes = []) {
        if ($fieldTypes == []) return null;
        $query = "ALTER TABLE `".$this->table."`";
        $noAddition = true;
        foreach ($fieldTypes as $key => $type) {
            if (!$this->columnExists($key) && $type !== "array") {
                $query .= " ADD `".$key."` ".$type." NULL,";
                $noAddition = false;
            }
        }
        // remove trailing comma, add semicolon
        $query = rtrim($query, ',').";";
        return $noAddition ? null : $query;
    }

    /**
     * @param $record
     * @return array
     */
    public function insertRecord($record, $timestamps) {
        $prep = $this->removeSubTables($record);
        if($timestamps) {
            $prep["record"] = array_merge($prep["record"], ["created_at" => now(), "updated_at" => now()]);
        }
        return ["id" => DB::table($this->table)->insertGetId($prep["record"]), "subTables" => $prep["subTables"]];
    }


    /**
     * @param $record
     * @param $table
     * @param false $timestamps
     * @return int
     */
    public function insert($record, $table, $timestamps = false) {
        if($timestamps) {
            $record = array_merge($record, ["created_at" => now()->toDateString(), "updated_at" => now()->toDateString()]);
        }
        return DB::table($table)->insertGetId($record);
    }

    /**
     * @param $record
     * @param $table
     * @param false $timestamps
     * @return int
     */
    public function insertOrUpdate($record, $table, $timestamps = false) {
        if($timestamps) {
            $record = array_merge($record, ["created_at" => now()->toDateString(), "updated_at" => now()->toDateString()]);
        }
        $id = 0;
        if (array_key_exists("name", $record) &&
            array_key_exists("cursor_context", $record) &&
            array_key_exists("parent_context", $record)){
            if($timestamps) {
                $record = array_merge($record, ["updated_at" => now()->toDateString()]);
            }
            $d = DB::table($table)
                ->where("name", $record["name"])
                ->where("cursor_context", "=", $record["cursor_context"])
                ->where("parent_context", "=", $record["parent_context"])->get();

            if (count($d) > 0) {
                $id = DB::table($table)
                    ->where('id', $d->first()->id)->update($record);
            } else {
                if($timestamps) {
                    $record = array_merge($record, ["created_at" => now()->toDateString(), "updated_at" => now()->toDateString()]);
                }
                $id = DB::table($table)->insertGetId($record);
            }
        }

        return $id;
    }

    public function update($record, $table, $id, $timestamp = false) {
        if ($timestamp) {
            $record = array_merge($record, ["updated_at" => now()->toDateString()]);
        }
        return DB::table($table)->where("id", $id)->update($record);
    }

    public function getRecord($table, $id) {
        return DB::table($table)
            ->where("id", $id)->get()->first();
    }
    public function getIncompleteJob($name) {
        return DB::table(self::$jobsRecordTable)
            ->where("name", $name)
            ->where("status", "=", 0)
            ->latest()->get()->first();
    }

    public function getJobParentContext($name, $parent_context) {
        return DB::table(self::$jobsRecordTable)
            ->where("name", $name)
            ->where("parent_context", "=", null)
            ->where("cursor_context", "=", $parent_context)
            ->get()->first();
    }

    public function getLatestJobContext($name) {
        return DB::table(self::$jobsRecordTable)
            ->where("name", $name)
            ->where("parent_context", "<>", null)
            ->latest()->get()->first();
    }

    /**
     * remove any nested object (sub table)
     * @param $record
     * @return mixed
     */
    private function removeSubTables($record){
        $rec = $record;
        $sub = [];
        foreach ($record as $key => $item) {
            if (gettype($item) == "array") {
                $sub[$key] = $item;
                unset($rec[$key]);
            }
        }
        return ["record" => $rec, "subTables" => $sub];
    }

}
