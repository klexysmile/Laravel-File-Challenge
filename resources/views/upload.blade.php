<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;600&display=swap" rel="stylesheet">

</head>
<body>
<form action="{{route('fileUpload')}}" method="post" enctype="multipart/form-data">
    <h3 class="text-center mb-5">Upload a records file</h3>
    @csrf
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <strong>{{ $message }}</strong>
        </div>
    @endif

    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="custom-file">
        <input type="file" name="file" class="custom-file-input" id="chooseFile">
        <label class="custom-file-label" for="chooseFile">Select file</label>
    </div>

    <button type="submit" name="submit" class="btn btn-primary btn-block mt-4">
        Upload Files
    </button>
</form>
</body>
</html>
