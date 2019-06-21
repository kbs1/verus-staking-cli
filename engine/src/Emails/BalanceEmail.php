<?php

namespace App\Emails;

class BalanceEmail extends Email
{
	public function send()
	{
		$this->mailer->Subject = ($this->sender_name !== '' ? '[' . $this->sender_name . '] ' : '') . 'Your wallet balance';
		$this->mailer->Body = "Your current wallet balance:\n" . json_encode($this->verus->getBalance(), JSON_PRETTY_PRINT) . "\n\nTotal generated coins: " . $this->verus->retrieveTotalGenerated();

		try {
			$this->mailer->send();
		} catch (\Exception $ex) {
			return "BalanceEmail could not be sent. Mailer Error: {$this->mailer->ErrorInfo}";
		}

		return "BalanceEmail successfully sent.";
	}
}
