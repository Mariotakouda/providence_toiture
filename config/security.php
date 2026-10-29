<?php

return [
    // true = Content-Security-Policy-Report-Only (test sans blocage) ; false = politique appliquée.
    'csp_report_only' => (bool) env('CSP_REPORT_ONLY', false),
];
