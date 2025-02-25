<?php

namespace Tests\Entity;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\Page;
use Tests\TestCase;

class PageEditorTest extends TestCase
{
    /** @var Page */
    protected $page;

    protected function setUp(): void
    {
        parent::setUp();
        $this->page = Page::query()->first();
    }

    public function test_default_editor_is_wysiwyg()
    {
        $this->assertEquals('wysiwyg', setting('app-editor'));
        $this->asAdmin()->get($this->page->getUrl() . '/edit')
            ->assertElementExists('#html-editor');
    }

    public function test_markdown_setting_shows_markdown_editor()
    {
        $this->setSettings(['app-editor' => 'markdown']);
        $this->asAdmin()->get($this->page->getUrl() . '/edit')
            ->assertElementNotExists('#html-editor')
            ->assertElementExists('#markdown-editor');
    }

    public function test_markdown_content_given_to_editor()
    {
        $this->setSettings(['app-editor' => 'markdown']);

        $mdContent = '# hello. This is a test';
        $this->page->markdown = $mdContent;
        $this->page->save();

        $this->asAdmin()->get($this->page->getUrl() . '/edit')
            ->assertElementContains('[name="markdown"]', $mdContent);
    }

    public function test_html_content_given_to_editor_if_no_markdown()
    {
        $this->setSettings(['app-editor' => 'markdown']);
        $this->asAdmin()->get($this->page->getUrl() . '/edit')
            ->assertElementContains('[name="markdown"]', $this->page->html);
    }

    public function test_empty_markdown_still_saves_without_error()
    {
        $this->setSettings(['app-editor' => 'markdown']);
        /** @var Book $book */
        $book = Book::query()->first();

        $this->asEditor()->get($book->getUrl('/create-page'));
        $draft = Page::query()->where('book_id', '=', $book->id)
            ->where('draft', '=', true)->first();

        $details = [
            'name'     => 'my page',
            'markdown' => '',
        ];
        $resp = $this->post($book->getUrl("/draft/{$draft->id}"), $details);
        $resp->assertRedirect();

        $this->assertDatabaseHas('pages', [
            'markdown' => $details['markdown'],
            'id'       => $draft->id,
            'draft'    => false,
        ]);
    }
}
