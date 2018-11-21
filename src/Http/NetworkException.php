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

use Psr\Http\Client\NetworkExceptionInterface;
use Exception;

/**
 * Class NetworkException
 *
 * @package Borsch\Http
 */
class NetworkException extends Exception implements NetworkExceptionInterface
{
    use ExceptionWithRequestTrait;
}
