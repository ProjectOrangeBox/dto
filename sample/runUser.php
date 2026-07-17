<?php

declare(strict_types=1);

// Show all PHP errors, warnings, and notices on the CLI.
error_reporting(E_ALL);
ini_set('display_errors', '1');

use orange\request\sample\User;

require __DIR__ . '/../../../autoload.php';

// The sample classes live outside the package autoload, so load it directly.
require_once __DIR__ . '/User.php';

$input = [
  'fullname' => 'Johnny Appleseed',
  'age' => '23',
  'clr' => 'Orange',
  'rgb' => 'red',
  'notRequired' => true,
];

$request = new User($input);

if ($request->isValid()) {
  echo $request->fieldname('name') . ' ' . $request->name . PHP_EOL;
  echo $request->fieldname('age') . ' ' . $request->age . PHP_EOL;
  echo $request->fieldname('color') . ' ' . $request->color . PHP_EOL;
  echo $request->fieldname('rgb') . ' ' . $request->rgb . PHP_EOL;
  echo $request->fieldname('notRequired') . ' ' . $request->notRequired . PHP_EOL;

  var_dump($request->validInputKeys());
  var_dump($request->validKeys());

  var_dump($request->asTable());
} else {
  var_dump($request->validInputKeys());
  var_dump($request->validKeys());

  var_dump($request->errors());
}
