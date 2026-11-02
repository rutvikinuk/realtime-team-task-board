<?php

return [

    /*
    | Maximum API requests per minute for one client IP. Raise it with
    | API_RATE_LIMIT if you sit behind a shared proxy or run load tests.
    */
    'api_rate_limit' => (int) env('API_RATE_LIMIT', 120),

];
