<?php

use App\Support\Markdown;

test('markdown unwraps fenced replies and renders lists', function () {
    $html = Markdown::render(<<<'MD'
```markdown
**Done**

- Flour Low - stock: 1
- Sugar - stock: 2
```
MD);

    expect($html)
        ->toContain('<strong>Done</strong>')
        ->toContain('<ul>')
        ->toContain('<li>')
        ->not->toContain('```');
});

test('markdown tables render from associative rows', function () {
    $table = Markdown::table([
        ['id' => 1, 'name' => 'Flour', 'stock_label' => '1 g'],
        ['id' => 2, 'name' => 'Sugar', 'stock_label' => '2 g', 'admin_url' => 'https://example.test'],
    ], ['id', 'name', 'stock_label']);

    $html = Markdown::render($table);

    expect($table)->toContain('| id | name | stock_label |')
        ->and($table)->not->toContain('admin_url')
        ->and($html)->toContain('<table>')
        ->and($html)->toContain('<th>name</th>')
        ->and($html)->toContain('<td>Flour</td>');
});

test('markdown turns key value blocks into bullets', function () {
    $html = Markdown::render("Summary\n\nrevenue: Rs. 10,000\norders: 4\nmargin: 22%");

    expect($html)
        ->toContain('<strong>revenue:</strong>')
        ->toContain('<strong>orders:</strong>')
        ->toContain('<ul>');
});
