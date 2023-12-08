This guideline will help you to use CleanTalk check_bot API method via the special library that can be downloaded from this repo.
## Usage
1. Download the lib and unpack it to your filesystem.
Add the CleanTalk Bot-detector JS library wrapper to the page HTML you want to protect.
```js
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <script src="https://moderate.cleantalk.org/ct-bot-detector-wrapper.js"></script>
    </meta>
</head>
```
2. Fill the [config.php](src%2FCleantalk%2Fconfig.php). Obligatory properties are:
   * access_key - your CleanTalk access key.
   * trust_cleantalk_decision - set this to true if you do not want to set custom checking settings

3. To start use CheckBot library, include "yourpath/src/autoloader.php" into the code you want to perform the check.

```php
require_once '../../src/autoloader.php';
```
4. Then create a new CleanTalk\CheckBot object, provide $_POST or filtered POST data to the constructor.

```php
$bot_checker = new \Cleantalk\CheckBot($_POST);
```

5. Then perform the check:
```php
$is_bot = $bot_checker->check()->getVerdict();
```

6. Then do the actions depending on the verdict.
```php
if ( $is_bot ) {
  die ($bot_checker->getBlockMessage());
}
```
## Config setup explanation

### Default config.php
```php
<?php
global $check_bot_config;
$check_bot_config = array(
    'access_key' => "",
    'trust_cleantalk_decision' => true,
    'common_block_message' => 'Visitor blocked. It seems to be a bot.',
    'bot_expectation' => 0.5,
    'ip_frequency_24hour' => 50,
    'ip_frequency_1hour' => 15,
    'ip_frequency_10min' => 5,
    'do_log' => false
);
```

### Common params

- <code> access_key (string) </code>
Your CleanTalk access key.
- <code> trust_cleantalk_decision (bool)</code>
Set this to true if you do not want to set custom checking settings. Applicable in the most cases. 
- <code> common_block_message (string) </code>
A message for blocked visitor.
- <code> do_log (bool) </code>
Set to true if you want to see the log, false otherwise.

### Custom params
<i>Params below affected only if the property "trust_cleantalk_decision is set to false.</i>

- <code> bot_expectation </code>
Set maximum bot probability percentage. For example, 0.5 is 50%. If CleanTalk API responsed with bot_expectation 0.53 - visitor will be blocked, if 0.47 - passed.

- <code> ip_frequency_24hour </code>
- <code> ip_frequency_1hour </code>
- <code> ip_frequency_10min </code>

Custom checks - set how to block a visitor whose IP address detected by CleanTalk service in the period. For example, if CleanTalk response contains ip_frequency_24hour = 1000, and the config property ip_frequency_24hour = 500, visitor will be blocked.


## Examples
Examples can be found in the /examples folder.
