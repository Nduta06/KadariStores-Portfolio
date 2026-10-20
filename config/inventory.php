<?php

return [
    /*
     * Remaining stock at or below this quantity is flagged "Low" on the
     * Stock Balance page.
     */
    'low_stock_threshold' => (float) env('LOW_STOCK_THRESHOLD', 5),
];
