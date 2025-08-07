<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rate Limit Exceeded - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <!-- Rate limit icon -->
                <div class="mx-auto h-24 w-24 text-red-500 mb-6">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Rate Limit Exceeded
                </h1>
                
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">
                    {{ $message ?? 'Too many requests. Please try again later.' }}
                </p>
                
                @if(isset($retryAfter))
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                Please wait <strong>{{ $retryAfter }} seconds</strong> before trying again.
                            </p>
                        </div>
                    </div>
                </div>
                @endif
                
                <div class="space-y-4">
                    <button 
                        onclick="history.back()" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                    >
                        Go Back
                    </button>
                    
                    <a 
                        href="{{ route('dashboard') }}" 
                        class="w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                    >
                        Return to Dashboard
                    </a>
                </div>
                
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        If you believe this is an error, please 
                        <a href="mailto:support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                            contact support
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if(isset($retryAfter))
    <script>
        // Auto-refresh after retry period
        setTimeout(function() {
            window.location.reload();
        }, {{ $retryAfter * 1000 }});
        
        // Countdown timer
        let timeLeft = {{ $retryAfter }};
        const countdownElement = document.querySelector('strong');
        
        const countdown = setInterval(function() {
            timeLeft--;
            if (countdownElement) {
                countdownElement.textContent = timeLeft + ' seconds';
            }
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                window.location.reload();
            }
        }, 1000);
    </script>
    @endif
</body>
</html>
