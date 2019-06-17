<?php

namespace App\Emails;

use App\Verus\Verus;
use App\Verus\Exceptions\VerusException;

use PHPMailer\PHPMailer\PHPMailer;

abstract class Email
{
	protected $verus, $config, $storage_path, $mailer;

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
			throw new VerusException('E-mails functionality is disabled, edit config.php to enable.');

		// Instantiation and passing `true` enables exceptions
		$mail = new PHPMailer(true);

		//Server settings
		//$mail->SMTPDebug = 2;									   // Enable verbose debug output
		$mail->isSMTP();											// Set mailer to use SMTP
		$mail->Host	   = $this->config['emails_config']['host'];  // Specify main and backup SMTP servers
		$mail->SMTPAuth   = true;								   // Enable SMTP authentication
		$mail->Username   = $this->config['emails_config']['username']; // SMTP username
		$mail->Password   = $this->config['emails_config']['password']; // SMTP password
		$mail->SMTPSecure = $this->config['emails_config']['security']; // Enable TLS encryption, `ssl` also accepted
		$mail->Port	   = $this->config['emails_config']['port']; // TCP port to connect to

		//Recipients
		$mail->setFrom($this->config['emails_config']['sender_address'], $this->config['emails_config']['sender_name']);
		$mail->addAddress($this->config['emails_config']['recipient']);	 // Add a recipient

		return $mail;
	}
}
