<?php

namespace CTI\RestClientBundle;

use Guzzle\Service\Client;
use Guzzle\Service\Command\DefaultResponseParser;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Service\Description\ServiceDescriptionInterface;
use JMS\Serializer\SerializerBuilder;
use Misd\GuzzleBundle\Service\Command\JMSSerializerResponseParser;
use Misd\GuzzleBundle\Service\Command\LocationVisitor\Request\JMSSerializerBodyVisitor;
use Symfony\Component\HttpFoundation\Response;
use CTI\RestClientBundle\Services\ResourceLocator;
use CTI\RestClientBundle\Exception\ResponseException;
use Guzzle\Http\Exception\RequestException;

/**
 * Rest client contains basic common functionality needed when making calls to RESTful API
 *
 * @package Teesnap\RestClientBundle
 * @author  Georgiana Gligor <georgiana@cloudtroopers.com>
 */
class RestClient
{

    /**
     * @var \Guzzle\Service\Client
     */
    protected $client;

    /**
     * @var ResourceLocator
     */
    protected $locator;

    /**
     * Class constructor
     *
     * @param int    $guzzleTimeout
     */
    public function __construct($guzzleTimeout)
    {
        $this->client = new Client();
        $this->client->setDefaultOption('verify', false);
        $this->client->setDefaultOption('timeout', $guzzleTimeout);
    }

    /**
     * Decorate internal client object with authentification params
     *
     * @param string $username
     * @param string $password
     */
    public function authenticate($username = '', $password = '')
    {
        $this->client->setDefaultOption(
            'auth',
            array($username, $password, 'Basic')
        );
    }

    /**
     * @param ResourceLocator $locator
     */
    public function setLocator(ResourceLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @return \Guzzle\Http\Url|string
     */
    public function getBaseUrl()
    {
        return $this->client->getBaseUrl();
    }

    /**
     * @param string $baseUrl
     *
     * @return RestClient
     */
    public function setBaseUrl($baseUrl)
    {
        $this->client->setBaseUrl($baseUrl);

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->client->getDefaultOption('timeout');
    }

    /**
     * @param int $timeout
     *
     * @return RestClient
     */
    public function setTimeout($timeout)
    {
        $this->client->setDefaultOption('timeout', $timeout);

        return $this;
    }

    /**
     * @param ServiceDescriptionInterface $description
     */
    public function setDescription($description)
    {
        if (is_string($description)) {
            // TODO prettify this ugliness
            $path = $this->locator->locate('@' . $description);
            $description = ServiceDescription::factory($path);
        }

        $this->client->setDescription($description);
    }

    /**
     * @param string $uri
     *
     * @return \Guzzle\Http\Message\RequestInterface
     */
    public function get($uri)
    {
        return $this->client->get($uri);
    }

    /**
     * Execute given operation as defined in the service description file
     *
     * @param string $operationName
     * @param array  $parameters
     *
     * @throws Exception\ResponseException
     *
     * @return mixed
     */
    public function execute($operationName, $parameters = array())
    {
        // TODO validate $operationName

        /** @var \Guzzle\Service\Command\OperationCommand $command */
        $command = $this->client->getCommand($operationName, $parameters);

        $serializer = SerializerBuilder::create()->build();
        $defaultParser = new DefaultResponseParser();
        $responseSerializer = new JMSSerializerResponseParser($serializer, $defaultParser);
        $command->setResponseParser($responseSerializer);

        $jmsBodyVisitor = new JMSSerializerBodyVisitor($serializer);
        $command->getRequestSerializer()->addVisitor('body', $jmsBodyVisitor);

        //TODO cleanup the current exception handling

        try {
            $result = $command->execute();
        } catch (RequestException $e) {
            $request = $e->getRequest();
            if (empty($request)) {
                throw new ResponseException($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $e);
            }
            $response = $request->getResponse();
            if (!empty($response)) {
                $statusCode = $response->getStatusCode();
                $body = $response->getBody(true);
                if (!empty($body)) {
                    $message = $body;
                } else {
                    $message = $e->getMessage();
                }
            } else {
                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                $message = $e->getMessage();
            }
            throw new ResponseException($message, $statusCode, $e);
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new ResponseException($e->getMessage(), $statusCode, $e);
        }

        return $result;
    }

    /**
     * Execute several operations in parallel
     *
     * @param Operation[] $operations
     *
     * @throws Exception\ResponseException
     *
     * @return mixed
     */
    public function executeParallel($operations = array())
    {
        $processed = array();
        foreach ($operations as $operation) {
            $processed[] = $this->client->getCommand($operation->getName(), $operation->getArguments());
        }

        try {
            $result = $this->client->execute($processed);
        } catch (\Exception $e) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new ResponseException($e->getMessage(), $statusCode, $e);
        }

        return $result;
    }

    /**
     * @param mixed $subscriber
     */
    public function addSubscriber($subscriber)
    {
        $this->client->addSubscriber($subscriber);
    }

}
