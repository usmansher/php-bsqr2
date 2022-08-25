<?php
/**
 * @author Tormi Talv <tormi.talv@sportlyzer.com> 2022
 * @since 2022-08-25 12:21:00
 * @version 1.0
 */

namespace com\peterbodnar\bsqr\composer;

class CheckSystemExecutables
{
	public static function check(\Composer\Script\Event $event): void
	{
		$required = ['/usr/bin/xz'];
		$notFound = [];
		foreach ($required as $exePath) {
			if (!file_exists($exePath)) {
				$notFound[] = $exePath;
			}
		}

		if (count($notFound) > 0) {
			throw new \Symfony\Component\Filesystem\Exception\FileNotFoundException(null, 0, null, $exePath);
		}
	}
}
