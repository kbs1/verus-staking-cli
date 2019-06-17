<?php

namespace App\Controllers;

use App\Support\{ExclusiveLock, UnableToObtainLockException};
use App\Verus\Exceptions\VerusException;
use App\Emails\{BalanceEmail, BackupEmail};

class WalletController extends Controller
{
	public function index($action = null)
	{
		if ($action == 'shield')
			return $this->shield();
		else if ($action == 'totalGenerated')
			return $this->totalGenerated();
		else if ($action == 'balance')
			return $this->balance();
		else if ($action == 'balanceEmail')
			return $this->balanceEmail();
		else if ($action == 'backupEmail')
			return $this->backupEmail();
		else if ($action == 'startFresh')
			return $this->startFresh();

		return $this->responseJson(['result' => 'invalid-call', 'message' => 'Invalid wallet operation call.']);
	}

	protected function shield()
	{
		$lock = new ExclusiveLock('wallet_shield', 300);

		try {
			$lock->obtain();
		} catch (UnableToObtainLockException $ex) {
			$this->log('Wallet shielding operation is currently in progress, please try again later.');
			return;
		}

		try {
			$this->verus->shield();
		} catch (VerusException $ex) {
			$this->log('VerusException while shielding: ' . $ex->getMessage());
		}

		$lock->release();
	}

	protected function totalGenerated()
	{
		$this->responseJson(['total_generated' => $this->verus->retrieveTotalGenerated()]);
	}

	protected function balance()
	{
		$this->responseJson($this->verus->getBalance());
	}

	protected function balanceEmail()
	{
		$lock = new ExclusiveLock('balance_email', 300);

		try {
			$lock->obtain();
		} catch (UnableToObtainLockException $ex) {
			$this->log('Balance email sending operation is currently in progress, please try again later.');
			return;
		}

		try {
			$mail = new BalanceEmail($this->verus, $this->config);
			$this->log($mail->send());
		} catch (\Exception $ex) {
			$this->log('Unable to send balance e-mail: ' . $ex->getMessage());
		}

		$lock->release();
	}

	protected function backupEmail()
	{
		$lock = new ExclusiveLock('backup_email', 300);

		try {
			$lock->obtain();
		} catch (UnableToObtainLockException $ex) {
			$this->log('Wallet backup email sending operation is currently in progress, please try again later.');
			return;
		}

		try {
			$mail = new BackupEmail($this->verus, $this->config);
			$this->log($mail->send());
		} catch (\Exception $ex) {
			$this->log('Unable to send wallet backup e-mail: ' . $ex->getMessage());
		}

		$lock->release();
	}

	protected function startFresh()
	{
		$this->verus->deleteData();
		$this->log('Success.');
	}
}
