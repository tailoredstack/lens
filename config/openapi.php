<?php

declare(strict_types=1);

return new \Lens\Config\OpenApiConfig(
    sources: [],
    title: 'My API',
    version: '0.0.0',
    basePath: '/api',
    exclude: [],
    includeInternal: false,
    outputFormat: 'json',
);
