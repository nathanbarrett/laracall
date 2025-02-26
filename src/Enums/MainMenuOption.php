<?php declare(strict_types=1);

namespace NathanBarrett\LaraCall\Enums;

enum MainMenuOption: string
{
    case SEND_REQUEST = 'Send Request';
    case EDIT_URL = 'Edit URL';
    case EDIT_METHOD = 'Edit Method';
    case EDIT_HEADERS = 'Edit Headers';
    case EDIT_BODY = 'Edit Body';
    case EDIT_QUERY_PARAMS = 'Edit Query Params';
    case EDIT_COOKIES = 'Edit Cookies';
    case EDIT_AUTH = 'Edit Auth';
    case EDIT_REQUEST_NAME = 'Edit Request Name';
}
