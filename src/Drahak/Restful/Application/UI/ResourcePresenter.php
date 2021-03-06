<?php
namespace Drahak\Restful\Application\UI;

use Drahak\Restful\Application\BadRequestException;
use Drahak\Restful\Application\IResourcePresenter;
use Drahak\Restful\Application\IResponseFactory;
use Drahak\Restful\Http\IInput;
use Drahak\Restful\Http\Request;
use Drahak\Restful\IResourceFactory;
use Drahak\Restful\InvalidStateException;
use Drahak\Restful\IResource;
use Drahak\Restful\Converters;
use Drahak\Restful\Resource\Link;
use Drahak\Restful\Security\AuthenticationContext;
use Drahak\Restful\Security\SecurityException;
use Drahak\Restful\Utils\RequestFilter;
use Drahak\Restful\Validation\IDataProvider;
use Drahak\Restful\Validation\ValidationException;
use Nette\Application;
use Nette\Application\UI;
use Nette\Application\IResponse;
use Nette\Http;

/**
 * Base presenter for REST API presenters
 * @package Drahak\Restful\Application
 * @author Drahomír Hanák
 */
abstract class ResourcePresenter extends UI\Presenter implements IResourcePresenter
{

	/** @internal */
	const VALIDATE_ACTION_PREFIX = 'validate';

	/** @var IResource */
	protected $resource;

	/** @var IInput|IDataProvider */
	protected $input;

	/** @var RequestFilter */
	protected $requestFilter;

	/** @var IResourceFactory */
	protected $resourceFactory;

	/** @var IResponseFactory */
	protected $responseFactory;

	/** @var AuthenticationContext */
	protected $authentication;

	/**
	 * Inject Drahak Restful
	 * @param IResponseFactory $responseFactory
	 * @param IResourceFactory $resourceFactory
	 * @param AuthenticationContext $authentication
	 * @param IInput $input
	 * @param RequestFilter $requestFilter
	 */
	public final function injectDrahakRestful(
		IResponseFactory $responseFactory, IResourceFactory $resourceFactory,
		AuthenticationContext $authentication, IInput $input, RequestFilter $requestFilter)
	{
		$this->responseFactory = $responseFactory;
		$this->resourceFactory = $resourceFactory;
		$this->authentication = $authentication;
		$this->requestFilter = $requestFilter;
		$this->input = $input;
	}

	/**
	 * Presenter startup
	 *
	 * @throws BadRequestException
	 */
	protected function startup()
	{
		parent::startup();
		$this->resource = $this->resourceFactory->create();
		$this->autoCanonicalize = FALSE;

		try {
			// calls $this->validate<Action>()
			$validationProcessed = $this->tryCall($this->formatValidateMethod($this->action), $this->params);

			// Check if input is validate
			if (!$this->input->isValid() && $validationProcessed === TRUE) {
				$errors = $this->input->validate();
				throw BadRequestException::unprocessableEntity($errors, 'Validation Failed: ' . $errors[0]->message);
			}
		} catch (BadRequestException $e) {
			if ($e->getCode() === 422) {
				$this->sendErrorResource($e);
				return;
			}
			throw $e;
		}
	}

	/**
	 * Check security and other presenter requirements
	 * @param $element
	 */
	public function checkRequirements($element)
	{
		try {
			parent::checkRequirements($element);
		} catch (Application\ForbiddenRequestException $e) {
			$this->sendErrorResource($e);
		}

		// Try to authenticate client
		try {
			$this->authentication->authenticate($this->input);
		} catch (SecurityException $e) {
			$this->sendErrorResource($e);
		}
	}

	/**
	 * On before render
	 */
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->sendResource();
	}

	/**
	 * Get REST API response
	 * @param string $contentType
	 * @param int $code
	 * @return IResponse
	 *
	 * @throws InvalidStateException
	 */
	public function sendResource($contentType = NULL, $code = NULL)
	{
		if ($contentType !== NULL) {
			$this->resource->setContentType($contentType);
		}

		if ($code !== NULL) {
			$this->getHttpResponse()->setCode($code);
		}

		try {
			$response = $this->responseFactory->create($this->resource);
			$this->sendResponse($response);
		} catch (InvalidStateException $e) {
			$this->sendErrorResource(BadRequestException::unsupportedMediaType($e->getMessage(), $e));
		}
	}

	/**
	 * Send error resource to output
	 * @param \Exception $e
	 */
	protected function sendErrorResource(\Exception $e)
	{
		/** @var Request $request */
		$request = $this->getHttpRequest();
		$code = $e->getCode() ? $e->getCode() : 500;
		if ($code < 100 || $code > 599) {
			$code = 400;
		}

		$this->resource = $this->resourceFactory->create();
		$this->resource->code = $code;
		$this->resource->status = 'error';
		$this->resource->message = $e->getMessage();
		$this->resource->setContentType(
			$request->getPreferredContentType() ? $request->getPreferredContentType() : IResource::JSON
		);

		if (isset($e->errors) && $e->errors) {
			$this->resource->errors = $e->errors;
		}

		$this->sendResource(NULL, $code);
	}

	/**
	 * Create resource link representation object
	 * @param string $destination
	 * @param array $args
	 * @param string $rel
	 * @return Link
	 */
	public function link($destination, $args = array(), $rel = Link::SELF)
	{
		$href = parent::link($destination, $args);
		return new Link($href, $rel);
	}


	/****************** Format methods ******************/

	/**
	 * Validate action method
	 */
	public static function formatValidateMethod($action)
	{
		return self::VALIDATE_ACTION_PREFIX . $action;
	}

}
