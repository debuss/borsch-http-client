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

use Psr\Http\Message\RequestInterface;

trait ExceptionWithRequestTrait
{
    /** @var RequestInterface */
    protected $request;

    /**
     * @param RequestInterface $request
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Returns the request.
     * The request object MAY be a different object from the one passed to ClientInterface::sendRequest()
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}