<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mon Miam Miam — ZeDuc@Space</title>
    <link rel="icon" type="image/png" href="/images/logo-zeduc-space.png">
    @viteReactRefresh
    @vite(['src/index.css', 'src/main.jsx'])
    @inertiaHead
  </head>
  <body>
    @inertia
  </body>
</html>
