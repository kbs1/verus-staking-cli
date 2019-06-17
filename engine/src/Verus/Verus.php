<?php

namespace App\Verus;

use App\Verus\Exceptions\VerusException;

class Verus
{
	protected $config, $storage_path, $logger;

	public function __construct(array $config)
	{
		$this->config = $config;
		$this->storage_path = __ROOT__ . '/storage';
		$this->logger = null;
	}

	public function setLogger(callable $logger)
	{
		$this->logger = $logger;
	}

	public function shield()
	{
		$result = $this->command('z_gettotalbalance');
		$balance = $result['private'] ?? '100.00000000';

		if (bccomp($balance, '0.00000000', 8) > 0) {
			$this->log("Private balance is $balance, previous unshielding operation might have failed. Please transfer private balance manually.");
			return;
		}

		$unspent = $this->command('listunspent');
		$shield = false;
		$total_generated = $this->retrieveTotalGenerated();
		$newly_generated = '0.00000000';

		foreach ($unspent as $entry) {
			if (($entry['generated'] ?? false) && ($entry['spendable'] ?? false)) {
				$shield = true;
				$newly_generated = bcadd($newly_generated, $entry['amount'] ?? '0.00000000', 8);
			}
		}

		$this->log('Total generated: ' . $total_generated);
		$this->log('Newly generated: ' . $newly_generated);
		$this->log(($shield ? 'R' : 'Not r') . 'unning shielding operation.');

		if (!$shield)
			return;

		if (bccomp($newly_generated, '0.00000000', 8) <= 0)
			throw new VerusException('Logic error: shielding but newly generated is <= 0.00000000');

		$result = $this->command('z_shieldcoinbase "*" ' . escapeshellarg($this->config['zs_address']));

		$shielding_value = $result['shieldingValue'] ?? '0.00000000';

		if (bccomp($newly_generated, $shielding_value, 8))
			$this->log("WARN: newly generated doesn't match shielding value, $newly_generated !== $shielding_value");

		$this->log("Shielding $shielding_value coins, waiting up to 20 minutes for shielded coins to arrive on private address.");

		$tries = 20 * 12;
		$try = 1;
		do {
			$this->log("Try $try: getting balances...");
			$try++;

			$result = $this->command('z_gettotalbalance');
			$balance = $result['private'] ?? '0.00000000';

			if (bccomp($balance, '0.00000000', 8) > 0) {
				$this->log("Private balance is $balance, unshielding coins...");
				break;
			}

			$this->log("Private balance not yet greater than 0, sleeping for 5 seconds...");

			sleep(5);
			$tries--;
		} while ($tries);

		if (!$tries)
			throw new VerusException("Private balance still 0 after 20 minutes, giving up.");

		$balance = bcsub($balance, '0.00010000', 8);

		$result = $this->command('z_sendmany ' . escapeshellarg($this->config['zs_address']) . ' ' . escapeshellarg('[{"address":"' . $this->config['t_address'] . '","amount":' . $balance . '}]') . ' 1 0.00010000', false);
		if (substr($result, 0, 5) !== 'opid-')
			throw new VerusException("Unexpected output from 'z_sendmany', expected 'opid-*', got '$result'");

		$this->log("Waiting up to 20 minutes for unshielded coins to arrive on transparent address.");

		$tries = 20 * 12;
		$try = 1;
		do {
			$this->log("Try $try: getting balances...");
			$try++;

			$result = $this->command('z_gettotalbalance');
			$balance = $result['private'] ?? '100.00000000';

			if (bccomp($balance, '0.00000000', 8) <= 0) {
				$this->log("Private balance is $balance, shielding operation done.");
				break;
			}

			$this->log("Private balance is still greater than 0, sleeping for 5 seconds...");

			sleep(5);
			$tries--;
		} while ($tries);

		if (!$tries)
			throw new VerusException("Private balance still greater than 0 after 20 minutes, giving up.");

		$this->storeTotalGenerated(bcadd($total_generated, $newly_generated, 8));
		$this->sheduleNewlyGeneratedCoinsEmail();
	}

	public function retrieveTotalGenerated()
	{
		$total = @file_get_contents($this->storage_path . '/total_generated.txt');

		if ($total === false)
			$total = '0.00000000';

		return $total;
	}

	public function getBalance()
	{
		return $this->command('z_gettotalbalance');
	}

	public function deleteData()
	{
		@unlink($this->storage_path . '/total_generated.txt');
		@unlink($this->storage_path . '/newly_generated_coins_email.txt');
		@unlink($this->storage_path . '/wallet_backup.zip');

		return true;
	}

	protected function storeTotalGenerated($total_generated)
	{
		$result = @file_put_contents($this->storage_path . '/total_generated.txt', $total_generated);

		if ($result === false)
			throw new VerusException('Unable to store total generated: ' . $total_generated);
	}

	protected function sheduleNewlyGeneratedCoinsEmail()
	{
		$result = @file_put_contents($this->storage_path . '/newly_generated_coins_email.txt', time() + 60 * 30);

		if ($result === false)
			throw new VerusException('Unable to queue newly generated coins e-mail.');
	}

	protected function log($data)
	{
		if ($this->logger)
			call_user_func_array($this->logger, [$data]);
	}

	protected function command($cmd, $parse_json = true)
	{
		$output = [];
		$return = 255;

		$this->runCommand($cmd, $output, $return);
		$cmd = $this->config['verus_path'] . ' ' . $cmd;

		$output = (array) $output;
		$output = implode("\n", $output);
		$return = (int) $return;

		if (!$output || $return)
			throw new VerusException("Command '$cmd' failed with return code $return, output: $output");

		if (!$parse_json)
			return $output;

		// convert all numbers to strings to aviod floating point imprecision
		$output = preg_replace('/":\s*(\d+\.*\d*E*e*\d*)/', '": "\1"', $output);

		$json = @json_decode($output, true);

		if ($json === null)
			throw new VerusException("Command '$cmd' returned invalid JSON, return code $return, output: $output");

		return $json;
	}

	protected function runCommand($cmd, &$output, &$return)
	{
		@exec($this->config['verus_path'] . ' ' . $cmd, $output, $return);
	}
}
