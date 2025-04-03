<?php
session_start();

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NoteSphere - Modern Collaborative Learning Platform</title>
  
  <!-- SEO Meta Tags -->
  <meta name="description" content="NoteSphere - Your modern platform for sharing and accessing study materials with real-time collaboration." />
  <meta name="keywords" content="notes, study materials, collaboration, education, e-learning" />
  <meta name="author" content="NoteSphere Team" />

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#f0fdf4',
              100: '#dcfce7',
              200: '#bbf7d0',
              300: '#86efac',
              400: '#4ade80',
              500: '#22c55e',
              600: '#16a34a',
              700: '#15803d',
              800: '#166534',
              900: '#14532d',
            },
            secondary: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              200: '#bae6fd',
              300: '#7dd3fc',
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7',
              700: '#0369a1',
              800: '#075985',
              900: '#0c4a6e',
            }
          },
          fontFamily: {
            sans: ['Inter', 'ui-sans-serif', 'system-ui'],
            display: ['Poppins', 'ui-sans-serif', 'system-ui'],
          },
          animation: {
            'float': 'float 6s ease-in-out infinite',
            'bg-gradient': 'bg-gradient 15s ease infinite',
            'pulse-slow': 'pulse 5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
          },
          keyframes: {
            float: {
              '0%, 100%': { transform: 'translateY(0)' },
              '50%': { transform: 'translateY(-10px)' },
            },
            'bg-gradient': {
              '0%, 100%': { 'background-position': '0% 50%' },
              '50%': { 'background-position': '100% 50%' },
            }
          }
        }
      }
    }
  </script>

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet" />

  <!-- AOS Library for Animations -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />

  <style type="text/tailwindcss">
    @layer utilities {
      .text-gradient {
        @apply bg-gradient-to-r from-primary-500 to-secondary-500 bg-clip-text text-transparent;
      }
      .glassmorphism {
        @apply bg-white/20 backdrop-blur-lg border border-white/30;
      }
      .shadow-glow {
        box-shadow: 0 0 20px rgba(34, 197, 94, 0.3);
      }
      .shadow-glow-lg {
        box-shadow: 0 0 30px rgba(34, 197, 94, 0.4);
      }
    }
  </style>
</head>
<body class="font-sans bg-gradient-to-br from-primary-50 to-primary-100 text-gray-900 min-h-screen">
  <!-- Navigation -->
  <nav class="fixed w-full z-50 glassmorphism">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16 items-center">
        <!-- Logo -->
        <div class="flex items-center">
          <a href="#" class="flex items-center space-x-2" data-aos="fade-right">
            <div class="w-10 h-10 rounded-lg bg-primary-600 flex items-center justify-center text-white">
              <i class="fas fa-book-open text-xl"></i>
            </div>
            <span class="text-2xl font-display font-bold text-gradient">NoteSphere</span>
          </a>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center space-x-8">
          <a href="#features" class="text-gray-700 hover:text-primary-600 transition-colors duration-200 font-medium" data-aos="fade-down" data-aos-delay="100">Features</a>
          <a href="#testimonials" class="text-gray-700 hover:text-primary-600 transition-colors duration-200 font-medium" data-aos="fade-down" data-aos-delay="200">Testimonials</a>
          <a href="#pricing" class="text-gray-700 hover:text-primary-600 transition-colors duration-200 font-medium" data-aos="fade-down" data-aos-delay="300">Pricing</a>
          <a href="#about" class="text-gray-700 hover:text-primary-600 transition-colors duration-200 font-medium" data-aos="fade-down" data-aos-delay="400">About</a>
        </div>

        <!-- Auth Buttons -->
        <div class="hidden md:flex items-center space-x-4">
          <button id="loginBtn" class="px-4 py-2 rounded-lg font-medium text-gray-700 hover:text-primary-600 transition-colors duration-200" data-aos="fade-down" data-aos-delay="500">
            Log In
          </button>
          <button id="signupBtn" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors duration-200 shadow-md hover:shadow-lg" data-aos="fade-down" data-aos-delay="600">
            Sign Up
          </button>
        </div>

        <!-- Mobile menu button -->
        <button id="mobile-menu-button" class="md:hidden text-gray-700 hover:text-primary-600 focus:outline-none" data-aos="fade-left">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="md:hidden hidden bg-white/95 border-t border-gray-200">
      <div class="px-4 pt-2 pb-4 space-y-2">
        <a href="#features" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50">Features</a>
        <a href="#testimonials" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50">Testimonials</a>
        <a href="#pricing" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50">Pricing</a>
        <a href="#about" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50">About</a>
        <div class="pt-2 border-t border-gray-200">
          <button id="mobileLoginBtn" class="w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50">Log In</button>
          <button id="mobileSignupBtn" class="w-full mt-2 px-3 py-2 rounded-md text-base font-medium text-white bg-primary-600 hover:bg-primary-700">Sign Up</button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="relative pt-24 pb-16 md:pt-32 md:pb-24 min-h-screen flex items-center overflow-hidden">
    <!-- Background video -->
    <video class="absolute inset-0 w-full h-full object-cover z-0" autoplay muted loop playsinline>
      <source src="assets/hero-bg.mp4" type="video/mp4">
      Your browser does not support the video tag.
    </video>
    
    <!-- Gradient overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-primary-400/30 to-secondary-400/30 backdrop-blur-sm z-10"></div>
    
    <!-- Hero content -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-20" data-aos="fade-up" data-aos-duration="1000">
      <div class="max-w-3xl mx-auto text-center">
        <!-- Premium badge -->
        <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/90 text-primary-700 text-sm font-semibold mb-6 shadow-md" data-aos="fade-up" data-aos-delay="100">
          <i class="fas fa-crown mr-2"></i>
          Trusted by 50,000+ students
        </div>
        
        <h1 class="text-4xl md:text-6xl font-display font-bold leading-tight mb-6 text-gray-900">
          <span class="text-gradient">Collaborate, Learn,</span> and Succeed Together
        </h1>
        
        <p class="text-lg md:text-xl text-gray-700 mb-10 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
          Join the largest student community to share and access study materials effortlessly. 
          Elevate your learning experience with NoteSphere.
        </p>
        
        <div class="flex flex-col sm:flex-row justify-center gap-4" data-aos="fade-up" data-aos-delay="300">
          <button id="heroSignupBtn" class="px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            Get Started for Free
          </button>
          <a href="#features" class="px-8 py-4 border-2 border-primary-600 text-primary-600 hover:bg-primary-600 hover:text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            Explore Features
          </a>
        </div>
      </div>
    </div>
    
    <!-- Floating elements -->
    <div class="absolute bottom-10 left-0 right-0 flex justify-center z-20">
      <div class="animate-bounce">
        <i class="fas fa-chevron-down text-gray-700 text-2xl"></i>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-display font-bold mb-4">
          <span class="text-gradient">Powerful Features</span> for Modern Learning
        </h2>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Designed to enhance your study experience with cutting-edge technology
        </p>
      </div>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Feature 1 -->
        <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100" data-aos="fade-up" data-aos-delay="100">
          <div class="w-16 h-16 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center mb-6 shadow-glow">
            <i class="fas fa-cloud-upload-alt text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold mb-3">Instant Upload</h3>
          <p class="text-gray-600">
            Drag and drop your notes for instant upload with our seamless cloud storage integration.
          </p>
        </div>
        
        <!-- Feature 2 -->
        <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100" data-aos="fade-up" data-aos-delay="200">
          <div class="w-16 h-16 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center mb-6 shadow-glow">
            <i class="fas fa-users text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold mb-3">Real-time Collaboration</h3>
          <p class="text-gray-600">
            Work simultaneously with classmates using our real-time collaborative editing tools.
          </p>
        </div>
        
        <!-- Feature 3 -->
        <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100" data-aos="fade-up" data-aos-delay="300">
          <div class="w-16 h-16 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center mb-6 shadow-glow">
            <i class="fas fa-mobile-alt text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold mb-3">Cross-Platform Sync</h3>
          <p class="text-gray-600">
            Access your notes anywhere, anytime across all your devices with automatic sync.
          </p>
        </div>
        
        <!-- Feature 4 -->
        <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100" data-aos="fade-up" data-aos-delay="100">
          <div class="w-16 h-16 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center mb-6 shadow-glow">
            <i class="fas fa-search text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold mb-3">Smart Search</h3>
          <p class="text-gray-600">
            Find exactly what you need with our AI-powered search that understands context.
          </p>
        </div>
        
        <!-- Feature 5 -->
        <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100" data-aos="fade-up" data-aos-delay="200">
          <div class="w-16 h-16 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center mb-6 shadow-glow">
            <i class="fas fa-shield-alt text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold mb-3">Secure Storage</h3>
          <p class="text-gray-600">
            Your data is encrypted and protected with enterprise-grade security measures.
          </p>
        </div>
        
        <!-- Feature 6 -->
        <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100" data-aos="fade-up" data-aos-delay="300">
          <div class="w-16 h-16 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center mb-6 shadow-glow">
            <i class="fas fa-chart-line text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold mb-3">Progress Tracking</h3>
          <p class="text-gray-600">
            Visualize your learning journey with detailed analytics and progress reports.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section class="py-16 md:py-24 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-display font-bold mb-4">
          How <span class="text-gradient">NoteSphere</span> Works
        </h2>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Get started in just a few simple steps
        </p>
      </div>
      
      <div class="relative">
        <!-- Timeline -->
        <div class="hidden lg:block absolute left-1/2 top-0 bottom-0 w-1 bg-gradient-to-b from-primary-400 to-secondary-400 -ml-0.5"></div>
        
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16">
          <!-- Step 1 -->
          <div class="lg:text-right" data-aos="fade-right">
            <div class="lg:pr-8">
              <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 text-primary-600 shadow-glow mb-4 lg:ml-auto">
                <span class="text-2xl font-bold">1</span>
              </div>
              <h3 class="text-xl font-bold mb-3">Create Your Account</h3>
              <p class="text-gray-600 mb-4">
                Sign up in seconds with your email or social account to join our learning community.
              </p>
            </div>
          </div>
          
          <!-- Step 1 Image (empty div for spacing on desktop) -->
          <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden">
  <img src="asstes/createacc.png" alt="Create Account" class="w-full h-full object-cover">
</div>

          <!-- Step 2 Image -->
          <div data-aos="fade-right">
            <div class="bg-white p-4 rounded-xl shadow-md">
              <div class="aspect-w-16 aspect-h-9 bg-gray-200 rounded-lg overflow-hidden">
                <!-- Placeholder for image -->
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden">
  <img src="asstes/upload.png" alt="Create Account" class="w-full h-full object-cover">
</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Step 2 -->
          <div class="lg:text-left" data-aos="fade-left">
            <div class="lg:pl-8">
              <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 text-primary-600 shadow-glow mb-4 lg:mr-auto">
                <span class="text-2xl font-bold">2</span>
              </div>
              <h3 class="text-xl font-bold mb-3">Upload Your Notes</h3>
              <p class="text-gray-600 mb-4">
                Easily upload your study materials in any format - PDFs, images, or text documents.
              </p>
            </div>
          </div>
          
          <!-- Step 3 -->
          <div class="lg:text-right" data-aos="fade-right">
            <div class="lg:pr-8">
              <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 text-primary-600 shadow-glow mb-4 lg:ml-auto">
                <span class="text-2xl font-bold">3</span>
              </div>
              <h3 class="text-xl font-bold mb-3">Organize & Share</h3>
              <p class="text-gray-600 mb-4">
                Categorize your notes by subject and share with classmates or keep them private.
              </p>
            </div>
          </div>
          
          <!-- Step 3 Image (empty div for spacing on desktop) -->
          <div data-aos="fade-left">
          <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden">
  <img src="asstes/share.png" alt="Create Account" class="w-full h-full object-cover">
</div>
          </div>
          
          <!-- Step 4 Image -->
          <div data-aos="fade-right">
            <div class="bg-white p-4 rounded-xl shadow-md">
              <div class="aspect-w-16 aspect-h-9 bg-gray-200 rounded-lg overflow-hidden">
                <!-- Placeholder for image -->
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden">
  <img src="asstes/colb.png" alt="Create Account" class="w-full h-full object-cover">
</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Step 4 -->
          <div class="lg:text-left" data-aos="fade-left">
            <div class="lg:pl-8">
              <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 text-primary-600 shadow-glow mb-4 lg:mr-auto">
                <span class="text-2xl font-bold">4</span>
              </div>
              <h3 class="text-xl font-bold mb-3">Collaborate & Learn</h3>
              <p class="text-gray-600 mb-4">
                Work together in real-time, leave comments, and enhance your learning experience.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section id="testimonials" class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-display font-bold mb-4">
          What <span class="text-gradient">Students Say</span>
        </h2>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Join thousands of students who have transformed their learning experience
        </p>
      </div>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Testimonial 1 -->
        <div class="bg-gray-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300" data-aos="fade-up" data-aos-delay="100">
          <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-full overflow-hidden mr-4">
              <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Sarah Johnson" class="w-full h-full object-cover">
            </div>
            <div>
              <h4 class="font-bold">Sarah Johnson</h4>
              <p class="text-sm text-gray-600">Computer Science Student</p>
            </div>
          </div>
          <p class="text-gray-700 mb-4">
            "NoteSphere has completely changed how I study. The collaborative features helped me ace my finals by working with classmates remotely."
          </p>
          <div class="flex text-yellow-400">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
        </div>
        
        <!-- Testimonial 2 -->
        <div class="bg-gray-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300" data-aos="fade-up" data-aos-delay="200">
          <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-full overflow-hidden mr-4">
              <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="Michael Chen" class="w-full h-full object-cover">
            </div>
            <div>
              <h4 class="font-bold">Michael Chen</h4>
              <p class="text-sm text-gray-600">Medical Student</p>
            </div>
          </div>
          <p class="text-gray-700 mb-4">
            "The quality of shared notes on NoteSphere is exceptional. I've found comprehensive study guides that saved me countless hours."
          </p>
          <div class="flex text-yellow-400">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
          </div>
        </div>
        
        <!-- Testimonial 3 -->
        <div class="bg-gray-50 p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300" data-aos="fade-up" data-aos-delay="300">
          <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-full overflow-hidden mr-4">
              <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Priya Patel" class="w-full h-full object-cover">
            </div>
            <div>
              <h4 class="font-bold">Priya Patel</h4>
              <p class="text-sm text-gray-600">Engineering Student</p>
            </div>
          </div>
          <p class="text-gray-700 mb-4">
            "As an international student, NoteSphere helped me connect with peers and access study materials I couldn't find elsewhere."
          </p>
          <div class="flex text-yellow-400">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
        </div>
      </div>
      
      <!-- Stats -->
      <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 text-center" data-aos="fade-up">
        <div class="bg-primary-600/10 p-6 rounded-xl">
          <div class="text-3xl font-bold text-primary-600 mb-2">50K+</div>
          <div class="text-gray-700">Active Students</div>
        </div>
        <div class="bg-primary-600/10 p-6 rounded-xl">
          <div class="text-3xl font-bold text-primary-600 mb-2">1M+</div>
          <div class="text-gray-700">Notes Shared</div>
        </div>
        <div class="bg-primary-600/10 p-6 rounded-xl">
          <div class="text-3xl font-bold text-primary-600 mb-2">4.9/5</div>
          <div class="text-gray-700">Average Rating</div>
        </div>
        <div class="bg-primary-600/10 p-6 rounded-xl">
          <div class="text-3xl font-bold text-primary-600 mb-2">100+</div>
          <div class="text-gray-700">Universities</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Pricing Section -->
  <section id="pricing" class="py-16 md:py-24 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-display font-bold mb-4">
          Simple, <span class="text-gradient">Transparent</span> Pricing
        </h2>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Choose the plan that works best for your learning needs
        </p>
      </div>
      
      <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
        <!-- Free Plan -->
        <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-200" data-aos="fade-up" data-aos-delay="100">
          <h3 class="text-xl font-bold mb-2">Free</h3>
          <p class="text-gray-600 mb-6">Perfect for trying out NoteSphere</p>
          <div class="text-4xl font-bold mb-6">₹0<span class="text-lg font-normal text-gray-600">/month</span></div>
          <ul class="space-y-3 mb-8">
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Basic note sharing</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>500MB storage</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Public study groups</span>
            </li>
            <li class="flex items-center text-gray-400">
              <i class="fas fa-times mr-2"></i>
              <span>No premium content</span>
            </li>
            <li class="flex items-center text-gray-400">
              <i class="fas fa-times mr-2"></i>
              <span>Ads supported</span>
            </li>
          </ul>
          <button class="w-full py-3 border-2 border-primary-600 text-primary-600 font-medium rounded-lg hover:bg-primary-600 hover:text-white transition-colors duration-200">
            Get Started
          </button>
        </div>
        
        <!-- Pro Plan (Featured) -->
        <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 border-2 border-primary-600 transform hover:-translate-y-2" data-aos="fade-up" data-aos-delay="200">
          <div class="absolute top-0 right-0 bg-primary-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg rounded-tr-lg">
            MOST POPULAR
          </div>
          <h3 class="text-xl font-bold mb-2">Pro</h3>
          <p class="text-gray-600 mb-6">For serious students</p>
          <div class="text-4xl font-bold mb-6">₹299<span class="text-lg font-normal text-gray-600">/month</span></div>
          <ul class="space-y-3 mb-8">
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Everything in Free</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>10GB storage</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Private study groups</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Premium content access</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Ad-free experience</span>
            </li>
          </ul>
          <button class="w-full py-3 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition-colors duration-200 shadow-md hover:shadow-lg">
            Upgrade Now
          </button>
        </div>
        
        <!-- Team Plan -->
        <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-200" data-aos="fade-up" data-aos-delay="300">
          <h3 class="text-xl font-bold mb-2">Team</h3>
          <p class="text-gray-600 mb-6">For study groups & classes</p>
          <div class="text-4xl font-bold mb-6">₹599<span class="text-lg font-normal text-gray-600">/month</span></div>
          <ul class="space-y-3 mb-8">
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Everything in Pro</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>50GB storage</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Up to 10 members</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Advanced collaboration</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-check text-primary-600 mr-2"></i>
              <span>Priority support</span>
            </li>
          </ul>
          <button class="w-full py-3 border-2 border-primary-600 text-primary-600 font-medium rounded-lg hover:bg-primary-600 hover:text-white transition-colors duration-200">
            Contact Sales
          </button>
        </div>
      </div>
      
      <div class="mt-12 text-center text-gray-600" data-aos="fade-up">
        <p>Need a custom plan? <a href="#" class="text-primary-600 hover:underline">Contact our team</a> for enterprise solutions.</p>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-16 md:py-24 bg-gradient-to-r from-primary-500 to-secondary-500 text-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-3xl md:text-4xl font-display font-bold mb-6" data-aos="fade-up">
        Ready to Transform Your Learning Experience?
      </h2>
      <p class="text-xl max-w-2xl mx-auto mb-8 opacity-90" data-aos="fade-up" data-aos-delay="100">
        Join thousands of students who are already collaborating and succeeding with NoteSphere.
      </p>
      <div class="flex flex-col sm:flex-row justify-center gap-4" data-aos="fade-up" data-aos-delay="200">
        <button id="ctaSignupBtn" class="px-8 py-4 bg-white text-primary-600 font-semibold rounded-lg shadow-lg hover:shadow-xl hover:bg-gray-100 transition-all duration-300">
          Sign Up Free
        </button>
        <a href="#features" class="px-8 py-4 border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 transition-all duration-300">
          Learn More
        </a>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-display font-bold mb-4">
          Frequently <span class="text-gradient">Asked Questions</span>
        </h2>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Get answers to common questions about NoteSphere
        </p>
      </div>
      
      <div class="max-w-3xl mx-auto">
        <!-- FAQ Item 1 -->
        <div class="mb-6 border-b border-gray-200 pb-6" data-aos="fade-up" data-aos-delay="100">
          <button class="faq-toggle flex justify-between items-center w-full text-left">
            <h3 class="text-xl font-bold">Is NoteSphere free to use?</h3>
            <i class="fas fa-chevron-down transition-transform duration-200"></i>
          </button>
          <div class="faq-content mt-4 text-gray-600 hidden">
            <p>
              Yes! NoteSphere offers a free plan with basic features that's perfect for students who want to try the platform. 
              We also offer premium plans with additional features and storage for those who need more advanced capabilities.
            </p>
          </div>
        </div>
        
        <!-- FAQ Item 2 -->
        <div class="mb-6 border-b border-gray-200 pb-6" data-aos="fade-up" data-aos-delay="200">
          <button class="faq-toggle flex justify-between items-center w-full text-left">
            <h3 class="text-xl font-bold">How do I share notes with classmates?</h3>
            <i class="fas fa-chevron-down transition-transform duration-200"></i>
          </button>
          <div class="faq-content mt-4 text-gray-600 hidden">
            <p>
              Sharing notes is easy! You can either create a study group and add classmates, or generate a shareable link for specific notes. 
              You control whether the notes are view-only or editable by others.
            </p>
          </div>
        </div>
        
        <!-- FAQ Item 3 -->
        <div class="mb-6 border-b border-gray-200 pb-6" data-aos="fade-up" data-aos-delay="300">
          <button class="faq-toggle flex justify-between items-center w-full text-left">
            <h3 class="text-xl font-bold">What file types are supported?</h3>
            <i class="fas fa-chevron-down transition-transform duration-200"></i>
          </button>
          <div class="faq-content mt-4 text-gray-600 hidden">
            <p>
              NoteSphere supports PDFs, Word documents, PowerPoint presentations, images (JPG, PNG), and our native note format. 
              We're constantly adding support for more file types based on student feedback.
            </p>
          </div>
        </div>
        
        <!-- FAQ Item 4 -->
        <div class="mb-6 border-b border-gray-200 pb-6" data-aos="fade-up" data-aos-delay="400">
          <button class="faq-toggle flex justify-between items-center w-full text-left">
            <h3 class="text-xl font-bold">Is my data secure?</h3>
            <i class="fas fa-chevron-down transition-transform duration-200"></i>
          </button>
          <div class="faq-content mt-4 text-gray-600 hidden">
            <p>
              Absolutely. We use industry-standard encryption for all data transfers and storage. 
              You retain full ownership of your notes, and we never share your data with third parties without your consent.
            </p>
          </div>
        </div>
        
        <!-- FAQ Item 5 -->
        <div class="mb-6" data-aos="fade-up" data-aos-delay="500">
          <button class="faq-toggle flex justify-between items-center w-full text-left">
            <h3 class="text-xl font-bold">Can I use NoteSphere on my phone?</h3>
            <i class="fas fa-chevron-down transition-transform duration-200"></i>
          </button>
          <div class="faq-content mt-4 text-gray-600 hidden">
            <p>
              Yes! NoteSphere has fully responsive web apps that work on any device, and we offer native mobile apps for iOS and Android 
              with all the features of the desktop version.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Signup Modal -->
  <div id="signupModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-all duration-300">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold">Create Your Account</h2>
          <button id="closeSignup" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        
        <form action="signup.php" method="POST" autocomplete="off" id="signupForm">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
          
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-1">Full Name</label>
            <input type="text" name="fullname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" 
                placeholder="Enter your name" required minlength="3" maxlength="50">
          </div>
          
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-1">Username</label>
            <input type="text" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" 
                placeholder="Choose a username" required pattern="[a-zA-Z0-9_]+" minlength="4" maxlength="20"
                title="4-20 characters (letters, numbers, underscores)">
            <p class="text-xs text-gray-500 mt-1">4-20 characters (letters, numbers, underscores)</p>
          </div>
          
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" 
                placeholder="Enter your email" required>
          </div>
          
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" id="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" 
                placeholder="Create a password" required minlength="8">
            <div class="password-strength mt-2">
              <div class="flex items-center mb-1">
                <div class="h-2 bg-gray-200 rounded-full flex-1 mr-2">
                  <div class="h-full rounded-full strength-bar bg-red-500 w-0"></div>
                </div>
                <span class="strength-text text-xs text-gray-600">Weak</span>
              </div>
              <ul class="text-xs text-gray-600 space-y-1">
                <li class="length flex items-center">
                  <i class="fas fa-circle text-[10px] mr-1"></i>
                  <span>At least 8 characters</span>
                </li>
                <li class="uppercase flex items-center">
                  <i class="fas fa-circle text-[10px] mr-1"></i>
                  <span>One uppercase letter</span>
                </li>
                <li class="number flex items-center">
                  <i class="fas fa-circle text-[10px] mr-1"></i>
                  <span>One number</span>
                </li>
              </ul>
            </div>
          </div>
          
          <div class="mb-6">
            <label class="block text-gray-700 text-sm font-medium mb-1">Confirm Password</label>
            <input type="password" name="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" 
                placeholder="Confirm your password" required minlength="8">
          </div>
          
          <button type="submit" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors duration-200">
            Sign Up
          </button>
          
          <div class="mt-4 text-center text-sm text-gray-600">
            Already have an account? 
            <button type="button" id="switchToLogin" class="text-primary-600 hover:underline">
              Log in here
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Login Modal -->
  <div id="loginModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-all duration-300">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold">Welcome Back</h2>
          <button id="closeLogin" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        
        <form action="login.php" method="POST" id="loginForm">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
          
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-1">Email or Username</label>
            <input type="text" name="login" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" 
                placeholder="Enter your email or username" required>
          </div>
          
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" 
                placeholder="Enter your password" required>
            <div class="text-right mt-1">
              <a href="#" class="text-sm text-primary-600 hover:underline">Forgot password?</a>
            </div>
          </div>
          
          <div class="mb-4 flex items-center">
            <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
            <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
          </div>
          
          <button type="submit" class="w-full py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors duration-200 mb-4">
            Log In
          </button>
          
          <div class="text-center text-sm text-gray-600">
            Don't have an account? 
            <button type="button" id="switchToSignup" class="text-primary-600 hover:underline">
              Sign up here
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white pt-16 pb-8">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid md:grid-cols-4 gap-8 mb-12">
        <!-- Logo and Info -->
        <div>
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-lg bg-primary-600 flex items-center justify-center text-white mr-2">
              <i class="fas fa-book-open text-xl"></i>
            </div>
            <span class="text-2xl font-display font-bold text-white">NoteSphere</span>
          </div>
          <p class="text-gray-400 mb-4">
            Empowering students through collaborative learning and knowledge sharing.
          </p>
          <div class="flex space-x-4">
            <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
              <i class="fab fa-twitter text-xl"></i>
            </a>
            <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
              <i class="fab fa-facebook text-xl"></i>
            </a>
            <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
              <i class="fab fa-instagram text-xl"></i>
            </a>
            <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
              <i class="fab fa-linkedin text-xl"></i>
            </a>
          </div>
        </div>
        
        <!-- Quick Links -->
        <div>
          <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
          <ul class="space-y-2">
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Home</a></li>
            <li><a href="#features" class="text-gray-400 hover:text-white transition-colors duration-200">Features</a></li>
            <li><a href="#pricing" class="text-gray-400 hover:text-white transition-colors duration-200">Pricing</a></li>
            <li><a href="#testimonials" class="text-gray-400 hover:text-white transition-colors duration-200">Testimonials</a></li>
            <li><a href="#about" class="text-gray-400 hover:text-white transition-colors duration-200">About Us</a></li>
          </ul>
        </div>
        
        <!-- Resources -->
        <div>
          <h3 class="text-lg font-semibold mb-4">Resources</h3>
          <ul class="space-y-2">
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Blog</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Help Center</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Tutorials</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Community</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">Webinars</a></li>
          </ul>
        </div>
        
        <!-- Newsletter -->
        <div>
  <h3 class="text-lg font-semibold mb-4">About the Creator</h3>
  <p class="text-gray-400 mb-4">
    NoteSphere is built by <span class="font-semibold text-white">Saieshwar</span>, a passionate IT student and web developer from Goa, India. 
    With expertise in cybersecurity, full-stack development, and UI/UX design, he aims to create innovative platforms that enhance learning and collaboration. 
    Saieshwar is also the founder of multiple tech initiatives, including <span class="font-semibold text-white">The Bitz</span> and <span class="font-semibold text-white">Goan Flavor</span>.  
  </p>
</div>

    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    // Initialize AOS
    AOS.init({
      duration: 800,
      once: true,
    });
    
    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {
      // Modal elements
      const signupModal = document.getElementById('signupModal');
      const loginModal = document.getElementById('loginModal');
      
      // Buttons to open modals
      const signupBtns = [document.getElementById('signupBtn'), document.getElementById('mobileSignupBtn'), document.getElementById('heroSignupBtn'), document.getElementById('ctaSignupBtn')];
      const loginBtns = [document.getElementById('loginBtn'), document.getElementById('mobileLoginBtn')];
      
      // Buttons to close modals
      const closeSignup = document.getElementById('closeSignup');
      const closeLogin = document.getElementById('closeLogin');
      
      // Switch between modals
      const switchToLogin = document.getElementById('switchToLogin');
      const switchToSignup = document.getElementById('switchToSignup');
      
      // Mobile menu toggle
      const mobileMenuButton = document.getElementById('mobile-menu-button');
      const mobileMenu = document.getElementById('mobile-menu');
      
      // Open signup modal
      signupBtns.forEach(btn => {
        if (btn) {
          btn.addEventListener('click', () => {
            signupModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
          });
        }
      });
      
      // Open login modal
      loginBtns.forEach(btn => {
        if (btn) {
          btn.addEventListener('click', () => {
            loginModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
          });
        }
      });
      
      // Close modals
      function closeModals() {
        signupModal.classList.add('hidden');
        loginModal.classList.add('hidden');
        document.body.style.overflow = '';
      }
      
      closeSignup.addEventListener('click', closeModals);
      closeLogin.addEventListener('click', closeModals);
      
      // Switch between modals
      if (switchToLogin) {
        switchToLogin.addEventListener('click', () => {
          signupModal.classList.add('hidden');
          loginModal.classList.remove('hidden');
        });
      }
      
      if (switchToSignup) {
        switchToSignup.addEventListener('click', () => {
          loginModal.classList.add('hidden');
          signupModal.classList.remove('hidden');
        });
      }
      
      // Close when clicking outside
      window.addEventListener('click', (e) => {
        if (e.target === signupModal || e.target === loginModal) {
          closeModals();
        }
      });
      
      // Mobile menu toggle
      if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
          mobileMenu.classList.toggle('hidden');
        });
      }
      
      // FAQ toggle functionality
      const faqToggles = document.querySelectorAll('.faq-toggle');
      faqToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
          const content = toggle.nextElementSibling;
          const icon = toggle.querySelector('i');
          
          content.classList.toggle('hidden');
          icon.classList.toggle('rotate-180');
        });
      });
      
      // Password strength indicator
      const passwordInput = document.getElementById('password');
      if (passwordInput) {
        passwordInput.addEventListener('input', function() {
          const password = this.value;
          const strengthBar = document.querySelector('.strength-bar');
          const strengthText = document.querySelector('.strength-text');
          const requirements = {
            length: document.querySelector('.length'),
            uppercase: document.querySelector('.uppercase'),
            number: document.querySelector('.number')
          };
          
          let strength = 0;
          
          // Validate length
          if (password.length >= 8) {
            strength += 1;
            requirements.length.innerHTML = '<i class="fas fa-check text-[10px] mr-1 text-green-500"></i><span class="text-green-500">8+ characters</span>';
          } else {
            requirements.length.innerHTML = '<i class="fas fa-circle text-[10px] mr-1"></i><span>At least 8 characters</span>';
          }
          
          // Validate uppercase
          if (/[A-Z]/.test(password)) {
            strength += 1;
            requirements.uppercase.innerHTML = '<i class="fas fa-check text-[10px] mr-1 text-green-500"></i><span class="text-green-500">Uppercase letter</span>';
          } else {
            requirements.uppercase.innerHTML = '<i class="fas fa-circle text-[10px] mr-1"></i><span>One uppercase letter</span>';
          }
          
          // Validate number
          if (/[0-9]/.test(password)) {
            strength += 1;
            requirements.number.innerHTML = '<i class="fas fa-check text-[10px] mr-1 text-green-500"></i><span class="text-green-500">Number</span>';
          } else {
            requirements.number.innerHTML = '<i class="fas fa-circle text-[10px] mr-1"></i><span>One number</span>';
          }
          
          // Update strength indicator
          switch(strength) {
            case 0:
              strengthBar.style.width = '0%';
              strengthBar.className = 'h-full rounded-full strength-bar bg-red-500 w-0';
              strengthText.textContent = 'Weak';
              break;
            case 1:
              strengthBar.style.width = '33%';
              strengthBar.className = 'h-full rounded-full strength-bar bg-orange-500 w-1/3';
              strengthText.textContent = 'Fair';
              break;
            case 2:
              strengthBar.style.width = '66%';
              strengthBar.className = 'h-full rounded-full strength-bar bg-yellow-500 w-2/3';
              strengthText.textContent = 'Good';
              break;
            case 3:
              strengthBar.style.width = '100%';
              strengthBar.className = 'h-full rounded-full strength-bar bg-green-500 w-full';
              strengthText.textContent = 'Strong';
              break;
          }
        });
      }
    });
  </script>
</body>
</html>