<?php

define('__ROOT__', __DIR__);

if ($argc < 2)
	usage();

date_default_timezone_set('UTC');

$config = require_once __DIR__ . '/config.php';

$map = [
	'wallet' => 'WalletController',
	'emails' => 'QueuedEmailsController',
];

$controller = $map[$argv[1]] ?? null;

if (!$controller) {
	echo "Invalid operation.\n";
	usage();
}

if (!isset($config['verus_path']))
	die("Config key 'verus_path' is missing.\n");

if (@!is_executable($config['verus_path']))
	die("Config key 'verus_path' is invalid - file is not executable.\n");

if (isset($config['wallet_path']) && @!is_file($config['wallet_path']))
	die("Config key 'wallet_path' is invalid - file is not a regular file.\n");

if (!preg_match('/^[0-9a-zA-Z]+$/siu', $config['t_address']))
	die("Config key 't_address' is in invalid format.\n");

if (!preg_match('/^[0-9a-zA-Z]+$/siu', $config['zs_address']))
	die("Config key 'zs_address' is in invalid format.\n");

require_once __DIR__ . '/vendor/autoload.php';

if (isset($config['verus_class']) && $config['verus_class'] == 'VerusLocal') {
	$verus = new App\Verus\VerusLocal($config);
} else {
	$verus = new App\Verus\Verus($config);
}

$controller = "App\\Controllers\\$controller";
$controller = new $controller($config, $verus);

$args = $argv;
array_shift($args);
array_shift($args);
call_user_func_array([$controller, 'index'], $args);

function usage()
{
	die("Usage: php " . basename(__FILE__, '.php') . " operation [args, ...]
operation
	wallet
	- execute wallet operations
	emails
	- execute queued email operations

wallet args:
	shield
	- automatically shields and unshields any coinbase if required
	balance
	- prints current walelt balance (z_gettotalbalance, JSON)
	totalGenerated
	- print a sum of total generated coins
	balanceEmail
	- sends current wallet balance e-mail, if configured
	backupEmail
	- sends wallet.dat backup e-mail, if configured
	startFresh
	- removes all data and reverts the application to initial state

emails args:
	send
	- sends queued e-mail notifications
");
}

function dd()
{
	foreach (func_get_args() as $arg) {
		var_dump($arg);
		echo "\n";
	}

	die("\n");
}

