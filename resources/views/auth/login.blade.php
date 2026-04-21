<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Arbee's Bakery</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-blue-700 min-h-screen flex items-center justify-center">

<div class="text-center w-full max-w-md">

    <!-- Header -->
    <div class="mb-8">
        <div class="inline-block px-4 py-1 border border-white text-white rounded-full text-sm mb-4">
            Borbolla
        </div>

        <h1 class="text-white text-4xl font-bold">ARBEE'S BAKERY</h1>
        <p class="text-blue-100 mt-2">Fresh baked goods made with love</p>
    </div>

    <!-- Card -->
    <div class="bg-gray-100 rounded-2xl shadow-lg p-6">

        <h2 class="text-lg font-semibold mb-1">Welcome</h2>
        <p class="text-gray-500 text-sm mb-4">Login to continue</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="mb-3 text-left">
                <label>Email</label>
                <input type="email" name="email" required
                    class="w-full mt-1 px-3 py-2 rounded-lg bg-gray-200">
            </div>

            <!-- Password -->
            <div class="mb-4 text-left">
                <label>Password</label>
                <input type="password" name="password" required
                    class="w-full mt-1 px-3 py-2 rounded-lg bg-gray-200">
            </div>

            <button class="w-full bg-blue-700 text-white py-2 rounded-lg">
                Login
            </button>
        </form>

        <!-- Switch -->
        <p class="text-sm text-gray-500 mt-4">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-blue-700 font-medium">
                Register
            </a>
        </p>

    </div>
</div>

</body>
</html>