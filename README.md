## Tunga Laravel Interview Challenge

## Requirements
Needs a database connection to run. Create a database and register it in .env
## Run process with
```gherkin
php artisan db:file
```
this will read the file `challenge.json` in the storage folder and start writing to the db. This without any knowledge of the data structure.

## Run on a minimal file
```gherkin
php artisan db:file --file=challenge.min.json
```

## Approach

    1. Read file
We read the file and determine its extension so as to select a suitable parser for the contents.
See `app/Services/Readers/FileReader.php`;

    2. Parse Each Record
We parse each record to understand the record data structure. 
This helps us to get field names and field types. We can also tell if a field itself needs a database table of its own.
See `app/Services/Parsers/RecordParser.php`
   
    3. Prepare the DB
We use the information gotten from the record to prepare the db. The assumption is that all records are of the same base class. So we assign a base table for the records.
   
Then we check for existence of columns and create them if they don't exist in the database. Also, for nested objects, we check for existence of tables and then columns
See `app/Services/Persistence/Repo.php`

    4. Filter Records
We Filter records using a filter registry. This registry is an array of filters. Each filter is registered as 
```gherkin
[
"field_name" => function ($fieldVal) {
  do something;
    return boolean;
},
]
```
For example, the case for age >18 <= 65
```injectablephp
MigrationProcess::registerFilter(["date_of_birth" => function($dob) {
                if ($dob == null) return true;
                else {
                    if (str_contains($dob, '/')) {
                        $dob = date('Y-m-d H:i:s', strtotime($dob));
                    }
                    $birth = new Carbon($dob);
                    return $birth->age > 18 && $birth->age <= 65;
                }
            }]);
```
So we can add or remove filters from the registry depending on the client's recommendations

    5. Do the Migration
We then run the migration for each record dynamically. so the process doesn't need to know beforehand any predefined schema

