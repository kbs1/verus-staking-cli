<?php

namespace App\Emails;

use App\Verus\Verus;
use App\Verus\Exceptions\VerusException;

use PHPMailer\PHPMailer\PHPMailer;

abstract class Email
{
	protected $verus, $config, $storage_path, $mailer, $sender_name;

	public function __construct(Verus $verus, array $config)
	{
		$this->verus = $verus;
		$this->config = $config;
		$this->storage_path = __ROOT__ . '/storage';
		$this->mailer = $this->getMailerInstance();
	}

	abstract public function send();

	protected function getMailerInstance()
	{
		if (!($this->config['emails_config']['host'] ?? false))
			throw new VerusException('E-mails functionality is disabled, edit config.php to enable it.');

		// passing true enables exceptions
		$mail = new PHPMailer(true);

		//$mail->SMTPDebug = 2;
		$mail->isSMTP();
		$mail->Host = $this->config['emails_config']['host'];
		$mail->SMTPAuth = true;
		$mail->Username = $this->config['emails_config']['username'];
		$mail->Password = $this->config['emails_config']['password'];
		$mail->SMTPSecure = $this->config['emails_config']['security'];
		$mail->Port = $this->config['emails_config']['port'];

		//Recipients
		$this->sender_name = (string) ($this->config['emails_config']['sender_name'] ?? '');

		if ($this->sender_name !== '')
			$mail->setFrom($this->config['emails_config']['sender_address'], $this->sender_name);
		else
			$mail->setFrom($this->config['emails_config']['sender_address']);

		$mail->addAddress($this->config['emails_config']['recipient']);

		return $mail;
	}
}
