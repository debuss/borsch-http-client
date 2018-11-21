<?php
/**
 * This file is part of the Borsch package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package   Borsch\Http
 * @author    Alexandre DEBUSSCHERE (debuss-a)
 * @copyright Copyright (c) Alexandre Debusschere <alexandre@debuss-a.me>
 * @licence   MIT
 */

namespace Borsch\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class Client
 *
 * @package Borsch\Http
 */
class Client implements ClientInterface
{

    /** @var array */
    protected $curl_opts = [];

    /** @var string */
    protected $response_class_name;

    /**
     * Can be a ResponseInterface or a ResponseFactoryInterface.
     *
     * @param string $response_class_name
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setResponseClassName(string $response_class_name): Client
    {
        if (!class_exists($response_class_name)) {
            throw new InvalidArgumentException(sprintf(
                'The provided Response class name [%s] does not exist.',
                $response_class_name
            ));
        }

        $interfaces = class_implements($response_class_name);
        if (!in_array(ResponseInterface::class, $interfaces) &&
            !in_array(ResponseFactoryInterface::class, $interfaces)) {
            throw new InvalidArgumentException(sprintf(
                'The provided Response class name [%s] is not an instance of %s or %s.',
                $response_class_name,
                ResponseInterface::class,
                ResponseFactoryInterface::class
            ));
        }

        $this->response_class_name = $response_class_name;

        return $this;
    }

    /**
     * The class name used to generate a response.
     *
     * @return string
     * @throws RuntimeException
     */
    public function getResponseClassName(): string
    {
        if (!$this->response_class_name) {
            throw new RuntimeException(sprintf(
                'Response class was not provided, you must set one with %s::setResponseClassName().',
                __CLASS__
            ));
        }

        return $this->response_class_name;
    }

    /**
     * @return ResponseInterface
     */
    protected function getResponseInstance(): ResponseInterface
    {
        $response = new $this->response_class_name();

        if ($response instanceof ResponseFactoryInterface) {
            $response = $response->createResponse();
        }

        return $response;
    }

    /**
     * @param int $option
     * @param mixed $value
     * @return $this
     */
    public function setCurlOption(int $option, $value): Client
    {
        $this->curl_opts[$option] = $value;

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     * @see Client::setCurlOption()
     */
    public function setCurlOptions(array $options): Client
    {
        foreach ($options as $option => $value) {
            $this->setCurlOption($option, $value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getCurlOptions(): array
    {
        return $this->curl_opts;
    }

    /**
     * @param string $protocol_version
     * @return int
     */
    protected function getCurlHttpVersion(string $protocol_version): int
    {
        switch (trim($protocol_version)) {
            case '2':
            case '2.0':
                return CURL_HTTP_VERSION_2_0;

            case '1.1':
                return CURL_HTTP_VERSION_1_1;

            default:
                return CURL_HTTP_VERSION_1_0;
        }
    }

    /**
     * @param array $request_headers
     * @return array
     */
    protected function getCurlHeaders(array $request_headers): array
    {
        $headers = [];
        if (count($request_headers)) {
            foreach ($request_headers as $name => $values) {
                $headers[] = sprintf('%s: %s', $name, implode(', ', $values));
            }
        }

        return $headers;
    }

    /**
     * @param RequestInterface $request
     * @return string
     * @throws RequestException
     */
    protected function getCurlPostField(RequestInterface $request): string
    {
        $stream = $request->getBody();

        if ($stream instanceof StreamInterface && $stream->isReadable() && $stream->isSeekable() && $stream->getSize()) {
            try {
                $stream->rewind();

                return $stream->getContents();
            } catch (RuntimeException $e) {
                $request_exception = new RequestException('Unable to read request body.', 0, $e);
                $request_exception->setRequest($request);

                throw $request_exception;
            }
        }

        return '';
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws ClientException
     * @throws NetworkException
     * @throws RequestException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if (!(bool)ini_get('allow_url_fopen')) {
            throw new ClientException('The PHP directive `allow_url_fopen` must be enabled.');
        }

        if (!strlen($request->getUri()->getHost())) {
            $request_exception = new RequestException('Host is missing from the Uri.');
            $request_exception->setRequest($request);

            throw $request_exception;
        }

        if (!strlen($request->getMethod())) {
            $request_exception = new RequestException('Request method is missing.');
            $request_exception->setRequest($request);

            throw $request_exception;
        }

        $response = $this->getResponseInstance();

        $curl = curl_init();
        $options = [
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_URL => $request->getUri(),
            CURLOPT_HTTP_VERSION => $this->getCurlHttpVersion($request->getProtocolVersion()),
            CURLOPT_POSTFIELDS => $this->getCurlPostField($request),
            CURLOPT_HTTPHEADER => $this->getCurlHeaders($request->getHeaders()),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADERFUNCTION => function ($curl, $header_line) use (&$response) {
                if (!strlen(trim($header_line))) {
                    return strlen($header_line);
                }

                if (substr($header_line, 0, 5) == 'HTTP/') {
                    $status = explode(' ', trim($header_line));

                    $response = $response->withStatus(
                        $status[1],
                        $status[2]
                    );
                } else {
                    $header = explode(':', trim($header_line));

                    $response = $response->withHeader(
                        array_shift($header),
                        trim(implode(':', $header))
                    );
                }

                return strlen($header_line);
            }
        ] + $this->getCurlOptions();

        curl_setopt_array($curl, $options);

        $content = curl_exec($curl);
        $error_code = curl_errno($curl);
        $error_message = curl_error($curl);

        curl_close($curl);

        if ($error_code) {
            $network_exception = new NetworkException($error_message, $error_code);
            $network_exception->setRequest($request);

            throw $network_exception;
        }

        try {
            $response->getBody()->write($content);
        } catch (RuntimeException $e) {
            throw new ClientException('Unable to write response body, check body is writable.', 0, $e);
        }

        return $response;
    }
}
