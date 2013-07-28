<?php
namespace Drahak\Restful\Resource;

use stdClass;
use Traversable;
use Nette\Object;

/**
 * ObjectConverter
 * @package Drahak\Restful\Resource
 * @author Drahomír Hanák
 */
class ObjectConverter extends Object implements IConverter
{

	/**
	 * Converts stdClass and traversable objects in resource to array
	 * @param array $resource
	 * @return array
	 */
	public function convert(array $resource)
	{
		return $this->parseObjects($resource);
	}

	/**
	 * Parse objects in resource
	 * @param array|Traversable|stdClass $data
	 * @return array
	 */
	protected function parseObjects($data)
	{
		if ($data instanceof Traversable) {
			$data = iterator_to_array($data, TRUE);
		} else if ($data instanceof stdClass) {
			$data = (array)$data;
		}

		foreach ($data as $key => $value) {
			if ($value instanceof Traversable || $value instanceof stdClass || is_array($value)) {
				$data[$key] = $this->parseObjects($value);
			}
		}
		return $data;
	}

}