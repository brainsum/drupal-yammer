# Yammer

Yammer integration module for Drupal

## Setup

Add this with non-example data to your settings.php:
```php
// Yammer.
$config['yammer.settings'] = [
  'client' => [
    'id' => '<client ID of the app>',
    'secret' => '<client secret of the app>',
    'network_id' => '<network ID>',
  ],
  'service_account' => [
    'name' => '<drupal user username of the service account>',
    'email' => '<drupal user email of the service account>',
  ],
  'date_format' => '<machine name of the date to be used>',
];
```
