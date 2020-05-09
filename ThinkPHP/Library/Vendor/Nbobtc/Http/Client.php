<?php
/**
 * @author Joshua Estes
 * @copyright 2012-2015 Joshua Estes
 * @license https://github.com/nbobtc/bitcoind-php/blob/2.x/LICENSE MIT
 */

namespace Nbobtc\Http;

use Nbobtc\Command\CommandInterface;
use Nbobtc\Http\Driver\CurlDriver;
use Nbobtc\Http\Driver\DriverInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Request;

/**
 * @since 2.0.0
 */
class Client implements ClientInterface
{
    /**
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * @var \Nbobtc\Http\Driver\DriverInterface
     */
    protected $driver;

    /**
     * Creates a new Client object
     *
     * Currently you MUST pass in a DSN so the client knows where to send
     * commands to.
     *
     * ```php
     * $client = new \Nbobtc\Http\Client('https://username:password@localhost:18332');
     * ```
     *
     * @since 2.0.0
     * @param string $dsn Data Source Name
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($dsn)
    {
        $this->driver  = new CurlDriver();
        $this->request = (new Request($dsn))->withHeader('Content-Type', 'application/json');
    }

    /**
     * @since 2.0.0
     * {@inheritdoc}
     */
    public function sendCommand(CommandInterface $command)
    {        
        $body = new \Zend\Diactoros\Stream('php://temp', 'w+');
        $body->write(json_encode(
            array(
                'method' => $command->getMethod(),
                'params' => $command->getParameters(),
                'id'     => $command->getId(),
            )
        ));

        $request = $this->request->withBody($body);
        
        /** @var \Psr\Http\Message\ResponseInterface */
        $this->response = $this->driver->execute($request);

        return $this->response;
    }

    /**
     * Configures the Client to use a specific driver
     *
     * @since 2.0.0
     * @param \Nbobtc\Http\Driver\DriverInterface $driver
     * @return self
     */
    public function withDriver(DriverInterface $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Return the current Request object
     *
     * @since 2.0.0
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the current Response object
     *
     * @since 2.0.0
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
