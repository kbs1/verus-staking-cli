Introduction
============
These tools will help you stake on your permanently running machine using Verus CLI, instead of running the Agama GUI wallet.

Features
========
- automatic shielding and unshielding of coinbase (staking, solo mining)
- automatic tracking of total coins generated (also present in e-mail notifications)
- optional e-mail notifications on newly generated coins
- optional periodical wallet.dat backups, also as an encrypted ZIP archive
- optional periodical wallet balance e-mail notifications

Initial Verus daemon configuration
==================================
Follow [this guide](https://medium.com/veruscoin/how-to-setup-a-verus-vrsc-staking-server-with-email-alerts-using-ubuntu-and-a-5-digitalocean-4605c6d9ed10)
to set-up your daemon for the first time. You will need a transparent address, a zs address and the daemon running with correct parameters
(`-mint -cheatcatcher=...`).

Do not stake with the same wallet.dat on multiple nodes.

Installation
============
Prerequisites:
1. install PHP 7.0+, use e.g `apt-get install php7.3-cli php7.3-zip` as `root`

Perform the following steps as non-root user (the same user the `verusd` daemon runs as):
1. `cd; git clone https://github.com/kbs1/verus-staking-cli`
2. `cd verus-staking-cli/engine`
3. `cp config.php.EXAMPLE config.php`, edit `config.php`. Read the comments to enable or disable features.
4. [install composer](https://getcomposer.org/download/), run `composer.phar install`
5. configure the following CRON schedule (`crontab -e`). Substitute paths as appropriate. Don't enter entries you do not wish to perform. You can configure cron timings arbitrarily, see the Usage section.
```
0 * * * * /usr/bin/php /home/verus/verus-staking-cli/engine/core.php wallet shield 2>&1 >> /home/verus/SHIELDING_LOG
45 3 * * * /usr/bin/php /home/verus/verus-staking-cli/engine/core.php wallet balanceEmail 2>&1 >> /home/verus/BALANCE_EMAILS_LOG
50 3 * * * /usr/bin/php /home/verus/verus-staking-cli/engine/core.php wallet backupEmail 2>&1 >> /home/verus/BACKUP_EMAILS_LOG
*/5 * * * * /usr/bin/php /home/verus/verus-staking-cli/engine/core.php emails send 2>&1 >> /home/verus/QUEUED_EMAILS_LOG
```

Usage
=====
You do not need to attend the machine. Each newly generated coinbase will be automatically shielded and unshielded. You will receive e-mail notifications
whenever you generate a new block, if you configure them. You may also receive periodical wallet backups, and periodical balance reports.

When you upgrade Verus, you don't have to disable the CRON schedule temporarily. Just check with `ps aux | grep php` to see if the scripts aren't currently
running. Otherwise you risk script failures with an inconsistent state as a result, should the `verusd` daemon become unexpectedly unavailable.

You may alter the crontab to run any scripts at any schedule. For example you can execute the shielding operation every minute if desired. Scripts contain
protections that won't allow critical tasks (such as shielding and unshielding) to overlap, so no harm is caused if scripts are invoked by cron arbitrarily
fast.

To view operations log at any time, simply `tail ..._LOG` to view recent output of that command.

These scripts expect you have all your coins in transparent addresses only. For more advanced usage scenarios, update the source accordingly.
