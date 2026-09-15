<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Services\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FormTemplateController extends Controller
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->value());

        return Inertia::render('admin/form-templates', [
            'filters' => compact('search'),
            'templates' => FormTemplate::query()
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($templateQuery) use ($search): void {
                        $templateQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('subject', 'like', "%{$search}%")
                            ->orWhere('body', 'like', "%{$search}%");
                    });
                })
                ->orderBy('id')
                ->get()
                ->map(fn (FormTemplate $template): array => $this->templatePayload($template)),
        ]);
    }

    public function edit(FormTemplate $formTemplate): Response
    {
        return Inertia::render('admin/form-template-form', [
            'template' => $this->templatePayload($formTemplate),
        ]);
    }

    public function update(Request $request, FormTemplate $formTemplate): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $body = $this->sanitizer->sanitize($data['body']);

        if (trim(strip_tags($body)) === '') {
            throw ValidationException::withMessages(['body' => 'The message must contain some text.']);
        }

        $formTemplate->fill([
            'subject' => strip_tags($data['subject']),
            'body' => $body,
        ])->save();

        return to_route('admin.form-templates.index')->with('success', 'Form template updated.');
    }

    /**
     * @return array{id: int, key: string, name: string, description: string, subject: string, body: string, variables: array<int, string>, updated_at: string|null}
     */
    private function templatePayload(FormTemplate $template): array
    {
        return [
            'id' => $template->id,
            'key' => $template->key->value,
            'name' => $template->name,
            'description' => $template->key->description(),
            'subject' => $template->subject,
            'body' => $template->body,
            'variables' => $template->variables,
            'updated_at' => $template->updated_at?->toDateTimeString(),
        ];
    }
}
