<?php
namespace Drahak\Restful;

use ArrayAccess;
use Serializable;
use ArrayIterator;
use IteratorAggregate;
use Nette\Object;
use Nette\Utils\Json;
use Nette\MemberAccessException;

/**
 * REST resource
 * @package Drahak\Restful
 * @author Drahomír Hanák
 *
 * @property string $contentType Allowed result content type
 * @property-read array $data
 */
class Resource extends Object implements ArrayAccess, Serializable, IteratorAggregate, IResource
{

	/** @var string */
	private $contentType;

	/** @var array */
	private $data = array();

	/**
	 * @param array $data
	 */
	public function __construct(array $data = array())
	{
		$this->data = $data;
	}

	/**
	 * Set result content type
	 * @param string $mime
	 * @return Resource
	 */
	public function setContentType($mime)
	{
		$this->contentType = $mime;
		return $this;
	}

	/**
	 * Get result mime type
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Get result set data
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/******************** Serializable ********************/

	/**
	 * Serialize result set
	 * @return string
	 */
	public function serialize()
	{
		return Json::encode($this->data);
	}

	/**
	 * Unserialize Resource
	 * @param string $serialized
	 */
	public function unserialize($serialized)
	{
		$this->data = Json::decode($serialized);
	}

	/******************** ArrayAccess interface ********************/

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->data[$offset];
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		if ($offset === NULL) {
			$offset = count($this->data);
		}
		$this->data[$offset] = $value;
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}

	/******************** Iterator aggregate interface ********************/

	/**
	 * Get resource data iterator
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->getData());
	}

	/******************** Magic methods ********************/

	/**
	 * Magic getter from $this->data
	 * @param string $name
	 *
	 * @throws \Exception|\Nette\MemberAccessException
	 * @return mixed
	 */
	public function &__get($name)
	{
		try {
			return parent::__get($name);
		} catch (MemberAccessException $e) {
			if (isset($this->data[$name])) {
				return $this->data[$name];
			}
			throw $e;
		}

	}

	/**
	 * Magic setter to $this->data
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		try {
			parent::__set($name, $value);
		} catch (MemberAccessException $e) {
			$this->data[$name] = $value;
		}
	}

	/**
	 * Magic isset to $this->data
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return !parent::__isset($name) ? isset($this->data[$name]) : TRUE;
	}

	/**
	 * Magic unset from $this->data
	 * @param string $name
	 * @throws \Exception|\Nette\MemberAccessException
	 */
	public function __unset($name)
	{
		try {
			parent::__unset($name);
		} catch (MemberAccessException $e) {
			if (isset($this->data[$name])) {
				unset($this->data[$name]);
				return;
			}
			throw $e;
		}
	}


}
