# MyTinyTodo Version 2.0.0

## About

The UI and Javascript of the version before this is superb. The original author has done a fantastic job with the UI and client-side Javascript. I barely touched the UI and Javascript.

This version 2.0.0 re-organizes the server code and converts it to a MVC format with a Router, Dependency Injection, proper Request and Response objects, and a break-up of files into smaller more organized pieces.

The server code uses League\Router and League\Container dependencies. There is a slight negative performance impact as a result.

This is a very nice Web Application.

## Installation

Since this is an application and not a library or dependency, the best way to install is to clone this repository to a public_http folder and run composer install. Then navigate to index, and the setup should run.

## Files Not Included in Repository

These two files are installed when the setup runs.

- mytinytodo.db
- App\Config\config.database.php

## RSS Feed

The RS Feed feature may not be working properly.

## Future Updates

The server code uses PDO. One upgrade would be to use the native PHP SQLite3 classes and MySQLi classes. Doing so may improve performance.
