<?php

declare(strict_types=1);

/**
 * Runnable demonstration of the sample request classes.
 *
 *   php sample/run.php
 *
 * It loads the Composer autoloader for the orange\dto\* classes, then loads
 * the sample request classes (which are not part of the package autoload) and
 * runs each one against a valid and an invalid input set.
 */

// Show all PHP errors, warnings, and notices on the CLI.
error_reporting(E_ALL);
ini_set('display_errors', '1');

use orange\dto\Dto;
use orange\dto\sample\ApiSettings;
use orange\dto\sample\Article;
use orange\dto\sample\ContactPreference;
use orange\dto\sample\Payment;
use orange\dto\sample\Registration;
use orange\dto\sample\User;

require __DIR__ . '/../../../autoload.php';

foreach (['User', 'Registration', 'Article', 'Payment', 'ApiSettings', 'ContactPreference'] as $sample) {
  require_once __DIR__ . '/' . $sample . '.php';
}

/**
 * Runs a request class against an input set and prints a short report.
 */
function demo(string $class, string $label, array $input): void
{
  /** @var Dto $request */
  $request = new $class($input);

  $short = substr((string)strrchr($class, '\\'), 1);

  echo PHP_EOL . '=== ' . $short . ' — ' . $label . ' ===' . PHP_EOL;

  if ($request->isValid()) {
    echo 'VALID' . PHP_EOL;
    echo 'asArray:   ' . json_encode($request->asArray()) . PHP_EOL;
    echo 'asColumns: ' . json_encode($request->asColumns()) . PHP_EOL;
    echo 'asTable:   ' . json_encode($request->asTable()) . PHP_EOL;
  } else {
    echo 'INVALID' . PHP_EOL;

    foreach ($request->errors() as $field => $messages) {
      echo '  - ' . $field . ': ' . implode('; ', $messages) . PHP_EOL;
    }
  }
}

demo(User::class, 'valid', [
  'name' => 'Johnny Appleseed',
  'age' => '23',
  'clr' => 'Orange',
]);

demo(User::class, 'invalid', [
  'name' => '',
  'age' => '10',
  'clr' => 'ab',
]);

demo(Registration::class, 'valid', [
  'username' => '  JohnnyApple  ',
  'email' => '  Johnny@Example.COM ',
  'password' => 'supersecret',
  'password_confirmation' => 'supersecret',
  'age' => '23',
]);

demo(Registration::class, 'invalid', [
  'username' => 'jo',
  'email' => 'not-an-email',
  'password' => 'short',
  'password_confirmation' => 'different',
  'age' => '10',
]);

demo(Article::class, 'valid', [
  'title' => "  The   Orange    Way  ",
  'slug' => 'The-Orange-Way',
  'body' => '<p>Hello <b>world</b></p>',
  // status omitted — DefaultTo supplies "draft"
  'published_on' => '2026-07-17',
]);

demo(Article::class, 'invalid', [
  'title' => '',
  'slug' => 'Not a Slug!',
  'body' => '',
  'status' => 'pending',
  'published_on' => '17/07/2026',
]);

demo(Payment::class, 'valid', [
  'card_number' => '4111 1111 1111 1111',
  'amount' => '49.99',
  'currency' => 'usd',
]);

demo(Payment::class, 'invalid', [
  'card_number' => '1234567890123456',
  'amount' => '0.01',
  'currency' => 'yen',
]);

demo(ApiSettings::class, 'valid', [
  'webhook_url' => 'https://example.com/hooks/incoming',
  'config' => '{"retries":3,"enabled":true}',
  'timezone' => 'America/New_York',
  'api_key' => '550e8400-e29b-41d4-a716-446655440000',
  'host' => 'API.Example.com',
  'brand_color' => '#ff8800',
]);

demo(ApiSettings::class, 'invalid', [
  'webhook_url' => 'notaurl',
  'config' => '{bad json}',
  'timezone' => 'Mars/Phobos',
  'api_key' => 'not-a-uuid',
  'host' => 'not a host',
  'brand_color' => 'ff88',
]);

demo(ContactPreference::class, 'valid (phone, no promo)', [
  'contact_method' => 'phone',
  'phone' => '555-123-4567',
  // email not required because method is "phone"
  'handle' => 'orange_fan',
  // promo_code + referral both omitted
]);

demo(ContactPreference::class, 'invalid (email required, disallowed handle, promo without referral)', [
  'contact_method' => 'email',
  'email' => '',
  'handle' => 'admin',
  'promo_code' => 'SAVE10',
  'referral' => '',
]);

echo PHP_EOL;
