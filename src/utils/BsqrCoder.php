<?php

namespace com\peterbodnar\bsqr\utils;

use com\peterbodnar\bsqr\model;


/**
 * Bysquare data encoder / parser
 */
class BsqrCoder
{
	/** @var ClientDataEncoder - BySquare serializer. */
	protected $cldEncoder;
	/** @var ClientDataParser - BySquare parser. */
	protected $cldParser;
	/** @var Lzma - Lzma compressor / decompressor. */
	protected $lzma;


	/**
	 * CRC32 hash.
	 *
	 * @param string $data
	 * @return string
	 */
	protected function crc32hash($data)
	{
		return strrev(hash("crc32b", $data, true));
	}


	public function __construct()
	{
		$this->cldEncoder = new ClientDataEncoder();
		$this->cldParser = new ClientDataParser();
		$this->lzma = new Lzma();
	}


	/**
	 * Encode document.
	 *
	 * @param model\Pay $document - Document to encode.
	 * @return string
	 * @throws BsqrCoderException
	 */
	public function encode(model\Pay $document)
	{
		$head = "\x00\x00";
		$clientData = $this->cldEncoder->encodePay($document);

		$clDataHash = $this->crc32hash($clientData);
		try {
			$lzmEncoded = $this->lzma->compress($clDataHash . $clientData);
		} catch (LzmaException $ex) {
			throw new BsqrCoderException("LZMA compression failed: " . $ex->getMessage(), 0, $ex);
		}

		return \ParagonIE\ConstantTime\Base32::encode($head . $lzmEncoded);
	}


	/**
	 * Parse document data.
	 *
	 * @param string $input - Data.
	 * @return model\Document
	 * @throws BsqrCoderException
	 */
	public function parse($input)
	{
		try {
			$b32decoded = \ParagonIE\ConstantTime\Base32::decode($input);
		} catch (\Exception $ex) {
			throw new BsqrCoderException("Base 32 decoding failed: " . $ex->getMessage(), 0, $ex);
		}

		$head = substr($b32decoded, 0, 2);
		$body = substr($b32decoded, 2);
		if ("\x00\x00" === $head) {
			$documentClass = model\Pay::class;
		} else {
			throw new BsqrCoderException("Unknown document type (0x" . bin2hex($head) . ").");
		}

		try {
			$lzmDecoded = $this->lzma->decompress($body);
		} catch (LzmaException $ex) {
			throw new BsqrCoderException("LZMA decompression failed: " . $ex->getMessage(), 0, $ex);
		}

		$clDataHash = substr($lzmDecoded, 0, 4);
		$clientData = substr($lzmDecoded, 4);
		if ($this->crc32hash($clientData) !== $clDataHash) {
			throw new BsqrCoderException("CRC32 hash does not match.");
		}

		try {
			$document = $this->cldParser->parse($documentClass, $clientData);
		} catch (ParserException $ex) {
			throw new BsqrCoderException("Client data parsing failed.", 0, $ex);
		}
		return $document;
	}

}
