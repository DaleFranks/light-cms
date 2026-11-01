<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FormSubmissionController extends Controller
{
    /**
     * Save what a visitor sent with a form, then return them to the page they
     * sent it from: showing the thank-you message, or their mistakes.
     */
    public function store(Request $request, Form $form): RedirectResponse
    {
        $backToForm = self::backToForm($form);

        // Only spam bots fill in the hidden field. They're thanked, but nothing is kept.
        if (filled($request->input(Form::HONEYPOT_FIELD))) {
            return $backToForm->with('form_sent', $form->slug);
        }

        $validator = Validator::make(
            $request->all(),
            $form->validationRules(),
            attributes: $form->validationAttributes(),
        );

        if ($validator->fails()) {
            return $backToForm->withErrors($validator, 'form-'.$form->slug)->withInput();
        }

        $form->recordSubmission($validator->validated());

        return $backToForm->with('form_sent', $form->slug);
    }

    /**
     * A redirect to the page the form was sent from, scrolled to the form.
     */
    public static function backToForm(Form|string $form): RedirectResponse
    {
        $slug = $form instanceof Form ? $form->slug : $form;

        return redirect()->to(Str::before(url()->previous(route('home')), '#').'#form-'.$slug);
    }
}
