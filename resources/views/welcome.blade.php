<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'FinFlow') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        html,
        body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1 0 auto;
            min-height: 0;
        }

        .hero-section {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            position: relative;
        }

        /* Fixed Footer */
        .fixed-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 50;
            background-color: white;
            border-top: 1px solid #e5e7eb;
            padding: 0.75rem 0;
            height: 60px;
            display: flex;
            align-items: center;
        }

        /* Fix: Proper z-index layering */
        .hero-background-overlay {
            position: absolute;
            inset: 0;
            z-index: 5;
            background: rgba(0, 0, 0, 0.5);
        }

        .hero-background-image {
            position: absolute;
            inset: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .hero-background-image img {
            height: 100%;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            object-position: right;
            max-height: 100%;
        }

        .hero-content {
            position: relative;
            z-index: 10;
            /* Content sits above background */
            width: 100%;
            padding-left: 1rem;
        }

        .hero-svg-decoration {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 8;
            /* SVG is below content but above background */
        }

        @media (max-width: 768px) {
            .hero-section {
                min-height: calc(100vh - 80px);
            }

            .fixed-footer {
                height: 56px;
                padding: 0.5rem 0;
            }

            .hero-content {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        @media (min-width: 768px) {
            .hero-content {
                width: 50%;
                padding-left: 2rem;
            }
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>

<body>
    <main>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-br from-blue-900 to-indigo-800 text-white overflow-hidden hero-section">
            <!-- Background Image - Lowest z-index -->
            <div class="hero-background-image">
                <img src="{{ asset('images/hero-background.jpg') }}" alt="Hero Background">
            </div>

            <!-- Dark Overlay - Middle z-index -->
            <div class="hero-background-overlay"></div>

            <!-- Content - Highest z-index -->
            <div class="hero-content">
                <h1 class="text-4xl md:text-6xl font-bold mb-4 md:mb-6 leading-tight">
                    Manage.<br>Track.<br>Grow.
                </h1>

                <p class="text-lg md:text-xl mb-6 md:mb-8 text-gray-100 max-w-xl">
                    Transform your financial workflow with powerful tools and
                    real-time visibility.
                </p>

                @if (route('login', absolute: false))
                    @auth
                        <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                            <a href="{{ url('/dashboard') }}"
                                class="bg-white text-blue-900 font-semibold px-8 py-3 rounded-full hover:bg-blue-100 transition duration-300 text-center inline-block">
                                Dashboard
                            </a>
                        </div>
                    @else
                        <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                            <a href="{{ route('login') }}"
                                class="bg-white text-blue-900 font-semibold px-8 py-3 rounded-full hover:bg-blue-100 transition duration-300 text-center inline-block">
                                Login
                            </a>

                            <a href="{{ route('register') }}"
                                class="border-2 border-white text-white font-semibold px-8 py-3 rounded-full hover:bg-white hover:text-blue-900 transition duration-300 text-center inline-block">
                                Register
                            </a>
                        </div>
                    @endauth
                @endif
            </div>

            <!-- SVG Decoration - Below content, above background -->
            <div class="hero-svg-decoration">
                <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z"
                        fill="white" />
                </svg>
            </div>
        </section>
    </main>

    <footer class="fixed-footer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div
                class="flex flex-col md:flex-row justify-center items-center text-xs text-gray-500 space-y-1 md:space-y-0 md:space-x-4">
                <div class="flex items-center">
                    <span>&copy; {{ date('Y') }} {{ config('app.name', 'FinFlow') }}. All rights reserved.</span>
                </div>
                <span class="hidden md:inline text-gray-300">|</span>
                <div class="flex items-center">
                    <svg class="h-4 w-4 text-green-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Data Privacy Act of 2012 Compliant</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
</body>

</html>
