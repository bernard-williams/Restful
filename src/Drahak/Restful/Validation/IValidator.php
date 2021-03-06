<?php
namespace Drahak\Restful\Validation;

/**
 * Validator interface
 * @package Drahak\Restful\Validation
 * @author Drahomír Hanák
 */
interface IValidator
{

	// Equality rules
	const EQUAL = ':equal';
	const IS_IN = ':equal';
	const OPTIONAL = 'optional';

	// Textual rules
	const MIN_LENGTH = 'string:%d..';
	const MAX_LENGTH = 'string:..%d';
	const LENGTH = 'string:%d..%d';
	const EMAIL = ':email';
	const URL = ':url';
	const REGEXP = ':regexp';
	const PATTERN = ':regexp'; // same as regexp

	// Numeric rules
	const INTEGER = 'int';
	const NUMERIC = 'numeric';
	const FLOAT = 'float';
	const RANGE = 'numeric:%d..%d';

	// Special
	const UUID = 'uuid';

	/**
	 * Validate value with rule
	 * @param mixed $value
	 * @param Rule $rule
	 * @return void
	 *
	 * @throws ValidationException
	 */
	public function validate($value, Rule $rule);

}
