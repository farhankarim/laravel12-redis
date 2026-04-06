<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Management System</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/university/main.jsx'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div id="university-app"></div>
</body>
</html>
