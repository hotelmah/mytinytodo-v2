# MyTinyTodo Version 2.0.0

## About

The UI and Javascript of the version before this is superb. The original author has done a fantastic job with the UI and client-side Javascript. I barely touched the UI and Javascript.

This version 2.0.0 re-organizes the server code and converts it to a MVC design pattern with a Router, Dependency Injection, proper Request and Response objects, and a break-up of files into smaller more organized pieces.

The server code uses League\Router and League\Container dependencies. There is a slight negative performance impact as a result.

This is a very nice Web Application.

## Installation

Since this is an application and not a library or dependency, the best way to install is to clone this repository to a sub-folder in your `public_http` folder and run `composer install`. Then navigate to index in the browser, and the setup should run.

## Files Not Included in Repository

These two files are installed when the setup runs.

- mytinytodo.db
- App\Config\config.database.php

## Features Not Tested

The MySQLi and PostGres database classes were not tested. Only SQLite3. Use SQLite3 as your database for the installation.

## RSS Feed

The RSS Feed feature may not be working properly.

## Windows as Your Development Environment

This is due to testing with IIS. I ran this App from a sub-directory and had the web root one level up.
In index.php, the `$URIPrefix` variable has a default value of `/mytinytodo`.
In production, the .htaccess file should read an empty URI_PREFIX, or you can set this if you run the app from a sub-directory
and you choose not to use a sub-domain which would effectively run the App in the root where a sub-directory is not used.

In production, on a LAMP host, the .htaccess contains this Environment Variable, URI_PREFIX with a value of an empty string:
`SetEnv URI_PREFIX`

## Future Updates

The server code uses PDO. One upgrade would be to use the native PHP SQLite3 classes and MySQLi classes. Doing so may improve performance.

Regarding Separation of Concerns, logic that normally would be in Model classes are found in the Controllers and Database namespace. Maybe this could be re-organized.
