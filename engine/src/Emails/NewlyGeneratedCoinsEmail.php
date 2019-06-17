<?php

namespace App\Emails;

class NewlyGeneratedCoinsEmail extends Email
{
	public function send()
	{
		if (!$this->shouldSend())
			return "NewlyGeneratedCoinsEmail should not be sent at this time.";

		$this->mailer->Subject = 'Newly generated coins!';
		$this->mailer->Body = "Newly generated coins on your wallet, congratulations!\n\nTotal generated coins: " . $this->verus->retrieveTotalGenerated() . "\n\nYour current wallet balance:\n" . json_encode($this->verus->getBalance(), JSON_PRETTY_PRINT);

		try {
			$this->mailer->send();
		} catch (\Exception $ex) {
			return "NewlyGeneratedCoinsEmail could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}

		@unlink($this->storage_path . '/newly_generated_coins_email.txt');

		return "NewlyGeneratedCoinsEmail successfully sent.";
	}

	protected function shouldSend()
	{
		$timestamp = @file_get_contents($this->storage_path . '/newly_generated_coins_email.txt');

		if ($timestamp === false)
			return false;

		return (int) $timestamp <= time();
	}
}
