# Yammer

Yammer integration module for Drupal

## Setup

Create an encryption key.

- Generate the key with the `dd bs=1 count=32 if=/dev/urandom | openssl base64 > yammer.key` command
- Move it to `<path to the private file system>/keys/yammer.key`


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
