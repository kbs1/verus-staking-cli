<?php

namespace App\Controllers;

use App\Verus\Verus;

class Controller
{
	protected $config, $verus;

	public function __construct(array $config, Verus $verus)
	{
		$this->config = $config;
		$this->verus = $verus;

		$this->verus->setLogger(function ($data) {
			$this->log($data);
		});
	}

	protected function log($data)
	{
		$this->response(date('Y-m-d H:i:s') . ' UTC [PID ' . getmypid() . '] ' . $data);
	}

	protected function response($data)
	{
		echo "$data\n";
	}

	protected function responseJson($data)
	{
		$data = @json_encode($data, JSON_PRETTY_PRINT);

		if ($data === false)
			echo "Internal error while encoding JSON.\n";

		$this->response($data);
	}
}
