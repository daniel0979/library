@extends('layouts.app', ['title' => 'Login'])

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-xl font-semibold mb-4">Login</h2>

    @if ($errors->any())
        <div class="mb-4 rounded bg-red-100 p-3 text-red-700 text-sm">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded border px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" class="w-full rounded border px-3 py-2" required>
        </div>
        <button type="submit" class="w-full rounded bg-slate-800 px-3 py-2 text-white">Login</button>
    </form>

    <p class="text-sm mt-4">No account? <a class="text-blue-600" href="{{ route('register') }}">Register</a></p>
    <p class="text-xs mt-2 text-slate-500">Admin demo: admin@library.local / password</p>
</div>
@endsection
