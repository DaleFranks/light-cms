<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Set up your site | Light CMS</title>
    <style>
        body { margin: 0; padding: 48px 16px; background: #f8fafc; color: #1e293b; font: 16px/1.6 system-ui, sans-serif; }
        main { max-width: 520px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px; }
        h1 { margin: 0 0 8px; font-size: 1.5rem; color: #0f172a; }
        h2 { margin: 28px 0 12px; font-size: 1rem; color: #0f172a; }
        p.intro { margin: 0 0 8px; color: #475569; }
        label { display: block; margin: 16px 0 6px; font-size: 0.875rem; font-weight: 600; }
        input { box-sizing: border-box; width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font: inherit; }
        input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .help { margin: 6px 0 0; font-size: 0.875rem; color: #64748b; }
        .error { margin: 6px 0 0; font-size: 0.875rem; font-weight: 500; color: #b91c1c; }
        .has-error input { border-color: #b91c1c; }
        button { margin-top: 28px; padding: 12px 24px; border: none; border-radius: 6px; background: #2563eb; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <main>
        <h1>Set up your site</h1>
        <p class="intro">Light CMS is installed. Name your site and create the account you'll use to run it.</p>

        <form method="POST" action="{{ route('setup.store') }}">
            @csrf

            <h2>Your site</h2>
            <div @class(['has-error' => $errors->has('site_name')])>
                <label for="site_name">Site name</label>
                {{-- Told not to offer autofill: password managers otherwise guess it wants an email address --}}
                <input type="text" id="site_name" name="site_name" value="{{ old('site_name') }}" required maxlength="100" autofocus
                    autocomplete="off" data-1p-ignore data-lpignore="true" data-bwignore data-form-type="other">
                <p class="help">Shown in the header and browser tabs. You can change it later on the Theme screen.</p>
                @error('site_name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <h2>Your admin account</h2>
            <div @class(['has-error' => $errors->has('name')])>
                <label for="name">Your name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div @class(['has-error' => $errors->has('email')])>
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                <p class="help">You'll log in with this.</p>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div @class(['has-error' => $errors->has('password')])>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                <p class="help">At least 8 characters.</p>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation">Password again</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit">Finish setting up</button>
        </form>
    </main>
</body>
</html>
