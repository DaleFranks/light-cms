<?php

namespace Tests\Feature;

use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Forms\RelationManagers\SubmissionsRelationManager;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormSubmissionsAdminTest extends TestCase
{
    use RefreshDatabase;

    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->form = Form::factory()->create(['name' => 'Contact', 'fields' => [
            ['label' => 'Name', 'type' => 'text'],
            ['label' => 'Extras', 'type' => 'checkboxes', 'choices' => ['A', 'B']],
        ]]);
    }

    private function submit(array $answers): FormSubmission
    {
        return $this->form->submissions()->create(['answers' => $answers]);
    }

    public function test_submissions_are_listed_with_a_column_per_field(): void
    {
        $submission = $this->submit([
            'name' => ['label' => 'Name', 'value' => 'Ann'],
            'extras' => ['label' => 'Extras', 'value' => ['A', 'B']],
        ]);

        Livewire::test(SubmissionsRelationManager::class, ['ownerRecord' => $this->form, 'pageClass' => EditForm::class])
            ->assertCanSeeTableRecords([$submission])
            ->assertTableColumnExists('answer_name')
            ->assertTableColumnStateSet('answer_extras', 'A, B', $submission);
    }

    public function test_a_submission_can_be_deleted(): void
    {
        $submission = $this->submit(['name' => ['label' => 'Name', 'value' => 'Ann']]);

        Livewire::test(SubmissionsRelationManager::class, ['ownerRecord' => $this->form, 'pageClass' => EditForm::class])
            ->callAction(TestAction::make(DeleteAction::class)->table($submission));

        $this->assertModelMissing($submission);
    }

    public function test_submissions_download_as_csv_including_answers_to_removed_fields(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 0));
        $this->submit([
            'name' => ['label' => 'Name', 'value' => '=HYPERLINK("http://evil")'],
            'phone' => ['label' => 'Phone', 'value' => '555-0100'],
        ]);
        $this->travelTo(now()->setDate(2026, 10, 2)->setTime(9, 0));
        $this->submit([
            'name' => ['label' => 'Name', 'value' => 'Bob, Jr.'],
            'extras' => ['label' => 'Extras', 'value' => ['A', 'B']],
        ]);

        $response = Livewire::test(SubmissionsRelationManager::class, ['ownerRecord' => $this->form, 'pageClass' => EditForm::class])
            ->callTableAction('download')
            ->assertFileDownloaded('contact-submissions-2026-10-02.csv');

        $this->assertSame(
            "Sent,Name,Extras,Phone\n"
            ."\"2026-10-01 09:00:00\",\"'=HYPERLINK(\"\"http://evil\"\")\",,555-0100\n"
            ."\"2026-10-02 09:00:00\",\"Bob, Jr.\",\"A, B\",\n",
            base64_decode($response->effects['download']['content']),
        );
    }

    public function test_submissions_can_be_downloaded_from_the_top_of_the_forms_edit_screen(): void
    {
        $this->submit(['name' => ['label' => 'Name', 'value' => 'Ann']]);

        Livewire::test(EditForm::class, ['record' => $this->form->getRouteKey()])
            ->assertActionEnabled('downloadSubmissions')
            ->callAction('downloadSubmissions')
            ->assertFileDownloaded();
    }

    public function test_the_edit_screen_download_is_disabled_until_something_is_sent(): void
    {
        Livewire::test(EditForm::class, ['record' => $this->form->getRouteKey()])
            ->assertActionDisabled('downloadSubmissions');
    }

    public function test_the_forms_list_offers_a_download_only_for_forms_with_submissions(): void
    {
        $this->submit(['name' => ['label' => 'Name', 'value' => 'Ann']]);
        $unused = Form::factory()->create();

        Livewire::test(ListForms::class)
            ->assertActionHidden(TestAction::make('downloadSubmissions')->table($unused))
            ->assertActionVisible(TestAction::make('downloadSubmissions')->table($this->form))
            ->callAction(TestAction::make('downloadSubmissions')->table($this->form))
            ->assertFileDownloaded();
    }

    public function test_deleting_a_form_warns_how_many_submissions_go_with_it(): void
    {
        $this->submit(['name' => ['label' => 'Name', 'value' => 'Ann']]);
        $this->submit(['name' => ['label' => 'Name', 'value' => 'Bob']]);

        Livewire::test(EditForm::class, ['record' => $this->form->getRouteKey()])
            ->mountAction(DeleteAction::class)
            ->assertActionMounted(DeleteAction::class)
            ->assertMountedActionModalSee('Its 2 submissions will be deleted too.')
            ->callMountedAction();

        $this->assertModelMissing($this->form);
        $this->assertDatabaseCount('form_submissions', 0);
    }
}
