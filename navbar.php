<nav class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
    <div>
        <button class="md:hidden text-gray-600">
            <i class="fas fa-bars text-xl"></i>
        </button>
        <h3 class="text-xl font-semibold text-gray-800">
            Welcome Back, <span class="text-indigo-600"><?= htmlspecialchars($user['name']) ?></span>
        </h3>
    </div>
    <div class="flex items-center space-x-4">
        <div class="dropdown">
            <button class="flex items-center space-x-2 focus:outline-none" type="button" id="profileDropdown" data-bs-toggle="dropdown">
                <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile" class="w-10 h-10 rounded-full border-2 border-indigo-100 object-cover shadow-sm">
                <span class="text-gray-600">@<?= htmlspecialchars($user['username']) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border border-gray-100 rounded-lg">
                <li><a class="dropdown-item hover:bg-gray-50" href="#"><i class="fas fa-user mr-2 text-indigo-600"></i>Profile</a></li>
                <li><a class="dropdown-item hover:bg-gray-50" href="#"><i class="fas fa-cog mr-2 text-blue-600"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-red-500 hover:bg-red-50" href="#"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>