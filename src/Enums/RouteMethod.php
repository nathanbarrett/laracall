<?php declare(strict_types=1);

namespace NathanBarrett\LaraCall\Enums;

enum RouteMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case HEAD = 'HEAD';
    case ANY = 'ANY';
}
