# Logging API Laravel Bridge

## Requirements

- Laravel 7.x or 8.x

## Installation

Add as dependency on your project.

```bash
composer require despatchcloudturkey/logging-api-laravel
```

Set your application API credentials to your `.env` file.

```
LOGGING_API_URL=http://your-logging-api-server.com
LOGGING_API_KEY=your-api-key
```

If you are using VPC (Local Network) with Logging API Server, you can use private url environment.

```
LOGGING_API_PRIVATE_URL=http://127.0.0.1:3001
```

## Usage

### Store a log

You can create a log without ID. Logging API will assign unique ID (127 char) for your log content. This function will return this id as `string`.

```php
$id = LoggingAPI::store('my log file content is here');
```

### Store a log with ID

You can create a log with your specific ID parameter.

```php
LoggingAPI::store('John want to go.', '123ABC');
```

### Upload a log file

You can upload your log file to Logging API as file.

```php
$id = LoggingAPI::upload('/path/of/my/file/'); // without ID
LoggingAPI::upload('/path/of/my/file/', 'my-id'); // with ID
```

### Get log content

You can obtain your log content as string.

```php
$contents = LoggingAPI::get('123ABC');
```

### Response log file

You can create a response with logged content/file.

```php
return LoggingAPI::response('my-id);
```

### Delete a log file

You can delete a log file in Logging API.

```php
LoggingAPI::delete('123ABC');
```

Created by Despatch Cloud.
