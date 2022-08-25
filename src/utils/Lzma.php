<?php

namespace com\peterbodnar\bsqr\utils;


/**
 * BySquare Lzma compressor / decompressor
 */
class Lzma
{
	private string $xzPath;


	/**
	 * @param string $xzPath - Path to xz executable
	 */
	public function __construct($xzPath = "/usr/bin/xz")
	{
		$this->xzPath = $xzPath;
	}


	/**
	 * Compress data.
	 *
	 * @param string $data - Data to compress
	 * @return string
	 * @throws LzmaException
	 */
	public function compress($data)
	{
		$errorMsg = "Data compression failed";
		$sizeBytesLE = pack("v", strlen($data));

		try {
			$command = new \Symfony\Component\Process\Process(
				[
					$this->xzPath,
					'--format',
					'raw',
					'--lzma1=lc=3,lp=0,pb=2,dict=32KiB',
					'-z',
					// '-v', // Use for debugging only. Will output information to stderr.
				],
				null,
				null,
				$data
			);
			$exitCode = $command->run();
			$stdErr = $command->getErrorOutput();
			$stdOutCompressedBin = $command->getOutput();
		} catch (\Symfony\Component\Process\Exception\ProcessFailedException $ex) {
			throw new LzmaException($errorMsg . ": " . $ex->getMessage(), 0, $ex);
		}
		$errorMsg .= " [" . $exitCode . "]";
		if ("" !== $stdErr) {
			throw new LzmaException($errorMsg . ": " . $stdErr);
		}
		if (0 !== $exitCode) {
			throw new LzmaException($errorMsg);
		}

		return $sizeBytesLE . $stdOutCompressedBin;
	}


	/**
	 * Decompress data.
	 *
	 * @param string $data - Compressed data
	 * @return string
	 * @throws LzmaException
	 */
	public function decompress($data)
	{
		$errorMsg = "Data decompression failed";
		$sizeBytesLE = substr($data, 0, 2);
		$dataCompressed = substr($data, 2);
		$size = unpack("v", $sizeBytesLE)[1];

		try {
			$command = new \Symfony\Component\Process\Process(
				[
					$this->xzPath,
					'--format' => 'raw',
					'--lzma1' => 'lc=3,lp=0,pb=2,dict=32KiB',
					'--decompress',
					'-c',
					'-'
				], null, null,
				$dataCompressed
			);
			$exitCode = $command->run();
			$stdErr = $command->getErrorOutput();
			$stdOut = $command->getOutput();
		} catch (\Symfony\Component\Process\Exception\ProcessFailedException $ex) {
			throw new LzmaException($errorMsg . ": " . $ex->getMessage(), 0, $ex);
		}
		if (strlen($stdOut) === $size) {
			return $stdOut;
		}
		$errorMsg .= " [" . $exitCode . "]";
		if ("" !== $stdErr) {
			$errorMsg .= ": " . $stdErr;
		}

		throw new LzmaException($errorMsg);
	}

}
