<?php

namespace com\peterbodnar\bsqr;

use com\peterbodnar\bsqr\model;
use com\peterbodnar\bsqr\utils\BsqrCoder;
use com\peterbodnar\bsqr\utils\BsqrCoderException;
use Endroid\QrCode\Writer\Result\ResultInterface;


/**
 * Bysquare facade to encode and render bysqr document
 */
class BySquare
{
	public const LOGO_CENTER = "BOTTOM";
	public const LOGO_RIGHT = "RIGHT";
	public const LOGO_LEFT = "LEFT";

	private int $qrSizePx = 300;
	private string $colorPrimary = "#000000";
	private string $colorSecondary = "#ffffff";

	protected BsqrCoder $bsqrCoder;

	protected ?\Endroid\QrCode\Label\Alignment\LabelAlignmentInterface $labelPosition = null;


	public function __construct()
	{
		$this->bsqrCoder = new BsqrCoder();
	}


	/**
	 * Set logo position
	 *
	 * @param string $logoPosition - Logo position
	 * @throws \Exception
	 * @see self::LOGO_*.
	 *
	 */
	public function setLogoPosition(string $logoPosition): void
	{
		switch ($logoPosition) {
			case self::LOGO_LEFT:
				$this->labelPosition = new \Endroid\QrCode\Label\Alignment\LabelAlignmentLeft();
				break;
			case self::LOGO_RIGHT:
				$this->labelPosition = new \Endroid\QrCode\Label\Alignment\LabelAlignmentRight();
				break;
			case self::LOGO_CENTER:
				$this->labelPosition = new \Endroid\QrCode\Label\Alignment\LabelAlignmentCenter();
				break;
			default:
				throw new \Exception(sprintf('Unsupporteed position %s', $logoPosition));
		}
	}

	public function setQrSizePx(int $qrSizePx): void
	{
		$this->qrSizePx = $qrSizePx;
	}

	/**
	 * @param string $colorPrimary
	 */
	public function setColorPrimary(string $colorPrimary): void
	{
		$this->colorPrimary = $colorPrimary;
	}

	/**
	 * @param string $colorSecondary
	 */
	public function setColorSecondary(string $colorSecondary): void
	{
		$this->colorSecondary = $colorSecondary;
	}

	/**
	 * Include svg image by specified name.
	 *
	 * @param string $name - Name of svg image
	 * @return string Svg string
	 */
	protected function includeSvg($name): string
	{
		$res = file_get_contents(__DIR__ . '/../res/' . $name);
		return str_replace(["{primary}", "{secondary}"], [$this->colorPrimary, $this->colorSecondary], $res);
	}

	/**
	 * Render by square document to svg image.
	 *
	 * @param model\Pay $document - By Square Document
	 * @return \Endroid\QrCode\Writer\Result\ResultInterface
	 * @throws BySquareException
	 */
	public function render(model\Pay $document): ResultInterface
	{
		$tmpLogoFile = sprintf(
			'.caption_%s_%s.svg',
			str_replace('#', '', $this->colorPrimary),
			str_replace('#', '', $this->colorSecondary)
		);

		try {
			$bsqrData = $this->bsqrCoder->encode($document);

			$logo = $this->includeSvg('pay-caption.svg');
			file_put_contents($tmpLogoFile, $logo);

			$qrBuilder = \Endroid\QrCode\Builder\Builder
				::create()
				->writer(new \Endroid\QrCode\Writer\SvgWriter())
				->writerOptions([])
				->data($bsqrData)
				->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
				->errorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh())
				->size($this->qrSizePx)
				->margin(10)
				->roundBlockSizeMode(new \Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin())
				->logoPath($tmpLogoFile)
				->labelText('Pay by Square')
				->labelFont(new \Endroid\QrCode\Label\Font\NotoSans(20))
				->labelAlignment($this->labelPosition);

			if (\Symfony\Component\Filesystem\Path::getExtension($tmpLogoFile, true) === 'svg') {
				$qrBuilder->logoResizeToWidth($this->qrSizePx);
				$qrBuilder->logoResizeToHeight($this->qrSizePx);
			}

			return $qrBuilder->build();
		} catch (BsqrCoderException $ex) {
			throw new BySquareException("Error while encoding bsqr document: " . $ex->getMessage(), 0, $ex);
		} catch (\BaconQrCode\Exception\RuntimeException $ex) {
			throw new BySquareException("Error while encoding data to qr-code matrix: " . $ex->getMessage(), 0, $ex);
		} finally {
			if (file_exists($tmpLogoFile)) {
				unlink($tmpLogoFile);
			}
		}
	}
}
