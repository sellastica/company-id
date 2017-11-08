<?php
namespace Sellastica\CompanyId;

use Sellastica\CompanyId\Exception;

class CompanyId
{
	const FORMAT_VISUALLY = 1,
		FORMAT_CONDENSED = 2;

	/** @var string */
	private $originalCompanyId;
	/** @var string */
	private $companyId;
	/** @var string */
	private $countryCode;
	/** @var array */
	private $data;


	/**
	 * @param string $companyId
	 * @param string $countryCode
	 */
	public function __construct(string $companyId, string $countryCode)
	{
		$this->originalCompanyId = $companyId;
		$this->companyId = $this->replaceUnknownCharacters($companyId);
		$this->countryCode = strtoupper($countryCode);
	}

	/**
	 * @return bool
	 */
	public function isValid(): bool
	{
		return preg_match('~^' . $this->getData('pattern') . '$~i', $this->companyId);
	}

	/**
	 * @throws Exception\CompanyIdValidationException
	 */
	public function validate(): void
	{
		if (!$this->isValid()) {
			throw new Exception\CompanyIdValidationException();
		}
	}

	/**
	 * @param int $format
	 * @return string
	 */
	public function format(int $format = self::FORMAT_CONDENSED): string
	{
		$formatPattern = $format === self::FORMAT_CONDENSED
			? $this->getData('formatCondensed')
			: $this->getData('formatVisually');
		return preg_replace(
			'~^' . $this->getData('pattern') . '$~i',
			$formatPattern,
			$this->getData('uppercase') ? strtoupper($this->companyId) : $this->companyId
		);
	}

	/**
	 * @param string $companyId
	 * @return string
	 */
	private function replaceUnknownCharacters(string $companyId): string
	{
		return preg_replace('~[^0-9a-zA-Z]~', '', $companyId);
	}

	/**
	 * @param string|null $key
	 * @return array|string
	 * @throws Exception\MissingCompanyIdDataException
	 */
	private function getData(string $key = null)
	{
		if (!isset($this->data)) {
			$file = __DIR__ . '/data/' . $this->countryCode . '.php';
			if (!file_exists($file)) {
				throw new Exception\MissingCompanyIdDataException();
			}

			$this->data = include($file);
		}

		return $key ? ($this->data[$key] ?? null) : $this->data;
	}

	/**
	 * @param string $companyId
	 * @param string $countryCode
	 * @param int $format
	 * @return string
	 */
	public static function formatIfPossible(
		string $companyId,
		string $countryCode,
		int $format = self::FORMAT_CONDENSED
	): string
	{
		try {
			$companyIdObject = new self($companyId, $countryCode);
			$companyIdObject->validate();
			return $companyIdObject->format($format);
		} catch (Exception\MissingCompanyIdDataException $e) {
			return $companyId;
		}
	}
}