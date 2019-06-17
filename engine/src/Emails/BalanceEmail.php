<?php

namespace App\Emails;

class BalanceEmail extends Email
{
	public function send()
	{
		$this->mailer->Subject = 'Your wallet balance';
		$this->mailer->Body = "Your current wallet balance:\n" . json_encode($this->verus->getBalance(), JSON_PRETTY_PRINT);

		try {
			$this->mailer->send();
		} catch (\Exception $ex) {
			return "BalanceEmail could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}

		return "BalanceEmail successfully sent.";
	}
}
