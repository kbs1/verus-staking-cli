<?php

namespace App\Controllers;

use App\Support\{ExclusiveLock, UnableToObtainLockException};
use App\Emails\NewlyGeneratedCoinsEmail;

class QueuedEmailsController extends Controller
{
	public function index($action = null)
	{
		if ($action == 'send')
			return $this->send();

		return $this->responseJson(['result' => 'invalid-call', 'message' => 'Invalid queued emails operation call.']);
	}

	protected function send()
	{
		$lock = new ExclusiveLock('queued_emails', 300);

		try {
			$lock->obtain();
		} catch (UnableToObtainLockException $ex) {
			$this->log('Queued emails sending operation is currently in progress, please try again later.');
			return;
		}

		try {
			$mail = new NewlyGeneratedCoinsEmail($this->verus, $this->config);
			$this->log($mail->send());
		} catch (\Exception $ex) {
			$this->log('Unable to send queued e-mails: ' . $ex->getMessage());
		}

		$lock->release();
	}
}
