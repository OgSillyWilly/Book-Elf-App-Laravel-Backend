<?php

test('the books API returns successful response', function () {
    $response = $this->get('/api/books');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        '*' => ['id', 'title', 'author', 'type']
    ]);
});
