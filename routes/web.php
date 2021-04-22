<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUpload;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get("/upload", function () {
    return view('upload');
});
Route::post('/upload-file', [FileUpload::class, 'fileUpload'])->name('fileUpload');

Route::get('/test', function () {
    $b = "{\"name\":\"Prof. Simeon Green\",\"address\":\"328 Bergstrom Heights Suite 709 49592 Lake Allenville\",\"checked\":false,\"description\":\"Voluptatibus nihil dolor quaerat. Reprehenderit est molestias quia nihil consectetur voluptatum et. Ea officiis ex ea suscipit dolorem. Ut ab vero fuga. Quam ipsum nisi debitis repudiandae quibusdam. Sint quisquam vitae rerum nobis.\",\"interest\":null,\"date_of_birth\":\"1989-03-21T01:11:13+00:00\",\"email\":\"nerdman@cormier.net\",\"account\":556436171909,\"credit_card\":{\"type\":\"Visa\",\"number\":4532383564703,\"name\":\"Brooks Hudson\",\"expirationDate\":\"12\/19\"}}";
    $a = \App\Services\Parsers\RecordParser::getInstance(json_decode($b, true));
    $c = null;
    if (is_array($a->getFieldValue('credit_card'))) {
        $c = \App\Services\Parsers\RecordParser::getInstance($a->getFieldValue('credit_card'));

    }
    $e = \App\Services\Persistence\Repo::getInstance('clients');
    $e->prepareTable($a->getFieldTypes());
    $rec = $e->insertRecord($a->getRecord());
   dd($a->getFieldNames(), $a->getFieldValue('name'),
       $a->getFieldType('credit_card'),
       $c->getFieldNames(),
       $c->getFieldValue('number'),
       $a->isTable("credit_card"),
       $a->isTable("address"),
       $a->get(3),
        $a->getRecord(),
        $a->getFieldTypes(),
        $e->buildTableQuery($a->getFieldTypes()),
        $rec
   );
});
Route::get('/test/db', function () {
    \App\Services\Persistence\Repo::$baseTable = 'users';
    $a = \App\Services\Persistence\Repo::getInstance();
    dd($a->getTable(), $a->getColumnNames());
});

Route::get('/test/sync', function () {
    $b = "[{\"name\":\"Prof. Simeon Green\",\"address\":\"328 Bergstrom Heights Suite 709 49592 Lake Allenville\",\"checked\":false,\"description\":\"Voluptatibus nihil dolor quaerat. Reprehenderit est molestias quia nihil consectetur voluptatum et. Ea officiis ex ea suscipit dolorem. Ut ab vero fuga. Quam ipsum nisi debitis repudiandae quibusdam. Sint quisquam vitae rerum nobis.\",\"interest\":null,\"date_of_birth\":\"1989-03-21T01:11:13+00:00\",\"email\":\"nerdman@cormier.net\",\"account\":556436171909,\"credit_card\":{\"type\":\"Visa\",\"number\":4532383564703,\"name\":\"Brooks Hudson\",\"expirationDate\":\"12\/19\"}},".
        "{\"name\":\"Prof. Simeon Bj\",\"address\":\"328 Bergstrom Heights Suite 709 49592 Lake Allenville\",\"checked\":false,\"description\":\"Voluptatibus nihil dolor quaerat. Reprehenderit est molestias quia nihil consectetur voluptatum et. Ea officiis ex ea suscipit dolorem. Ut ab vero fuga. Quam ipsum nisi debitis repudiandae quibusdam. Sint quisquam vitae rerum nobis.\",\"interest\":null,\"date_of_birth\":\"1989-03-21T01:11:13+00:00\",\"email\":\"nerdman@cormier.net\",\"account\":556436171909,\"credit_card\":{\"type\":\"Visa\",\"number\":4532383564703,\"name\":\"Brooks Hudson\",\"expirationDate\":\"12\/19\"}},".
        "{\"name\":\"Prof. Simeon Gn\",\"address\":\"329 Bergstrom Heights Suite 709 49592 Lake Allenville\",\"checked\":false,\"description\":\"Voluptatibus nihil dolor quaerat. Reprehenderit est molestias quia nihil consectetur voluptatum et. Ea officiis ex ea suscipit dolorem. Ut ab vero fuga. Quam ipsum nisi debitis repudiandae quibusdam. Sint quisquam vitae rerum nobis.\",\"interest\":null,\"date_of_birth\":\"1989-03-21T01:11:13+00:00\",\"email\":\"nerdman@cormier.net\",\"account\":556436171909,\"credit_card\":{\"type\":\"Visa\",\"number\":4532383564703,\"name\":\"Brooks Hudson\",\"expirationDate\":\"12\/19\"}},".
        "{\"name\":\"Prof. Simeon Green\",\"address\":\"328 Bergstrom Heights Suite 709 49592 Lake Allenville\",\"checked\":false,\"description\":\"Voluptatibus nihil dolor quaerat. Reprehenderit est molestias quia nihil consectetur voluptatum et. Ea officiis ex ea suscipit dolorem. Ut ab vero fuga. Quam ipsum nisi debitis repudiandae quibusdam. Sint quisquam vitae rerum nobis.\",\"interest\":null,\"date_of_birth\":\"1989-03-21T01:11:13+00:00\",\"email\":\"nerdman@cormier.net\",\"account\":556436171909,\"credit_card\":{\"type\":\"Visa\",\"number\":4532383564703,\"name\":\"Brooks Hudson\",\"expirationDate\":\"12\/19\"}},".
        "{\"name\":\"Prof. Simeon amber\",\"address\":\"324 Bergstrom Heights Suite 709 49592 Lake Allenville\",\"checked\":false,\"description\":\"Voluptatibus nihil dolor quaerat. Reprehenderit est molestias quia nihil consectetur voluptatum et. Ea officiis ex ea suscipit dolorem. Ut ab vero fuga. Quam ipsum nisi debitis repudiandae quibusdam. Sint quisquam vitae rerum nobis.\",\"interest\":null,\"date_of_birth\":\"1989-03-21T01:11:13+00:00\",\"email\":\"nerdman@cormier.net\",\"account\":556436171909,\"credit_card\":{\"type\":\"Visa\",\"number\":4532383564703,\"name\":\"Brooks Hudson\",\"expirationDate\":\"12\/19\"}},".
        "{\"name\":\"Prof. Simeon Green\",\"address\":\"325 Bergstrom Heights Suite 709 49592 Lake Allenville\",\"checked\":false,\"description\":\"Voluptatibus nihil dolor quaerat. Reprehenderit est molestias quia nihil consectetur voluptatum et. Ea officiis ex ea suscipit dolorem. Ut ab vero fuga. Quam ipsum nisi debitis repudiandae quibusdam. Sint quisquam vitae rerum nobis.\",\"interest\":null,\"date_of_birth\":\"1989-03-21T01:11:13+00:00\",\"email\":\"nerdman@cormier.net\",\"account\":556436171909,\"credit_card\":{\"type\":\"Visa\",\"number\":4532383564703,\"name\":\"Brooks Hudson\",\"expirationDate\":\"12\/19\"}},".
        "{\"name\":\"Prof. Simeon Jason\",\"address\":\"326 Bergstrom Heights Suite 709 49592 Lake Allenville\",\"checked\":false,\"description\":\"Voluptatibus nihil dolor quaerat. Reprehenderit est molestias quia nihil consectetur voluptatum et. Ea officiis ex ea suscipit dolorem. Ut ab vero fuga. Quam ipsum nisi debitis repudiandae quibusdam. Sint quisquam vitae rerum nobis.\",\"interest\":null,\"date_of_birth\":\"1989-03-21T01:11:13+00:00\",\"email\":\"nerdman@cormier.net\",\"account\":556436171909,\"credit_card\":{\"type\":\"Visa\",\"number\":4532383564703,\"name\":\"Brooks Hudson\",\"expirationDate\":\"12\/19\"}},".
        "{\"name\":\"Prof. Simeon Galons\",\"address\":\"327 Bergstrom Heights Suite 709 49592 Lake Allenville\",\"checked\":false,\"description\":\"Voluptatibus nihil dolor quaerat. Reprehenderit est molestias quia nihil consectetur voluptatum et. Ea officiis ex ea suscipit dolorem. Ut ab vero fuga. Quam ipsum nisi debitis repudiandae quibusdam. Sint quisquam vitae rerum nobis.\",\"interest\":null,\"date_of_birth\":\"1989-03-21T01:11:13+00:00\",\"email\":\"nerdman@cormier.net\",\"account\":556436171909,\"credit_card\":{\"type\":\"Visa\",\"number\":4532383564703,\"name\":\"Brooks Hudson\",\"expirationDate\":\"12\/19\"}}".
        "]";
//  dd(json_decode($b, true));
    \App\Services\Persistence\MigrationProcess::start(json_decode($b, true), "challenge.json");
//    dd($a->getTable(), $a->getColumnNames());

});

Route::get('/test/files', function () {
    $b = \Illuminate\Support\Facades\Storage::get('challenge.json');
//    dd($b);
//  dd(json_decode($b, true));
    \App\Services\Persistence\MigrationProcess::start(json_decode($b, true), "challenge.json");
//    dd($a->getTable(), $a->getColumnNames());

});

