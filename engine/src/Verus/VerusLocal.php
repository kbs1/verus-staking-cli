<?php

namespace App\Verus;

class VerusLocal extends Verus
{
	protected $state, $asked_times;

	public function __construct(array $config)
	{
		parent::__construct($config);

		$this->state = 1;
		$this->asked_times = 0;
	}

	protected function runCommand($cmd, &$output, &$return)
	{
		$return = 0;
		$output = [];

		if (substr($cmd, 0, 16) == 'z_shieldcoinbase') {
			$output = explode("\n", '{
  "remainingUTXOs": 0,
  "remainingValue": 0.00000000,
  "shieldingUTXOs": 1,
  "shieldingValue": 24.00000000,
  "opid": "opid-xxxx"
}');
			$this->state = 2;
		}

		if (substr($cmd, 0, 10) == 'z_sendmany') {
			$output = ['opid-xxxx'];
		}

		if ($cmd == 'listunspent') {
			$output = explode("\n", '[
  {
    "txid": "0123456789012345678901234567890123456789012345678901234",
    "vout": 0,
    "generated": false,
    "address": "R0123456789012345678901234567890123",
    "account": "test-main",
    "amount": 5.99242017,
    "scriptPubKey": "01234567890123456789012345678901234567890123456789",
    "confirmations": 333,
    "spendable": true
  },
  {
    "txid": "0123456789012345678901234567890123456789012345678901234666",
    "vout": 0,
    "generated": true,
    "address": "R0123456789012345678901234567890123",
    "amount": 24.00000000,
    "interest": 0.00000000,
    "scriptPubKey": "6536536253",
    "confirmations": 222,
    "spendable": ' . ($this->state < 2 ? 'true' : 'false') . '
  }
]');
		}

		if ($cmd == 'z_gettotalbalance' && ($this->state == 1 || $this->asked_times < 3 || $this->asked_times > 6)) {
			$output = explode("\n", '{
  "transparent": "50.12345678",
  "interest": "0.00",
  "private": "0.00",
  "total": "50.12345678"
}');

			$this->asked_times++;
		} else if ($cmd == 'z_gettotalbalance' && $this->state == 2) {
			$output = explode("\n", '{
  "transparent": "26.12345678",
  "interest": "0.00",
  "private": "23.9999",
  "total": "50.12335678"
}');
			$this->asked_times++;
		}

		if (!$output)
			$return = 255;
	}
}
