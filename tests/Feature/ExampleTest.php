<?php

test('the application home page is available', function () {
    $this->get(route('home'))->assertOk();
});
