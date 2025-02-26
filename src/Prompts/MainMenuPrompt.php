<?php declare(strict_types=1);

namespace NathanBarrett\LaraCall\Prompts;

use NathanBarrett\LaraCall\Data\RouteCallContext;
use NathanBarrett\LaraCall\Enums\MainMenuOption;
use function Laravel\Prompts\clear;

class MainMenuPrompt
{
    private int $promptCount = 0;

    public function __construct()
    {
        //
    }

    public function prompt(RouteCallContext $context): MainMenuOption
    {
        $this->promptCount++;

        clear();
    }

    private function renderRequestDetails(RouteCallContext $context): void
    {
        $headers = ['Option', 'Value'];
        $rows = [
            [
                'option' => 'Uri',
                'value' => $context->routeUri ?? 'Not set',
            ],
            [
                'option' => 'Method',
                'value' => $context->routeMethod?->value ?? 'Not set',
            ],
            [
                'parameter' => 'Parameters',
                'value' => $context->parameters ? json_encode($context->parameters) : 'Not set',
            ],
            [
                'option' => 'Headers',
                'value' => $context->headers ? json_encode($context->headers) : 'Not set',
            ],
            [
                'option' => 'Cookies',
                'value' => $context->cookies ? json_encode($context->cookies) : 'Not set',
            ],
            [
                'option' => 'Body',
                'value' => $context->body ? json_encode($context->body) : 'Not set',
            ],
            [
                'option' => 'Without Middleware',
                'value' => $context->withoutMiddleware ? 'Yes' : 'No',
            ],
//            [
//                'option' => 'Acting As Identifier',
//                'value' => $context->actingAsIdentifier ?? 'Not set',
//            ],
//            [
//                'option' => 'Acting As Entity Class',
//                'value' => $context->actingAsEntityClass ?? 'Not set',
//            ],
//            [
//                'option' => 'Acting As Property',
//                'value' => $context->actingAsProperty ?? 'Not set',
//            ],
        ];
    }
}
