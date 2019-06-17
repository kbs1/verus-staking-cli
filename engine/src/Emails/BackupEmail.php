<?php

namespace App\Emails;

use ZipArchive;

class BackupEmail extends Email
{
	public function send()
	{
		$path = $this->config['wallet_path'] ?? null;

		if (!$path)
			return 'BackupEmail is not enabled in config.php, wallet path missing.';

		if (!@is_file($path))
			return 'BackupEmail configuration error, wallet path is not a regular file.';

		$this->mailer->Subject = 'Your wallet backup';
		$this->mailer->Body = "Attached is your wallet backup. Your current wallet balance:\n" . json_encode($this->verus->getBalance(), JSON_PRETTY_PRINT);

		$password = (string) ($this->config['backup_zip_password'] ?? '');

		@unlink($zip_path = $this->storage_path . '/wallet_backup.zip');
		$zip = new ZipArchive();

		if ($zip->open($zip_path, ZipArchive::CREATE) === true) {
			if ($password !== '')
				$zip->setPassword($password); //set default password

			$zip->addFile($path, basename($path));
			if ($password !== '')
				$zip->setEncryptionName(basename($path), ZipArchive::EM_AES_256);

			$zip->close();

			$this->mailer->addAttachment($zip_path);
		}

		try {
			$this->mailer->send();
		} catch (\Exception $ex) {
			return "BackupEmail could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}

		return "BackupEmail successfully sent.";
	}
}
