<?php

test('database queue retry_after exceeds the 200s job timeout', function () {
    expect((int) config('queue.connections.database.retry_after'))->toBeGreaterThanOrEqual(240);
});
