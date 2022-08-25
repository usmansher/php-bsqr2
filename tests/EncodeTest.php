<?php
declare(strict_types=1);

/**
 * @author Tormi Talv <tormi.talv@sportlyzer.com> 2022
 * @since 2022-08-25 19:22:01
 * @version 1.0
 */

namespace Tests;

class EncodeTest extends \PHPUnit\Framework\TestCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		\Tests\Util\TestHelper::prepareOutput();
	}

	/**
	 * @covers \com\peterbodnar\bsqr\BySquare
	 * @return void
	 */
	public function testEncode(): void
	{
		$document = (new \com\peterbodnar\bsqr\model\Pay())
			->setInvoiceId("1234567890")
			->addPayment(
				(new \com\peterbodnar\bsqr\model\Payment())
					->setDueDate("2022-08-31")
					->setAmount(323.45, "EUR")
					->setSymbols("1234567890", "308")
					->addBankAccount("SK3112000000198742637541", "XXXXXXXXXXX")
			// ->setNote("Payment note")
			// ->setOriginatorsReferenceInformation("Originators Reference Information")
			// ->setDirectDebitExt( /* Direct Debit Extension */ )
			// ->setStandingOrderExt( /* Standing Order Extension */ )
			);

		$bySquare = new \com\peterbodnar\bsqr\BySquare();
		$bySquare->setLogoPosition(\com\peterbodnar\bsqr\BySquare::LOGO_CENTER);
		$qrResult = $bySquare->render($document);


		$savedImage = \Tests\Util\TestHelper::getOutputDir() . DIRECTORY_SEPARATOR . 'test_qr.svg';
		$qrResult->saveToFile($savedImage);

		$this->assertFileExists($savedImage);
		$this->assertGreaterThan(30, filesize($savedImage));
	}
}
