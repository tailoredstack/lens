<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Tempest\Http\Get;
use Tempest\Http\Post;

final class TestController
{
    #[Get('/test')]
    public function index(): array
    {
        return [];
    }

    #[Post('/test')]
    public function store(array $data): array
    {
        return [];
    }
}
