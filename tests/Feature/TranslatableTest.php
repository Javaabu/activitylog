<?php

namespace Javaabu\Activitylog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Activitylog\Activitylog;
use Javaabu\Activitylog\Models\Activity;
use Javaabu\Activitylog\Support\Diff;
use Javaabu\Activitylog\Support\Translations;
use Javaabu\Activitylog\Tests\TestCase;
use Javaabu\Activitylog\Tests\TestSupport\Models\Article;
use Javaabu\Activitylog\Tests\TestSupport\Models\Page;
use Javaabu\Activitylog\Tests\TestSupport\Models\User;
use Javaabu\Translatable\Testing\InteractsWithLanguages;
use Javaabu\Translatable\TranslatableServiceProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('translatable')]
class TranslatableTest extends TestCase
{
    use InteractsWithLanguages;
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), TranslatableServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLanguages(['en', 'dv'], 'en');

        $this->actingAs(User::factory()->create(['name' => 'Ahmed']));
    }

    #[Test]
    public function translation_changes_are_shown_per_language_and_field(): void
    {
        $article = Article::create(['title' => 'Report', 'lang' => 'en']);

        $article->addTranslation('dv', 'title', 'ރިޕޯޓު')->save();

        $log = $this->latestLog();
        $rows = Diff::forActivity($log)->rows();

        $this->assertEquals(['translations'], $log->changedAttributes());
        $this->assertCount(1, $rows);
        $this->assertEquals('translations.dv.title', $rows[0]['attribute']);
        $this->assertEquals('Title (ދިވެހި)', $rows[0]['label']);
        $this->assertEquals('ރިޕޯޓު', $rows[0]['new']);

        $this->assertEquals('Ahmed set the title (ދިވެހި) of Article Report to ރިޕޯޓު', (string) $log->describe());
    }

    #[Test]
    public function changed_translations_have_their_old_value(): void
    {
        $article = Article::create(['title' => 'Report', 'lang' => 'en']);
        $article->addTranslation('dv', 'title', 'ރިޕޯޓު')->save();
        $article->addTranslation('dv', 'title', 'ފައިނަލް')->save();

        $rows = Diff::forActivity($this->latestLog())->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('ރިޕޯޓު', $rows[0]['old']);
        $this->assertEquals('ފައިނަލް', $rows[0]['new']);
        $this->assertEquals('Ahmed updated the title (ދިވެހި) of Article Report from ރިޕޯޓު to ފައިނަލް', (string) $this->latestLog()->describe());
    }

    #[Test]
    public function rolling_back_restores_translations(): void
    {
        $article = Article::create(['title' => 'Report', 'lang' => 'en']);
        $article->addTranslation('dv', 'title', 'ރިޕޯޓު')->save();
        $version = $this->latestLog();
        $article->addTranslation('dv', 'title', 'ފައިނަލް')->save();

        $rows = $version->rollback()->changes()->expanded()->rows();

        $this->assertEquals('translations.dv.title', $rows[0]['attribute']);
        $this->assertEquals('ފައިނަލް', $rows[0]['old']);
        $this->assertEquals('ރިޕޯޓު', $rows[0]['new']);

        $version->rollback()->perform();

        $this->assertEquals('ރިޕޯޓު', $article->refresh()->translate('title', 'dv'));
    }

    #[Test]
    public function the_history_of_a_db_translated_record_includes_its_languages(): void
    {
        $page = Page::create(['title' => 'About', 'lang' => 'en']);
        $page->addTranslation('dv', 'title', 'އަޅުގަނޑުމެންނާ ބެހޭ');
        Page::create(['title' => 'Other', 'lang' => 'en']);

        $translation = Page::query()->where('lang', 'dv')->first();

        $this->assertNotNull($translation);
        $this->assertEqualsCanonicalizing([$page->id, $translation->id], Translations::relatedKeys($page));
        $this->assertEqualsCanonicalizing([$page->id, $translation->id], Translations::relatedKeys($translation));

        $this->artisan('permissions:sync');

        $user = User::factory()->create();
        $user->givePermissionTo('view_all_logs');

        $history = Activitylog::historyQuery($page, $user)->get();

        $this->assertEqualsCanonicalizing([$page->id, $translation->id], $history->pluck('subject_id')->unique()->all());
    }

    #[Test]
    public function without_languages_translations_are_a_plain_attribute(): void
    {
        $this->withoutTranslatable(function () {
            $article = Article::create(['title' => 'Report', 'lang' => 'en', 'translations' => ['dv' => ['title' => 'ރިޕޯޓު']]]);
            $article->update(['translations' => ['dv' => ['title' => 'ފައިނަލް']]]);

            $rows = Diff::forActivity($this->latestLog())->rows();

            $this->assertFalse(Translations::enabled());
            $this->assertEquals('translations', $rows[0]['attribute']);
        });
    }

    protected function latestLog(): Activity
    {
        return Activity::query()->latest('id')->first();
    }
}
