<?php


namespace App\Services\Persistence;


use App\Services\Parsers\RecordParser;
use phpDocumentor\Reflection\Types\Self_;

class MigrationProcess
{
    protected static $repo;
    protected static $current_batch = [];
    protected static $name;
    protected static $trusted = false;
    protected static $recordParser = null;
    protected static $subRecordParser = [];
    protected static $subRepos = [];
    protected static $filters = [];
    public static function start($records, $name, $trust = false, $at = null) {
        $START = now();
        if ($records == null) {
            throw new \Exception("Records cannot be null");
        }
        $cnt = count($records);
        self::$name = $name;
        self::$repo = Repo::getInstance();
        self::$trusted = $trust;
        // check for incomplete jobs
        $incomplete = self::$repo->getIncompleteJob($name);
        if ($incomplete !== null && $at !== null) {
            // the context here could be a nested object or the base object
            $parent = self::$repo->getJobParentContext($name, $incomplete->parent_context);
            self::$current_batch = [$incomplete->cursor_context => $incomplete->id, self::$name => $at->id];
            if ($parent == null) {
                $j = $incomplete->cursor_pos;
                for ($i = $incomplete->cursor_pos; $i < count($records); $i++) {
                    echo ($i + 1)."/".$cnt."\n";
                    if ($i == $incomplete->cursor_pos) {
                        $rep = Repo::getInstance($incomplete->cursor_context);
                        self::writeRecord(RecordParser::getInstance($records[$i]), $i, $incomplete->cursor_context, $rep);
                    } else {
                        self::writeRecord(RecordParser::getInstance($records[$i]), $i, Repo::$baseTable, self::$repo);
                        self::updateJobWriterPointer($i, false);
                    }
                    $j++;
                }
            } else {
                self::$current_batch = array_merge(self::$current_batch, [$parent->cursor_context => $parent->id]);
                $j = $parent->cursor_pos;
                for ($i = $parent->cursor_pos; $i < count($records); $i++) {
                    echo ($i + 1)."/".$cnt."\n";
                    if ($i == $parent->cursor_pos) {
                        $rep = Repo::getInstance($incomplete->cursor_context);
                        $parentRec = $rep->getRecord($parent->cursor_context, $parent->cursor_context_id);
                        self::writeRecord(RecordParser::getInstance($records[$i][$incomplete->cursor_context]),
                            $i, $incomplete->cursor_context, $rep, $parent->cursor_context,
                            $parentRec ? $parentRec->id : 0);
                    } else {
                        self::writeRecord(RecordParser::getInstance($records[$i]), $i, Repo::$baseTable, self::$repo);
                        self::updateJobWriterPointer($i, false);
                    }
                    $j++;
                }
            }
        } else {

            $j = 0;
            if ($at != null) {
                $j = $at->cursor_pos + 1;
                $latest = self::$repo->getLatestJobContext(self::$name);
                self::$current_batch = [$latest->parent_context => $latest->id, self::$name => $at->id];
            } else {
                // initialize
                self::initNewJobWritePointer($name);
                self::initWritePointer($name);
            }
            $k = $j;
            for ($i = $k; $i < count($records); $i++) {
                echo ($i + 1)."/".$cnt."\n";
                self::writeRecord(RecordParser::getInstance($records[$i]), $i, Repo::$baseTable, self::$repo);
                self::updateJobWriterPointer($i, false);
                $j++;
            }
        }
        self::updateJobWriterPointer($j, true);
        $STOP = now();
        echo "done in ".number_format(($STOP->getTimestamp() - $START->getTimestamp()))." seconds\n";
    }

    private static function initWritePointer($name) {
        $id = self::$repo->insert([
            "name" => $name,
            "cursor_context" => Repo::$baseTable,
            "cursor_pos" => 0
        ],  Repo::$jobsRecordTable, true);
        self::$current_batch = array_merge(self::$current_batch, [Repo::$baseTable => $id]);
    }
    private static function initNewJobWritePointer($name) {
        $id = self::$repo->insert([
            "name" => $name,
            "cursor_pos" => 0
        ],  Repo::$jobsTable, true);
        self::$current_batch = array_merge(self::$current_batch, [$name => $id]);
    }

    private static function createWritePointer($context, $parent_context) {
        $id = self::$repo->insertOrUpdate([
            "name" => self::$name,
            "cursor_context" => $context,
            "parent_context" => $parent_context,
            "cursor_pos" => 0
        ],  Repo::$jobsRecordTable, true);
        self::$current_batch = array_merge(self::$current_batch, [$context => $id]);
    }

    /**
     * @param $context
     * @param $index
     */
    private static function updateWritePointer($context, $index, $status, $context_id = null)  {
        self::$repo->update([
            "cursor_pos" => $index,
            "status" => $status,
            "cursor_context_id" => $context_id
        ], Repo::$jobsRecordTable, self::$current_batch[$context], true);
    }

    private static function updateJobWriterPointer($index, $status) {
        self::$repo->update([
            "cursor_pos" => $index,
            "status" => $status
        ], Repo::$jobsTable, self::$current_batch[self::$name], true);
    }

    private static function writeRecord($recordParser,
                                        $index, $context, $repository,
                                        $parent_context = null, $parentId = null) {
        // apply the registered filters
        if (self::filter($recordParser)) {
            $fieldTypes = $recordParser->getFieldTypes();
            $record = $recordParser->getRecord();
            if ($parentId != null && $parent_context != null) {
                $fieldTypes = array_merge($fieldTypes, [$parent_context."_id" => " BIGINT NULL"]);
                $record = array_merge($record, [$parent_context."_id" => $parentId]);
            }
            $repository->prepareTable($fieldTypes);
            $written = $repository->insertRecord($record, true);
            $complete = array_key_exists("subTables", $written) && count($written["subTables"]) == 0;
            if(array_key_exists("id", $written)) {
                self::updateWritePointer($context, $index, true, $written["id"]);
            }
            if (!$complete) {
                $i = 0;
                foreach ($written["subTables"] as $k => $subTable) {
                    // create a write pointer
                    if (gettype($k) !== "string") {
                        // this is a list of this $context
                        self::createWritePointer($k, $context);

                        self::writeRecord(RecordParser::getInstance($subTable), $i, $context, Repo::getInstance($context), $parent_context, $written["id"]);
                        self::updateWritePointer($k, $i, true, $written["id"]);
                    } else {
                        self::createWritePointer($k, $context);

                        self::writeRecord(RecordParser::getInstance($subTable), $i, $k, Repo::getInstance($k), $context, $written["id"]);
                    }
                    $i++;
                }
            }
        }
    }
    public static function registerFilter($filter) {
        self::$filters = array_merge(self::$filters, $filter);
    }
    private static function filter($record) {
        foreach (self::$filters as $key => $filter) {
            if(!$filter($record->getFieldValue($key))) {
                return false;
            };
        }
        return true;
    }
}
