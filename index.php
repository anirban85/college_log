<?php
// index.php — EduTrack Pro: Modern Faculty College Log & Attendance Tracker (XAMPP / PHP / MySQL)
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

// Determine active logged-in user
$userId = $_SESSION['user_id'] ?? null;
$currentUser = null;
if ($userId) {
    $stmt = $pdo->prepare("SELECT `id`, `username`, `full_name`, `email`, `department` FROM `users` WHERE `id` = :id");
    $stmt->execute([':id' => $userId]);
    $currentUser = $stmt->fetch();
}

$isLoggedIn = ($currentUser !== null);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTrack Pro — Faculty Log & Attendance Tracker</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        }
                    }
                }
            }
        };
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- SheetJS (Excel .xlsx Export) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <!-- jsPDF & jsPDF-AutoTable (Official PDF Export) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .student-card-transition {
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>

<?php if (!$isLoggedIn): ?>
<!-- ======================================================== -->
<!-- AUTHENTICATION GATE: SIGN IN & REGISTER NEW FACULTY       -->
<!-- ======================================================== -->
<body class="min-h-full bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-slate-800 flex flex-col justify-center items-center py-10 px-4 sm:px-6">
    <div class="max-w-md w-full">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="inline-flex w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-700 items-center justify-center text-white shadow-xl shadow-indigo-500/30 mb-3 border border-white/20">
                <i data-lucide="graduation-cap" class="w-9 h-9"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">EduTrack Pro</h1>
            <span class="inline-block mt-1 text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full bg-brand-500/20 text-indigo-300 border border-indigo-400/30">Faculty Portal</span>
            <p class="text-xs text-slate-300 mt-2">Daily Campus Log, Class Attendance & Exam Invigilation Tracker</p>
        </div>

        <!-- Auth Card -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden">
            <!-- Tabs -->
            <div class="grid grid-cols-2 bg-slate-100 p-1.5 border-b border-slate-200 text-xs font-bold gap-1">
                <button type="button" id="gate-tab-btn-signin" onclick="switchGateTab('signin')" class="py-2.5 rounded-xl bg-white text-brand-600 shadow-sm flex items-center justify-center gap-1.5 transition">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Sign In</span>
                </button>
                <button type="button" id="gate-tab-btn-register" onclick="switchGateTab('register')" class="py-2.5 rounded-xl text-slate-500 hover:text-slate-800 flex items-center justify-center gap-1.5 transition">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Register New Faculty</span>
                </button>
            </div>

            <div class="p-6 sm:p-7">
                <!-- Message Box -->
                <div id="gate-alert-box" class="hidden mb-4 p-3 rounded-xl text-xs font-semibold flex items-center gap-2"></div>

                <!-- SIGN IN FORM -->
                <form id="gate-form-signin" onsubmit="handleGateSignIn(event)" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Username or Institutional Email</label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="text" id="login-user" required placeholder="e.g. alorika or username" class="w-full rounded-xl border border-slate-300 pl-10 pr-3.5 py-2.5 text-sm text-slate-900 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="password" id="login-pass" required placeholder="Enter password" class="w-full rounded-xl border border-slate-300 pl-10 pr-10 py-2.5 text-sm text-slate-900 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                            <button type="button" onclick="togglePassVisibility('login-pass', this)" class="absolute right-3.5 top-2.5 text-slate-400 hover:text-slate-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="btn-submit-signin" class="w-full py-2.5 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-500/20 flex items-center justify-center gap-2 transition">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        <span>Sign In to Portal</span>
                    </button>

                    <div class="text-center pt-2">
                        <p class="text-xs text-slate-500">
                            New faculty member? 
                            <button type="button" onclick="switchGateTab('register')" class="text-brand-600 font-bold hover:underline">Create an account</button>
                        </p>
                    </div>
                </form>

                <!-- REGISTER FORM -->
                <form id="gate-form-register" onsubmit="handleGateRegister(event)" class="hidden space-y-3.5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Choose Username <span class="text-rose-500">*</span></label>
                            <input type="text" id="reg-user" required placeholder="e.g. prof_das" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                            <p class="text-[10px] text-slate-400 mt-0.5">3-30 chars (letters, digits, _)</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Choose Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="password" id="reg-pass" required placeholder="Min. 4 characters" class="w-full rounded-xl border border-slate-300 pl-3 pr-8 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                                <button type="button" onclick="togglePassVisibility('reg-pass', this)" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Faculty Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="reg-name" required placeholder="e.g. Dr. Subhasish Das" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Department & College / Institution</label>
                        <input type="text" id="reg-dept" value="Dept. of Physics, The Heritage College" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Institutional Email (Optional)</label>
                        <input type="email" id="reg-email" placeholder="e.g. subhasish.das@theheritage.ac.in" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                    </div>

                    <button type="submit" id="btn-submit-register" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md shadow-emerald-500/20 flex items-center justify-center gap-2 transition mt-1">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Register & Access Portal</span>
                    </button>

                    <div class="text-center pt-2">
                        <p class="text-xs text-slate-500">
                            Already registered? 
                            <button type="button" onclick="switchGateTab('signin')" class="text-brand-600 font-bold hover:underline">Sign In here</button>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>MySQL Database: <?= htmlspecialchars(defined('DB_NAME') ? DB_NAME : 'college_log_db') ?></span>
                </span>
                <span>Protected Faculty Session</span>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>

    <script>
        lucide.createIcons();

        function switchGateTab(tab) {
            const btnSignIn = document.getElementById('gate-tab-btn-signin');
            const btnRegister = document.getElementById('gate-tab-btn-register');
            const formSignIn = document.getElementById('gate-form-signin');
            const formRegister = document.getElementById('gate-form-register');
            const alertBox = document.getElementById('gate-alert-box');
            alertBox.classList.add('hidden');

            if (tab === 'signin') {
                btnSignIn.className = "py-2.5 rounded-xl bg-white text-brand-600 shadow-sm flex items-center justify-center gap-1.5 transition";
                btnRegister.className = "py-2.5 rounded-xl text-slate-500 hover:text-slate-800 flex items-center justify-center gap-1.5 transition";
                formSignIn.classList.remove('hidden');
                formRegister.classList.add('hidden');
            } else {
                btnSignIn.className = "py-2.5 rounded-xl text-slate-500 hover:text-slate-800 flex items-center justify-center gap-1.5 transition";
                btnRegister.className = "py-2.5 rounded-xl bg-white text-brand-600 shadow-sm flex items-center justify-center gap-1.5 transition";
                formSignIn.classList.add('hidden');
                formRegister.classList.remove('hidden');
            }
            lucide.createIcons();
        }

        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<i data-lucide="eye-off" class="w-4 h-4"></i>';
            } else {
                input.type = 'password';
                btn.innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i>';
            }
            lucide.createIcons();
        }

        function showGateAlert(msg, type = 'error') {
            const box = document.getElementById('gate-alert-box');
            box.className = `p-3 rounded-xl text-xs font-semibold flex items-center gap-2 ${type === 'error' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'}`;
            box.innerHTML = `<i data-lucide="${type === 'error' ? 'alert-circle' : 'check-circle-2'}" class="w-4 h-4 shrink-0"></i><span>${msg}</span>`;
            box.classList.remove('hidden');
            lucide.createIcons();
        }

        async function handleGateSignIn(event) {
            event.preventDefault();
            const u = document.getElementById('login-user').value.trim();
            const p = document.getElementById('login-pass').value;
            const btn = document.getElementById('btn-submit-signin');

            if (!u || !p) {
                showGateAlert("Please enter both username and password.");
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Signing in...';
            lucide.createIcons();

            try {
                const resp = await fetch('auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: u, password: p })
                });
                const res = await resp.json();
                if (res.success) {
                    showGateAlert(`Signed in! Loading your portal...`, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 600);
                } else {
                    showGateAlert(res.error || "Invalid username or password.");
                }
            } catch (e) {
                showGateAlert("Network error connecting to auth server.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="log-in" class="w-4 h-4"></i> Sign In to Portal';
                lucide.createIcons();
            }
        }

        async function handleGateRegister(event) {
            event.preventDefault();
            const username = document.getElementById('reg-user').value.trim();
            const password = document.getElementById('reg-pass').value;
            const fullName = document.getElementById('reg-name').value.trim();
            const department = document.getElementById('reg-dept').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const btn = document.getElementById('btn-submit-register');

            if (!username || !password || !fullName) {
                showGateAlert("Username, password, and full name are required.");
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Creating account...';
            lucide.createIcons();

            try {
                const resp = await fetch('auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username,
                        password,
                        full_name: fullName,
                        department,
                        email
                    })
                });
                const res = await resp.json();
                if (res.success) {
                    showGateAlert(`Account created! Welcome ${fullName}! Loading portal...`, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 700);
                } else {
                    showGateAlert(res.error || "Registration failed.");
                }
            } catch (e) {
                showGateAlert("Network error connecting to registration server.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Register & Access Portal';
                lucide.createIcons();
            }
        }
    </script>
</body>

<?php else: ?>
<!-- ======================================================== -->
<!-- AUTHENTICATED FACULTY DASHBOARD                           -->
<!-- ======================================================== -->
<body class="min-h-full text-slate-800 flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between py-3.5 gap-3">
                <!-- Brand -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-600 to-indigo-800 flex items-center justify-center text-white shadow-md shadow-brand-500/20">
                            <i data-lucide="graduation-cap" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-lg font-bold tracking-tight text-slate-900">EduTrack Pro</h1>
                                <span class="text-[11px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 border border-brand-200">Faculty Portal</span>
                            </div>
                            <p class="text-xs text-slate-500">Daily Campus Log, Class Attendance & Exam Invigilation Tracker</p>
                        </div>
                    </div>

                    <!-- Mobile Sign Out -->
                    <div class="flex items-center gap-2 md:hidden">
                        <button onclick="handleSignOut()" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            <span>Sign Out</span>
                        </button>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <nav class="flex items-center gap-1.5 bg-slate-100/90 p-1.5 rounded-xl border border-slate-200/70 overflow-x-auto">
                    <button onclick="switchTab('entry')" id="tab-btn-entry" class="tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap bg-white text-brand-600 shadow-sm">
                        <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                        <span>Daily Log & Class Entry</span>
                    </button>
                    <button onclick="switchTab('reports')" id="tab-btn-reports" class="tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap text-slate-600 hover:text-slate-900">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                        <span>Reports & Export</span>
                        <span id="badge-total-logs" class="ml-1 px-1.5 py-0.5 text-[11px] rounded-full bg-brand-100 text-brand-700 font-bold">0</span>
                    </button>
                    <button onclick="switchTab('roster')" id="tab-btn-roster" class="tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap text-slate-600 hover:text-slate-900">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Semester Roster Manager</span>
                    </button>
                </nav>

                <!-- Faculty Profile / Account Actions -->
                <div class="hidden md:flex items-center gap-3">
                    <div onclick="openProfileModal()" class="cursor-pointer group flex items-center gap-2.5 px-3 py-1.5 rounded-xl hover:bg-slate-100 border border-transparent hover:border-slate-200 transition" title="Click to edit faculty profile">
                        <div id="header-user-avatar" class="w-8 h-8 rounded-full bg-brand-600 text-white font-bold text-xs flex items-center justify-center shadow-xs overflow-hidden">
                            <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 2)) ?>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <p id="faculty-name-display" class="text-xs font-bold text-slate-800 group-hover:text-brand-600 transition"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" title="Connected to MySQL"></span>
                            </div>
                            <p id="faculty-dept-display" class="text-[11px] text-slate-500"><?= htmlspecialchars($currentUser['department']) ?></p>
                        </div>
                    </div>

                    <!-- Sign Out Button -->
                    <button onclick="handleSignOut()" class="flex items-center gap-1.5 text-xs font-semibold text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-3 py-2 rounded-xl border border-rose-200 transition" title="Sign out of your faculty account">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        <span>Sign Out</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Sub-Banner -->
    <div class="bg-indigo-950 text-indigo-100 border-b border-indigo-900/80 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="font-medium">Connected to MySQL Database: <strong class="text-white font-mono"><?= htmlspecialchars(defined('DB_NAME') ? DB_NAME : 'college_log_db') ?></strong> (Active Workspace: <span class="text-emerald-300 font-semibold"><?= htmlspecialchars($currentUser['full_name']) ?></span>)</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-indigo-900/90 text-indigo-200 border border-indigo-800">User: @<?= htmlspecialchars($currentUser['username']) ?></span>
                <button onclick="handleSignOut()" class="underline hover:text-white font-semibold flex items-center gap-1">
                    <span>Sign Out / Switch Account</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- ========================================== -->
        <!-- TAB 1: DAILY LOG & CLASS / EXAM ENTRY      -->
        <!-- ========================================== -->
        <section id="tab-entry" class="space-y-6">
            
            <!-- 1. Daily Campus Arrival & Departure Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-4 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-xl bg-white/10 backdrop-blur-sm">
                            <i data-lucide="clock" class="w-5 h-5 text-indigo-300"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold">1. Daily Campus Arrival & Departure Log</h2>
                            <p id="banner-faculty-subtitle" class="text-xs text-slate-300"><?= htmlspecialchars($currentUser['full_name'] ?? 'Faculty Member') ?> • <?= htmlspecialchars($currentUser['department'] ?? 'Department') ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="campus-duration-badge" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30">
                            <i data-lucide="timer" class="w-3.5 h-3.5"></i>
                            <span id="campus-hours-text">7h 30m on campus</span>
                        </span>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <!-- Date Selector -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Log Date</label>
                            <div class="relative">
                                <input type="date" id="log-date" onchange="handleDateChange()" class="w-full rounded-xl border border-slate-300 bg-slate-50/60 px-3.5 py-2.5 text-sm font-semibold text-slate-800 focus:bg-white focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                            </div>
                        </div>

                        <!-- Arrival Time -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500">Arrival Time (In)</label>
                                <button type="button" onclick="punchNow('arrival')" class="text-[11px] font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                                    <i data-lucide="zap" class="w-3 h-3"></i> Punch In Now
                                </button>
                            </div>
                            <input type="time" id="arrival-time" onchange="updateCampusDuration()" value="09:15" class="w-full rounded-xl border border-slate-300 bg-slate-50/60 px-3.5 py-2.5 text-sm font-semibold text-slate-800 focus:bg-white focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                        </div>

                        <!-- Departure Time -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold uppercase tracking-wider text-slate-500">Departure Time (Out)</label>
                                <button type="button" onclick="punchNow('departure')" class="text-[11px] font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                                    <i data-lucide="zap" class="w-3 h-3"></i> Punch Out Now
                                </button>
                            </div>
                            <input type="time" id="departure-time" onchange="updateCampusDuration()" value="16:45" class="w-full rounded-xl border border-slate-300 bg-slate-50/60 px-3.5 py-2.5 text-sm font-semibold text-slate-800 focus:bg-white focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                        </div>

                        <!-- Save Daily Attendance Timing Button -->
                        <div>
                            <button type="button" onclick="saveDailyTimingOnly()" class="w-full flex items-center justify-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold px-4 py-2.5 text-sm shadow-sm transition">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
                                <span>Update Day Timing</span>
                            </button>
                        </div>
                    </div>

                    <!-- Quick summary bar for the chosen date -->
                    <div id="selected-date-summary" class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span id="date-status-label">Showing timing for selected date. Any class or exam duty logged below will automatically link to this date's arrival & departure log.</span>
                        </div>
                        <button onclick="switchTab('reports')" class="text-brand-600 hover:underline font-medium flex items-center gap-1">
                            <span>View all logs for this date</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Activity Entry Form + Student Attendance Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- Left Column: Class / Exam Duty Details (5 cols) -->
                <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 flex flex-col justify-between">
                    <div class="space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                                    <i data-lucide="book-open-check" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">2. Class & Exam Duty Details</h3>
                                    <p class="text-xs text-slate-500">Select duty type and fill session particulars</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sticky/Notice Banner When Editing An Existing Log -->
                        <div id="editing-log-indicator" class="hidden p-3.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 flex items-center justify-between gap-3 animate-in fade-in">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-200 text-amber-900 flex items-center justify-center shrink-0">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-amber-950">Editing Log Record: <span id="editing-log-code" class="font-mono text-brand-700 font-extrabold"></span></p>
                                    <p class="text-[11px] text-amber-800">Modify details or attendance, then click "Update Log Entry" below.</p>
                                </div>
                            </div>
                            <button type="button" onclick="cancelEditingActivityLog()" class="px-3 py-1.5 rounded-lg bg-white border border-amber-300 hover:bg-amber-100 text-amber-900 text-xs font-bold shadow-xs shrink-0 transition">
                                Cancel Edit
                            </button>
                        </div>

                        <!-- Duty Type Selector Pills -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Activity / Duty Category</label>
                            <div class="grid grid-cols-2 gap-2" id="duty-type-group">
                                <button type="button" onclick="setActivityType('Lecture Class')" data-type="Lecture Class" class="duty-type-btn flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-semibold transition bg-brand-50 border-brand-600 text-brand-700 shadow-sm">
                                    <i data-lucide="presentation" class="w-4 h-4"></i>
                                    <span>Lecture Class</span>
                                </button>
                                <button type="button" onclick="setActivityType('Lab Session')" data-type="Lab Session" class="duty-type-btn flex items-center gap-2 px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                                    <i data-lucide="flask-conical" class="w-4 h-4"></i>
                                    <span>Lab Session</span>
                                </button>
                                <button type="button" onclick="setActivityType('Exam Duty')" data-type="Exam Duty" class="duty-type-btn flex items-center gap-2 px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                                    <span>Exam Invigilation</span>
                                </button>
                                <button type="button" onclick="setActivityType('Tutorial / Extra')" data-type="Tutorial / Extra" class="duty-type-btn flex items-center gap-2 px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                                    <span>Tutorial / Extra</span>
                                </button>
                            </div>
                        </div>

                        <!-- Semester & Time Slot -->
                        <div class="space-y-2.5">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Semester</label>
                                    <select id="semester-select" onchange="handleSemesterChange()" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 focus:border-brand-600 outline-none">
                                        <option value="Sem 1">Semester 1</option>
                                        <option value="Sem 2">Semester 2</option>
                                        <option value="Sem 3" selected>Semester 3</option>
                                        <option value="Sem 4">Semester 4</option>
                                        <option value="Sem 5">Semester 5</option>
                                        <option value="Sem 6">Semester 6</option>
                                        <option value="Sem 7">Semester 7</option>
                                        <option value="Sem 8">Semester 8</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Slot Start <span class="text-rose-500">*</span></label>
                                    <input type="time" id="slot-start" value="11:15" oninput="checkLiveSlotConflict()" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-800 focus:border-brand-600 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Slot End <span class="text-rose-500">*</span></label>
                                    <input type="time" id="slot-end" value="12:15" oninput="checkLiveSlotConflict()" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-800 focus:border-brand-600 outline-none">
                                </div>
                            </div>

                            <!-- Live Time-Slot Availability / Overlap Indicator -->
                            <div id="live-conflict-banner" class="rounded-xl px-3.5 py-2.5 text-xs flex items-start gap-2.5 border transition-all bg-emerald-50/70 border-emerald-200 text-emerald-800">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                                <div class="flex-1">
                                    <span class="font-bold">Time Slot Available:</span>
                                    <span id="live-conflict-text">No overlap with any existing class or duty on this date.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Fields: CLASS TAKEN -->
                        <div id="class-fields-container" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Physics Paper / Course Name & Code <span class="text-rose-500">*</span></label>
                                <input type="text" id="subject-name" placeholder="e.g. Quantum Mechanics I (PHSA-CC5)" value="Quantum Mechanics I (PHSA-CC5)" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-brand-600 outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Room / Physics Lab No.</label>
                                    <input type="text" id="class-room" placeholder="e.g. Physics LH-302" value="Physics LH-302" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-brand-600 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Topic Covered</label>
                                    <input type="text" id="class-topic" placeholder="e.g. Wave-Particle Duality & Schrödinger Eq." value="Schrödinger Wave Equation & Operators" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-brand-600 outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Fields: EXAM DUTY (Hidden by default unless Exam Duty selected) -->
                        <div id="exam-fields-container" class="hidden space-y-4 bg-amber-50/70 p-4 rounded-xl border border-amber-200/80">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                                    <i data-lucide="award" class="w-4 h-4"></i> Exam Invigilation Particulars
                                </span>
                                <span class="text-[11px] px-2 py-0.5 rounded bg-amber-200/70 text-amber-900 font-semibold">Exam Duty Mode</span>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Examination Name & Paper Code <span class="text-rose-500">*</span></label>
                                <input type="text" id="exam-name" placeholder="e.g. CU B.Sc. Sem-5 Theory Exam — Solid State Physics (PHSA-CC12)" value="CU B.Sc. Internal Exam — Solid State Physics (PHSA-CC12)" class="w-full rounded-xl border border-amber-300 bg-white px-3.5 py-2 text-sm text-slate-800 focus:border-amber-600 outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Exam Hall / Room</label>
                                    <input type="text" id="exam-hall" placeholder="e.g. Heritage Main Block Hall-201" value="THC Block-B Hall 201" class="w-full rounded-xl border border-amber-300 bg-white px-3.5 py-2 text-sm text-slate-800 focus:border-amber-600 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Duty Role & Shift</label>
                                    <select id="exam-role" class="w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-slate-800 focus:border-amber-600 outline-none">
                                        <option value="Invigilator (Morning Shift)">Invigilator (Morning)</option>
                                        <option value="Invigilator (Afternoon Shift)">Invigilator (Afternoon)</option>
                                        <option value="Practical Examiner (Physics Lab)">Practical Examiner (Lab)</option>
                                        <option value="Room Supervisor">Room Supervisor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <label class="text-xs font-medium text-amber-900 flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="exam-track-roster" checked onchange="toggleExamRosterMode()" class="rounded text-amber-600 focus:ring-amber-500">
                                    <span>Track individual examinee attendance from semester roster</span>
                                </label>
                            </div>
                        </div>

                        <!-- Remarks / Notes -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Remarks / Additional Notes</label>
                            <textarea id="entry-remarks" rows="2" placeholder="Optional notes about assignment given, lab equipment, or exam incident..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:border-brand-600 outline-none resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Submit / Update Log Button -->
                    <div class="pt-5 mt-5 border-t border-slate-100 flex items-center gap-2">
                        <button id="cancel-edit-btn" type="button" onclick="cancelEditingActivityLog()" class="hidden py-3 px-4 rounded-xl border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm transition shrink-0">
                            Cancel
                        </button>
                        <button id="save-log-submit-btn" type="button" onclick="saveFullActivityLog()" class="flex-1 py-3 px-5 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 text-white font-bold text-sm shadow-lg shadow-brand-500/25 flex items-center justify-center gap-2 transition transform active:scale-[0.99]">
                            <i data-lucide="save" id="save-log-btn-icon" class="w-4 h-4"></i>
                            <span id="save-log-btn-text">Save Log & Student Attendance</span>
                        </button>
                    </div>
                </div>

                <!-- Right Column: Interactive Student Attendance Sheet (7 cols) -->
                <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 flex flex-col">
                    <!-- Header & Live Counters -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900">3. Student Attendance Sheet</h3>
                                <span id="active-sem-pill" class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">Sem 3</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Click on any student card to toggle <span class="text-emerald-600 font-semibold">Present (P)</span> or <span class="text-rose-600 font-semibold">Absent (A)</span></p>
                        </div>

                        <!-- Live Attendance Counters -->
                        <div class="flex items-center gap-2">
                            <div class="px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-center">
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Total</span>
                                <span id="stat-total-students" class="text-sm font-extrabold text-slate-800">0</span>
                            </div>
                            <div class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-center">
                                <span class="block text-[10px] uppercase font-bold text-emerald-600">Present</span>
                                <span id="stat-present-students" class="text-sm font-extrabold text-emerald-700">0</span>
                            </div>
                            <div class="px-3 py-1.5 rounded-xl bg-rose-50 border border-rose-200 text-center">
                                <span class="block text-[10px] uppercase font-bold text-rose-600">Absent</span>
                                <span id="stat-absent-students" class="text-sm font-extrabold text-rose-700">0</span>
                            </div>
                            <div class="px-3 py-1.5 rounded-xl bg-brand-50 border border-brand-200 text-center">
                                <span class="block text-[10px] uppercase font-bold text-brand-600">Rate</span>
                                <span id="stat-attendance-pct" class="text-sm font-extrabold text-brand-700">0%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar: Search & Quick Bulk Toggles -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 my-4">
                        <!-- Filter input -->
                        <div class="relative flex-1">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input type="text" id="student-search-input" oninput="renderEntryAttendanceList()" placeholder="Search student by name or roll number..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50/70 text-xs sm:text-sm focus:bg-white focus:border-brand-500 outline-none">
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <button type="button" onclick="markAllAttendance(true)" class="px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold flex items-center gap-1 transition">
                                <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                                <span>All Present</span>
                            </button>
                            <button type="button" onclick="markAllAttendance(false)" class="px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold flex items-center gap-1 transition">
                                <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                                <span>All Absent</span>
                            </button>
                            <button type="button" onclick="openQuickAddStudentModal()" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center gap-1 transition" title="Add Student to this Semester">
                                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                <span>+ Student</span>
                            </button>
                        </div>
                    </div>

                    <!-- Student Interactive Grid -->
                    <div id="student-attendance-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-[380px] overflow-y-auto pr-1 flex-1">
                        <!-- Dynamically populated by JS -->
                    </div>

                    <!-- Manual Headcount Override Box (When Exam Duty has individual roster unchecked) -->
                    <div id="manual-headcount-box" class="hidden my-auto py-8 px-6 rounded-2xl bg-slate-50 border border-dashed border-slate-300 text-center space-y-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center mx-auto">
                            <i data-lucide="users" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Manual Examinee Headcount Mode</h4>
                            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">Enter total allotted candidates and total present examinees in the examination hall.</p>
                        </div>
                        <div class="flex items-center justify-center gap-4 max-w-xs mx-auto">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Total Candidates</label>
                                <input type="number" id="manual-total-count" value="40" min="1" oninput="syncManualHeadcount()" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-center font-bold text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-emerald-700 mb-1">Present Candidates</label>
                                <input type="number" id="manual-present-count" value="38" min="0" oninput="syncManualHeadcount()" class="w-full rounded-xl border border-emerald-400 bg-emerald-50 px-3 py-2 text-center font-bold text-sm text-emerald-800">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Recent Logs Preview at Bottom of Tab 1 -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="history" class="w-4 h-4 text-brand-600"></i>
                            <span>Recent Activity Logs</span>
                        </h3>
                        <p class="text-xs text-slate-500">Latest classes & exam invigilation duties saved in your logbook</p>
                    </div>
                    <button onclick="switchTab('reports')" class="text-xs font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                        <span>Open Full Date-Range Report & Export</span>
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-2.5 px-3">Date & Campus Time</th>
                                <th class="py-2.5 px-3">Type</th>
                                <th class="py-2.5 px-3">Semester & Slot</th>
                                <th class="py-2.5 px-3">Subject / Exam Duty Details</th>
                                <th class="py-2.5 px-3">Attendance</th>
                                <th class="py-2.5 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="recent-logs-tbody" class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- TAB 2: DATE RANGE REPORTS & EXPORT         -->
        <!-- ========================================== -->
        <section id="tab-reports" class="hidden space-y-6">
            
            <!-- Filter & Export Control Bar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-5">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-brand-600"></i>
                            <span>Date & Date-Range Log Report & Export Center</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Filter by single date, custom date range, semester, or duty category and export to Excel or PDF</p>
                    </div>

                    <!-- Export Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <button onclick="exportReportToExcel()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs sm:text-sm shadow-sm shadow-emerald-500/20 transition">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Export Excel (.xlsx)</span>
                        </button>
                        <button onclick="exportReportToPDF()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs sm:text-sm shadow-sm shadow-rose-500/20 transition">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>Export Official PDF</span>
                        </button>
                    </div>
                </div>

                <!-- Filter Controls Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5 items-end">
                    <!-- Filter Mode: Date Range vs Single Date -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Filter Mode</label>
                        <select id="filter-date-mode" onchange="handleFilterModeChange()" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs sm:text-sm font-semibold text-slate-800 outline-none focus:border-brand-600">
                            <option value="range" selected>Date Range (From - To)</option>
                            <option value="single">Single Specific Date</option>
                            <option value="all">All Dates</option>
                        </select>
                    </div>

                    <!-- From Date / Single Date -->
                    <div id="filter-from-wrapper">
                        <label id="filter-from-label" class="block text-xs font-semibold text-slate-600 mb-1">From Date</label>
                        <input type="date" id="filter-from-date" onchange="renderReportView()" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs sm:text-sm font-medium text-slate-800 outline-none focus:border-brand-600">
                    </div>

                    <!-- To Date -->
                    <div id="filter-to-wrapper">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">To Date</label>
                        <input type="date" id="filter-to-date" onchange="renderReportView()" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs sm:text-sm font-medium text-slate-800 outline-none focus:border-brand-600">
                    </div>

                    <!-- Semester Filter -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Semester</label>
                        <select id="filter-semester" onchange="renderReportView()" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs sm:text-sm font-medium text-slate-800 outline-none focus:border-brand-600">
                            <option value="ALL">All Semesters</option>
                            <option value="Sem 1">Semester 1</option>
                            <option value="Sem 2">Semester 2</option>
                            <option value="Sem 3">Semester 3</option>
                            <option value="Sem 4">Semester 4</option>
                            <option value="Sem 5">Semester 5</option>
                            <option value="Sem 6">Semester 6</option>
                            <option value="Sem 7">Semester 7</option>
                            <option value="Sem 8">Semester 8</option>
                        </select>
                    </div>

                    <!-- Activity / Duty Filter -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Activity Type</label>
                        <select id="filter-type" onchange="renderReportView()" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs sm:text-sm font-medium text-slate-800 outline-none focus:border-brand-600">
                            <option value="ALL">All Activities</option>
                            <option value="CLASSES_ONLY">All Classes (Lecture/Lab)</option>
                            <option value="Lecture Class">Lecture Classes Only</option>
                            <option value="Lab Session">Lab Sessions Only</option>
                            <option value="Exam Duty">Exam Invigilation Duty Only</option>
                            <option value="Tutorial / Extra">Tutorial / Extra Only</option>
                        </select>
                    </div>

                    <!-- Quick Reset Filter -->
                    <div class="flex gap-2">
                        <button onclick="setQuickDatePreset('today')" class="flex-1 py-2 px-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">Today</button>
                        <button onclick="setQuickDatePreset('all')" class="flex-1 py-2 px-2.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 font-semibold text-xs transition">Show All</button>
                    </div>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider">Days Logged</span>
                        <i data-lucide="calendar-check" class="w-4 h-4 text-brand-500"></i>
                    </div>
                    <p id="kpi-days" class="text-2xl font-extrabold text-slate-900">0</p>
                    <p id="kpi-hours-sub" class="text-[11px] text-slate-500 mt-0.5">0 hrs on campus</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider">Classes Taken</span>
                        <i data-lucide="book-open" class="w-4 h-4 text-indigo-500"></i>
                    </div>
                    <p id="kpi-classes" class="text-2xl font-extrabold text-slate-900">0</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Lectures, Labs & Tutorials</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider">Exam Duties</span>
                        <i data-lucide="shield-check" class="w-4 h-4 text-amber-500"></i>
                    </div>
                    <p id="kpi-exams" class="text-2xl font-extrabold text-slate-900">0</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Invigilation & Supervision</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider">Avg Attendance</span>
                        <i data-lucide="user-check" class="w-4 h-4 text-emerald-500"></i>
                    </div>
                    <p id="kpi-avg-att" class="text-2xl font-extrabold text-emerald-600">0%</p>
                    <p id="kpi-att-counts" class="text-[11px] text-slate-500 mt-0.5">0 / 0 students</p>
                </div>

                <div class="col-span-2 md:col-span-1 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-400 mb-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider">Search Keyword</span>
                        <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                    </div>
                    <input type="text" id="report-search" oninput="renderReportView()" placeholder="Subject, student, room..." class="w-full mt-1 px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-brand-500 outline-none">
                </div>
            </div>

            <!-- Detailed Filtered Report Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Detailed Log & Student Attendance Records</h3>
                        <p id="report-count-subtitle" class="text-xs text-slate-500">Showing matching records</p>
                    </div>
                    <div class="text-xs text-slate-500 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-brand-500"></span> Click "View Students" to inspect individual roll-call</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Arrival / Departure</th>
                                <th class="py-3 px-4">Activity Type</th>
                                <th class="py-3 px-4">Sem & Time Slot</th>
                                <th class="py-3 px-4">Course / Exam Duty Details</th>
                                <th class="py-3 px-4">Present / Total</th>
                                <th class="py-3 px-4 text-right">Student Roll-Call & Actions</th>
                            </tr>
                        </thead>
                        <tbody id="report-table-body" class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <!-- Dynamically populated -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- TAB 3: SEMESTER ROSTER MANAGER             -->
        <!-- ========================================== -->
        <section id="tab-roster" class="hidden space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="users" class="w-5 h-5 text-brand-600"></i>
                            <span>Semester Student Roster Manager</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Manage student lists for each semester (Sem 1 to Sem 8) used during class attendance logging</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="text-xs font-bold uppercase text-slate-500">Select Semester:</label>
                        <select id="roster-sem-select" onchange="renderRosterManager()" class="rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm font-bold text-brand-700 outline-none focus:border-brand-600">
                            <option value="Sem 1">Semester 1</option>
                            <option value="Sem 2">Semester 2</option>
                            <option value="Sem 3" selected>Semester 3</option>
                            <option value="Sem 4">Semester 4</option>
                            <option value="Sem 5">Semester 5</option>
                            <option value="Sem 6">Semester 6</option>
                            <option value="Sem 7">Semester 7</option>
                            <option value="Sem 8">Semester 8</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-5">
                    <!-- Add Single or Bulk Students -->
                    <div class="space-y-4 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                        <h3 class="text-sm font-bold text-slate-800">Add Student to Selected Semester</h3>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Roll Number</label>
                            <input type="text" id="new-student-roll" onkeydown="if(event.key==='Enter') addStudentToRoster()" placeholder="e.g. PHS/25/315" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm uppercase font-mono outline-none focus:border-brand-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Student Full Name</label>
                            <input type="text" id="new-student-name" onkeydown="if(event.key==='Enter') addStudentToRoster()" placeholder="e.g. Rituparna Sengupta" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand-600">
                        </div>
                        <button type="button" onclick="addStudentToRoster()" class="w-full py-2.5 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs sm:text-sm flex items-center justify-center gap-2 transition shadow-sm">
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                            <span>Add Student to Roster</span>
                        </button>
                    </div>

                    <!-- Current Student List -->
                    <div class="lg:col-span-2 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 id="roster-list-heading" class="text-sm font-bold text-slate-800">Enrolled Students</h3>
                            <span id="roster-count-badge" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-brand-50 text-brand-700">0 Students</span>
                        </div>
                        <div id="roster-manager-list" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-[420px] overflow-y-auto pr-1">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- MODALS: STUDENT DETAIL & CONFLICT -->
    <!-- ========================================== -->
    <!-- MODAL: VIEW LOG & EACH STUDENT ATTENDANCE  -->
    <!-- ========================================== -->
    <div id="student-detail-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div>
                    <span id="modal-badge-type" class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded bg-brand-500 text-white">Lecture Class</span>
                    <h3 id="modal-title" class="text-base font-bold mt-1">Data Structures & Algorithms (CS-301)</h3>
                    <p id="modal-subtitle" class="text-xs text-slate-300">Date: 2025-02-20 | Sem 3 | Slot: 10:00 - 11:00</p>
                </div>
                <button onclick="closeStudentModal()" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Info Bar -->
            <div id="modal-meta-bar" class="px-6 py-3 bg-slate-50 border-b border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <!-- Populated dynamically -->
            </div>

            <!-- Modal Body: Individual Student Roll Call -->
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Individual Student Attendance Breakdown</h4>
                    <div class="flex items-center gap-2">
                        <button onclick="filterModalStudents('ALL')" id="modal-filter-all" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-900 text-white">All</button>
                        <button onclick="filterModalStudents('P')" id="modal-filter-p" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Present Only</button>
                        <button onclick="filterModalStudents('A')" id="modal-filter-a" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100">Absent Only</button>
                    </div>
                </div>

                <div id="modal-student-list" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <!-- Populated by JS -->
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <button id="modal-single-pdf-btn" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition">
                    <i data-lucide="file-down" class="w-4 h-4"></i>
                    <span>Download Session Attendance Sheet (PDF)</span>
                </button>
                <button onclick="closeStudentModal()" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-semibold transition">Close</button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: DATE & TIME OVERLAP CONFLICT ALERT  -->
    <!-- ========================================== -->
    <div id="conflict-alert-modal" class="fixed inset-0 z-50 hidden bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-rose-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Alert Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-rose-600 to-red-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                        <i data-lucide="shield-alert" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded bg-rose-900/50 text-rose-100">Schedule Notice</span>
                        <h3 id="conflict-modal-title" class="text-base font-bold mt-0.5">Time Slot Conflict Detected!</h3>
                    </div>
                </div>
                <button type="button" onclick="closeConflictModal()" class="p-1.5 rounded-lg text-rose-100 hover:text-white hover:bg-white/10 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Alert Body -->
            <div class="p-6 space-y-4">
                <p id="conflict-modal-desc" class="text-xs sm:text-sm text-slate-600 leading-relaxed font-medium">
                    A session conflict was detected on the selected date.
                </p>

                <!-- Conflicting Details Box -->
                <div id="conflict-details-box" class="p-4 rounded-xl bg-rose-50/80 border border-rose-200 text-xs space-y-2">
                    <!-- Dynamic details -->
                </div>

                <!-- Suggestion / Action Info -->
                <div class="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-200/80 text-xs text-indigo-900 flex items-start gap-2.5">
                    <i data-lucide="sparkles" class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5"></i>
                    <p id="conflict-suggestion-text" class="font-medium">
                        You can auto-adjust to the next available slot, edit the conflicting entry, or save anyway for parallel sessions.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2">
                    <button type="button" onclick="closeConflictModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition order-last sm:order-first">
                        Cancel & Revise
                    </button>
                    <button type="button" id="conflict-edit-existing-btn" class="hidden px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        <span>Edit Existing Log</span>
                    </button>
                    <button type="button" id="conflict-force-save-btn" class="hidden px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition">
                        <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                        <span>Save Anyway (Multi-Batch)</span>
                    </button>
                    <button type="button" id="conflict-auto-fix-btn" class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm transition">
                        <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                        <span>Auto-Adjust Slot</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- MODAL: EDIT STUDENT IN SEMESTER ROSTER                    -->
    <!-- ========================================================== -->
    <div id="edit-student-modal" class="fixed inset-0 z-50 hidden bg-slate-900/75 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-brand-600 to-indigo-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-white">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 id="edit-student-modal-title" class="text-base font-bold">Edit Student Details</h3>
                        <p id="edit-student-modal-subtitle" class="text-xs text-brand-100">Update Roll Number or Student Name</p>
                    </div>
                </div>
                <button type="button" onclick="closeEditStudentModal()" class="p-1.5 rounded-lg text-brand-200 hover:text-white hover:bg-white/10 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form onsubmit="submitEditStudent(event)" class="p-6 space-y-4">
                <input type="hidden" id="edit-student-sem">
                <input type="hidden" id="edit-student-old-roll">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Roll Number <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit-student-new-roll" required placeholder="e.g. PHS/25/301" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-brand-600 uppercase font-mono outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Student Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit-student-new-name" required placeholder="e.g. Aakash Bhattacharya" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-brand-600 outline-none">
                </div>

                <div id="edit-student-error" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold"></div>

                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeEditStudentModal()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Cancel
                    </button>
                    <button type="submit" id="edit-student-submit-btn" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-500/20 flex items-center gap-1.5 transition">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Save Student Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- MODAL: QUICK ADD STUDENT FROM TAB 1 ATTENDANCE SHEET       -->
    <!-- ========================================================== -->
    <div id="quick-add-student-modal" class="fixed inset-0 z-50 hidden bg-slate-900/75 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-white">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold">Add Student to <span id="quick-add-sem-title">Semester</span></h3>
                        <p class="text-xs text-emerald-100">Enrolls student into MySQL roster and marks attendance</p>
                    </div>
                </div>
                <button type="button" onclick="closeQuickAddStudentModal()" class="p-1.5 rounded-lg text-emerald-200 hover:text-white hover:bg-white/10 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form onsubmit="submitQuickAddStudent(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Roll Number <span class="text-rose-500">*</span></label>
                    <input type="text" id="quick-add-roll" required placeholder="e.g. PHS/25/315" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm uppercase font-mono outline-none focus:border-emerald-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Student Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="quick-add-name" required placeholder="e.g. Rituparna Sengupta" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600">
                </div>
                <div id="quick-add-error" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold"></div>
                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeQuickAddStudentModal()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Cancel
                    </button>
                    <button type="submit" id="quick-add-submit-btn" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-500/20 flex items-center gap-1.5 transition">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                        <span>Add & Mark Present</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- MODAL: SECURE PASSWORD VERIFICATION FOR RECORD DELETION   -->
    <!-- ========================================================== -->
    <div id="delete-password-modal" class="fixed inset-0 z-50 hidden bg-slate-900/75 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-rose-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-rose-600 to-red-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white shrink-0">
                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded bg-rose-900/50 text-rose-100">Security Verification</span>
                        <h3 id="delete-modal-title" class="text-base font-bold mt-0.5">Confirm Permanent Deletion</h3>
                    </div>
                </div>
                <button type="button" onclick="closeDeletePasswordModal()" class="p-1.5 rounded-lg text-rose-200 hover:text-white hover:bg-white/10 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form onsubmit="confirmDeleteWithPassword(event)" class="p-6 space-y-4">
                <p id="delete-modal-desc" class="text-xs text-slate-600 font-medium">To protect your academic log records from accidental or unauthorized deletion, please enter your password to confirm:</p>

                <!-- Record Meta Box -->
                <div id="delete-modal-meta" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 font-mono">
                    <!-- Populated dynamically -->
                </div>

                <!-- Password Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Enter Your Faculty Account Password <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                        <input type="password" id="delete-modal-password" required placeholder="Enter password" class="w-full rounded-xl border border-slate-300 pl-10 pr-10 py-2.5 text-sm text-slate-900 focus:border-rose-600 focus:ring-2 focus:ring-rose-500/20 outline-none transition">
                        <button type="button" onclick="togglePassVisibility('delete-modal-password', this)" class="absolute right-3.5 top-2.5 text-slate-400 hover:text-slate-600">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Error Box -->
                <div id="delete-modal-error" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold flex items-center gap-2">
                    <!-- Error text populated dynamically -->
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeDeletePasswordModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                        Cancel
                    </button>
                    <button type="submit" id="delete-modal-submit-btn" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-500/20 flex items-center gap-2 transition">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span>Permanently Delete</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- MODAL: EDIT FACULTY PROFILE & PASSWORD                     -->
    <!-- ========================================================== -->
    <div id="profile-edit-modal" class="fixed inset-0 z-50 hidden bg-slate-900/75 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-indigo-950 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white">
                        <i data-lucide="user-cog" class="w-5 h-5 text-indigo-300"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold">Faculty Profile & Settings</h3>
                        <p class="text-xs text-slate-300">Manage your profile details and credentials</p>
                    </div>
                </div>
                <button type="button" onclick="closeProfileModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form onsubmit="submitProfileUpdate(event)" class="p-6 space-y-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Username (Read-Only)</label>
                    <input type="text" value="<?= htmlspecialchars($currentUser['username']) ?>" readonly class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-500 outline-none font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Faculty Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="prof-edit-name" required value="<?= htmlspecialchars($currentUser['full_name']) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Department & College / Institution</label>
                    <input type="text" id="prof-edit-dept" value="<?= htmlspecialchars($currentUser['department']) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Institutional Email</label>
                    <input type="email" id="prof-edit-email" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <p class="text-xs font-bold text-slate-700 mb-2">Change Password (Optional)</p>
                    <div class="space-y-2">
                        <input type="password" id="prof-edit-current-pass" placeholder="Enter current password" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                        <input type="password" id="prof-edit-new-pass" placeholder="Enter new password (min. 4 chars)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-brand-600 outline-none">
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-between">
                    <button type="button" onclick="handleSignOut()" class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold border border-rose-200 transition flex items-center gap-1.5">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        <span>Sign Out</span>
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // Active Faculty Profile
        let FACULTY_PROFILE = {
            id: <?= json_encode((int)$currentUser['id']) ?>,
            name: <?= json_encode($currentUser['full_name']) ?>,
            designation: <?= json_encode($currentUser['department']) ?>,
            email: <?= json_encode($currentUser['email'] ?? '') ?>,
            username: <?= json_encode($currentUser['username']) ?>
        };

        let semesterRosters = {};
        let dailyTimings = {};
        let activityLogs = [];
        let currentActivityType = "Lecture Class";
        let currentSessionAttendance = {};
        let activeModalLog = null;
        let activeModalFilter = 'ALL';
        let pendingDeleteAction = null;
        window.editingLogId = null;

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function escapeJs(str) {
            if (!str) return '';
            return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        }

        function getInitials(name) {
            if (!name) return "FP";
            const parts = name.trim().split(/\s+/);
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }

        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<i data-lucide="eye-off" class="w-4 h-4"></i>';
            } else {
                input.type = 'password';
                btn.innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i>';
            }
            lucide.createIcons();
        }

        function openProfileModal() {
            document.getElementById('profile-edit-modal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeProfileModal() {
            document.getElementById('profile-edit-modal').classList.add('hidden');
        }

        async function submitProfileUpdate(event) {
            event.preventDefault();
            const fullName = document.getElementById('prof-edit-name').value.trim();
            const department = document.getElementById('prof-edit-dept').value.trim();
            const email = document.getElementById('prof-edit-email').value.trim();
            const currentPass = document.getElementById('prof-edit-current-pass').value;
            const newPass = document.getElementById('prof-edit-new-pass').value;

            if (!fullName) {
                showToast("Full name cannot be empty.", "error");
                return;
            }

            try {
                const resp = await fetch('auth.php?action=update_profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        full_name: fullName,
                        department,
                        email,
                        current_password: currentPass,
                        new_password: newPass
                    })
                });
                const res = await resp.json();
                if (res.success) {
                    showToast("Profile updated successfully!", "success");
                    closeProfileModal();
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(res.error || "Update failed.", "error");
                }
            } catch (e) {
                showToast("Network error updating profile.", "error");
            }
        }

        async function handleSignOut() {
            if (!confirm("Are you sure you want to sign out of your faculty account?")) return;
            try {
                await fetch('auth.php?action=logout');
                showToast("Signed out successfully.", "info");
                setTimeout(() => window.location.reload(), 300);
            } catch (e) {
                window.location.reload();
            }
        }

        // --- PASSWORD DELETION VERIFICATION MODAL ---
        function openDeletePasswordModal(actionConfig) {
            pendingDeleteAction = actionConfig;
            document.getElementById('delete-modal-title').textContent = actionConfig.title || 'Confirm Permanent Deletion';
            document.getElementById('delete-modal-desc').textContent = actionConfig.desc || 'Please enter your password to confirm permanent deletion of this record:';
            document.getElementById('delete-modal-meta').textContent = actionConfig.meta || '';
            document.getElementById('delete-modal-password').value = '';
            document.getElementById('delete-modal-error').classList.add('hidden');
            document.getElementById('delete-password-modal').classList.remove('hidden');
            document.getElementById('delete-modal-password').focus();
            lucide.createIcons();
        }

        function closeDeletePasswordModal() {
            document.getElementById('delete-password-modal').classList.add('hidden');
            pendingDeleteAction = null;
        }

        async function confirmDeleteWithPassword(event) {
            if (event) event.preventDefault();
            if (!pendingDeleteAction) return;

            const password = document.getElementById('delete-modal-password').value;
            const errorBox = document.getElementById('delete-modal-error');
            const submitBtn = document.getElementById('delete-modal-submit-btn');

            if (!password) {
                errorBox.textContent = "Please enter your password.";
                errorBox.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Verifying password...';
            lucide.createIcons();

            try {
                if (pendingDeleteAction.type === 'log') {
                    const resp = await fetch('api.php?action=delete_log', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            log_id: pendingDeleteAction.id,
                            password: password
                        })
                    });
                    const res = await resp.json();
                    if (res.success) {
                        activityLogs = activityLogs.filter(l => l.id !== pendingDeleteAction.id);
                        updateTotalBadge();
                        renderRecentLogsTable();
                        renderReportView();
                        checkLiveSlotConflict();
                        closeDeletePasswordModal();
                        showToast(`Log entry ${pendingDeleteAction.id} has been permanently deleted.`, "info");
                    } else {
                        errorBox.textContent = res.error || "Incorrect password. Deletion cancelled.";
                        errorBox.classList.remove('hidden');
                    }
                } else if (pendingDeleteAction.type === 'student') {
                    const resp = await fetch('api.php?action=remove_roster_student', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            semester: pendingDeleteAction.sem,
                            roll_no: pendingDeleteAction.roll,
                            password: password
                        })
                    });
                    const res = await resp.json();
                    if (res.success) {
                        const list = semesterRosters[pendingDeleteAction.sem] || [];
                        semesterRosters[pendingDeleteAction.sem] = list.filter(s => s.roll.toLowerCase().trim() !== pendingDeleteAction.roll.toLowerCase().trim());
                        delete currentSessionAttendance[pendingDeleteAction.roll];
                        renderRosterManager();
                        renderEntryAttendanceList();
                        closeDeletePasswordModal();
                        showToast(`Student ${pendingDeleteAction.roll} permanently removed from ${pendingDeleteAction.sem} roster.`, "info");
                    } else {
                        errorBox.textContent = res.error || "Incorrect password. Removal cancelled.";
                        errorBox.classList.remove('hidden');
                    }
                }
            } catch (e) {
                errorBox.textContent = "Server error while processing deletion.";
                errorBox.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="trash-2" class="w-4 h-4"></i> Permanently Delete';
                lucide.createIcons();
            }
        }

        // --- LOAD STATE FROM MYSQL ---
        async function loadStateFromStorage() {
            try {
                const resp = await fetch('api.php?action=get_state');
                const data = await resp.json();
                if (data.success) {
                    FACULTY_PROFILE = {
                        id: data.user.id,
                        username: data.user.username,
                        name: data.user.full_name,
                        designation: data.user.department,
                        email: data.user.email || '',
                        accountKey: data.user.username
                    };
                    semesterRosters = data.semesterRosters || {};
                    dailyTimings = data.dailyTimings || {};
                    activityLogs = data.activityLogs || [];

                    handleDateChange();
                    handleSemesterChange();
                    renderRecentLogsTable();
                    renderReportView();
                    renderRosterManager();
                    updateTotalBadge();
                    checkLiveSlotConflict();
                } else if (data.logged_in === false) {
                    window.location.reload();
                } else {
                    showToast("Failed to load records from MySQL: " + (data.error || ''), "error");
                }
            } catch (e) {
                console.error("Network or API error:", e);
                showToast("Error connecting to MySQL database server.", "error");
            }
        }

        // Helpers
        function getRelativeDateStr(offset) {
            const d = new Date();
            d.setDate(d.getDate() + offset);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        function timeToMinutes(timeStr) {
            if (!timeStr || !timeStr.includes(':')) return -1;
            const [h, m] = timeStr.split(':').map(Number);
            if (isNaN(h) || isNaN(m)) return -1;
            return h * 60 + m;
        }

        function minutesToTime(totalMins) {
            const h = Math.floor(totalMins / 60);
            const m = totalMins % 60;
            return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
        }

        function switchTab(tabId) {
            ['entry', 'reports', 'roster'].forEach(id => {
                const sec = document.getElementById(`tab-${id}`);
                const btn = document.getElementById(`tab-btn-${id}`);
                if (id === tabId) {
                    sec.classList.remove('hidden');
                    btn.className = "tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap bg-white text-brand-600 shadow-sm";
                } else {
                    sec.classList.add('hidden');
                    btn.className = "tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap text-slate-600 hover:text-slate-900";
                }
            });

            if (tabId === 'reports') {
                renderReportView();
            } else if (tabId === 'roster') {
                renderRosterManager();
            }
            lucide.createIcons();
        }

        // --- DAILY ARRIVAL & DEPARTURE TIMING LOGIC ---
        function initDefaultDates() {
            const today = getRelativeDateStr(0);
            const threeDaysAgo = getRelativeDateStr(-3);

            document.getElementById('log-date').value = today;
            document.getElementById('filter-from-date').value = threeDaysAgo;
            document.getElementById('filter-to-date').value = today;

            handleDateChange();
        }

        function handleDateChange() {
            const dateVal = document.getElementById('log-date').value;
            const saved = dailyTimings[dateVal];
            if (saved) {
                document.getElementById('arrival-time').value = saved.arrival || "09:15";
                document.getElementById('departure-time').value = saved.departure || "16:45";
            } else {
                document.getElementById('arrival-time').value = "09:15";
                document.getElementById('departure-time').value = "16:45";
            }
            updateCampusDuration();
            checkLiveSlotConflict();
        }

        function punchNow(type) {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const timeStr = `${hh}:${mm}`;

            if (type === 'arrival') {
                document.getElementById('arrival-time').value = timeStr;
                showToast(`Arrival time punched at ${timeStr}`, 'success');
            } else {
                document.getElementById('departure-time').value = timeStr;
                showToast(`Departure time punched at ${timeStr}`, 'success');
            }
            updateCampusDuration();
        }

        function calculateDurationMinutes(startStr, endStr) {
            if (!startStr || !endStr) return 0;
            const [sh, sm] = startStr.split(':').map(Number);
            const [eh, em] = endStr.split(':').map(Number);
            const diff = (eh * 60 + em) - (sh * 60 + sm);
            return diff > 0 ? diff : 0;
        }

        function formatMinutesToHrs(mins) {
            if (mins <= 0) return "0h 0m";
            const h = Math.floor(mins / 60);
            const m = mins % 60;
            return `${h}h ${m}m`;
        }

        function updateCampusDuration() {
            const arr = document.getElementById('arrival-time').value;
            const dep = document.getElementById('departure-time').value;
            const mins = calculateDurationMinutes(arr, dep);
            const textEl = document.getElementById('campus-hours-text');
            if (mins > 0) {
                textEl.textContent = `${formatMinutesToHrs(mins)} on campus (${arr} - ${dep})`;
            } else {
                textEl.textContent = `Invalid time range (Departure must be after Arrival)`;
            }
            checkLiveSlotConflict();
        }

        async function saveDailyTimingOnly() {
            const dateVal = document.getElementById('log-date').value;
            const arr = document.getElementById('arrival-time').value;
            const dep = document.getElementById('departure-time').value;

            if (!dateVal) {
                showToast("Please select a valid date first.", "error");
                return;
            }

            if (timeToMinutes(dep) <= timeToMinutes(arr)) {
                openConflictModal({
                    type: "INVALID_CAMPUS_TIME",
                    date: dateVal,
                    newStart: arr,
                    newEnd: dep
                });
                return;
            }

            try {
                const resp = await fetch('api.php?action=save_daily_timing', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        log_date: dateVal,
                        arrival_time: arr,
                        departure_time: dep
                    })
                });
                const res = await resp.json();
                if (res.success) {
                    dailyTimings[dateVal] = { arrival: arr, departure: dep };
                    let updatedCount = 0;
                    activityLogs.forEach(log => {
                        if (log.date === dateVal) {
                            log.arrivalTime = arr;
                            log.departureTime = dep;
                            updatedCount++;
                        }
                    });
                    renderRecentLogsTable();
                    renderReportView();
                    checkLiveSlotConflict();
                    showToast(`Campus timing (${arr} - ${dep}) saved in MySQL for ${dateVal}${updatedCount ? ` & synced across ${updatedCount} log(s)` : ''}.`, "success");
                } else {
                    showToast("Error: " + (res.error || "Failed to save timing"), "error");
                }
            } catch (e) {
                console.error("Error saving timing to MySQL:", e);
                showToast("Server error while saving daily timing.", "error");
            }
        }

        // --- STRICT DATE & TIME OVERLAP VALIDATION ENGINE ---
        function validateSessionTimeSlot(dateVal, startStr, endStr, arrivalStr, departureStr) {
            const newStart = timeToMinutes(startStr);
            const newEnd = timeToMinutes(endStr);
            const arrMins = timeToMinutes(arrivalStr);
            const depMins = timeToMinutes(departureStr);

            if (arrMins >= 0 && depMins >= 0 && depMins <= arrMins) {
                return {
                    valid: false,
                    reason: "INVALID_CAMPUS_TIME",
                    message: `Campus Departure Time (${departureStr}) must be later than Arrival Time (${arrivalStr}).`
                };
            }

            if (newStart < 0 || newEnd < 0 || newEnd <= newStart) {
                return {
                    valid: false,
                    reason: "INVALID_SLOT_RANGE",
                    message: `Session End Time (${endStr || '--:--'}) must be strictly after Start Time (${startStr || '--:--'}).`
                };
            }

            // Exclude currently edited log so editing a record never flags a conflict with itself!
            const logsOnSameDate = activityLogs.filter(l => l.date === dateVal && (!window.editingLogId || l.id !== window.editingLogId));
            for (const existing of logsOnSameDate) {
                const exStart = timeToMinutes(existing.slotStart);
                const exEnd = timeToMinutes(existing.slotEnd);
                if (newStart < exEnd && newEnd > exStart) {
                    return {
                        valid: false,
                        reason: "OVERLAP_CONFLICT",
                        conflictingLog: existing,
                        sameDateLogs: logsOnSameDate,
                        message: `Overlaps with ${existing.activityType}: "${existing.subjectOrExam}" (${existing.slotStart} – ${existing.slotEnd})`
                    };
                }
            }

            // Check if session falls outside Campus Arrival & Departure window
            const facultyName = (FACULTY_PROFILE && FACULTY_PROFILE.name) ? FACULTY_PROFILE.name : 'Faculty';
            if (arrMins >= 0 && depMins >= 0 && (newStart < arrMins || newEnd > depMins)) {
                return {
                    valid: false,
                    reason: "OUTSIDE_CAMPUS_HOURS",
                    attemptedStart: startStr,
                    attemptedEnd: endStr,
                    arrivalStr: arrivalStr,
                    departureStr: departureStr,
                    message: `Session slot (${startStr} – ${endStr}) falls outside ${facultyName}'s logged campus hours (${arrivalStr} – ${departureStr}) on ${dateVal}.`
                };
            }

            return {
                valid: true,
                sameDateLogs: logsOnSameDate
            };
        }

        // Find the next non-overlapping 1-hour slot on the selected date
        function findNextAvailableSlot(dateVal, durationMins = 60) {
            const arrMins = Math.max(0, timeToMinutes(document.getElementById('arrival-time').value || "09:15"));
            const depMins = timeToMinutes(document.getElementById('departure-time').value || "16:45");
            const logsOnDate = activityLogs
                .filter(l => l.date === dateVal)
                .map(l => ({ s: timeToMinutes(l.slotStart), e: timeToMinutes(l.slotEnd) }))
                .sort((a, b) => a.s - b.s);

            let candidateStart = arrMins;
            for (const interval of logsOnDate) {
                if (candidateStart + durationMins <= interval.s) {
                    break;
                }
                if (candidateStart < interval.e) {
                    candidateStart = interval.e + 15; // 15-min break after existing class
                }
            }

            if (candidateStart + durationMins <= depMins) {
                return {
                    start: minutesToTime(candidateStart),
                    end: minutesToTime(candidateStart + durationMins)
                };
            }
            return null;
        }

        // Live conflict badge updater as user edits Date, Slot Start, or Slot End
        function checkLiveSlotConflict() {
            const banner = document.getElementById('live-conflict-banner');
            if (!banner) return;

            const dateVal = document.getElementById('log-date').value;
            const slotStart = document.getElementById('slot-start').value;
            const slotEnd = document.getElementById('slot-end').value;
            const arrivalTime = document.getElementById('arrival-time').value;
            const departureTime = document.getElementById('departure-time').value;

            const check = validateSessionTimeSlot(dateVal, slotStart, slotEnd, arrivalTime, departureTime);

            if (!check.valid) {
                if (check.reason === "OVERLAP_CONFLICT") {
                    const c = check.conflictingLog;
                    banner.className = "rounded-xl px-3.5 py-2.5 text-xs flex items-start gap-2.5 border transition-all bg-rose-50 border-rose-300 text-rose-900 shadow-xs";
                    banner.innerHTML = `
                        <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600 shrink-0 mt-0.5"></i>
                        <div class="flex-1">
                            <span class="font-extrabold uppercase tracking-wide text-rose-700">Time Overlap Conflict on ${dateVal}:</span>
                            <p class="mt-0.5 font-medium">Slot <strong>${slotStart} – ${slotEnd}</strong> clashes with <strong>${c.subjectOrExam}</strong> (${c.semester}, ${c.slotStart} – ${c.slotEnd} in ${c.roomOrHall}).</p>
                        </div>
                    `;
                } else {
                    banner.className = "rounded-xl px-3.5 py-2.5 text-xs flex items-start gap-2.5 border transition-all bg-amber-50 border-amber-300 text-amber-900";
                    banner.innerHTML = `
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                        <div class="flex-1">
                            <span class="font-bold">Time Validation Alert:</span>
                            <span>${check.message}</span>
                        </div>
                    `;
                }
            } else {
                const countOnDate = (check.sameDateLogs || []).length;
                banner.className = "rounded-xl px-3.5 py-2.5 text-xs flex items-start gap-2.5 border transition-all bg-emerald-50/70 border-emerald-200 text-emerald-800";
                banner.innerHTML = `
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <span class="font-bold">Slot ${slotStart} – ${slotEnd} Available:</span>
                        <span>No overlap on ${dateVal} (${countOnDate} other session${countOnDate === 1 ? '' : 's'} logged on this date).</span>
                    </div>
                `;
            }
            lucide.createIcons();
        }

        function openConflictModal(conflictInfo) {
            const modal = document.getElementById('conflict-alert-modal');
            const titleEl = document.getElementById('conflict-modal-title');
            const descEl = document.getElementById('conflict-modal-desc');
            const boxEl = document.getElementById('conflict-details-box');
            const suggestionEl = document.getElementById('conflict-suggestion-text');
            const autoFixBtn = document.getElementById('conflict-auto-fix-btn');
            const forceSaveBtn = document.getElementById('conflict-force-save-btn');
            const editExistingBtn = document.getElementById('conflict-edit-existing-btn');

            if (!modal) return;

            if (autoFixBtn) autoFixBtn.classList.add('hidden');
            if (forceSaveBtn) forceSaveBtn.classList.add('hidden');
            if (editExistingBtn) editExistingBtn.classList.add('hidden');

            const facultyName = (FACULTY_PROFILE && FACULTY_PROFILE.name) ? FACULTY_PROFILE.name : 'Faculty';

            if (conflictInfo.reason === "OVERLAP_CONFLICT" && conflictInfo.conflictingLog) {
                const c = conflictInfo.conflictingLog;
                titleEl.textContent = "Time Slot Overlap Detected!";
                descEl.innerHTML = `${facultyName} already has a <strong>${escapeHtml(c.activityType)}</strong> scheduled on <strong>${conflictInfo.date}</strong> during an overlapping time window.`;

                boxEl.innerHTML = `
                    <div class="flex items-center justify-between border-b border-rose-200/80 pb-2">
                        <span class="font-extrabold text-rose-800 uppercase tracking-wider">Conflicting Existing Entry (${escapeHtml(c.id)})</span>
                        <span class="px-2 py-0.5 rounded bg-rose-200 text-rose-900 font-bold">${escapeHtml(c.slotStart)} – ${escapeHtml(c.slotEnd)}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-1 text-slate-700">
                        <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Date</span><strong class="text-slate-900">${escapeHtml(c.date)}</strong></div>
                        <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Activity & Sem</span><strong class="text-slate-900">${escapeHtml(c.activityType)} (${escapeHtml(c.semester)})</strong></div>
                        <div class="col-span-2"><span class="text-slate-400 block text-[10px] uppercase font-bold">Course / Exam Duty</span><strong class="text-slate-900">${escapeHtml(c.subjectOrExam)}</strong></div>
                        <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Room / Hall</span><strong class="text-slate-900">${escapeHtml(c.roomOrHall)}</strong></div>
                        <div><span class="text-slate-400 block text-[10px] uppercase font-bold">Your Attempted Slot</span><strong class="text-rose-700">${escapeHtml(conflictInfo.attemptedStart)} – ${escapeHtml(conflictInfo.attemptedEnd)}</strong></div>
                    </div>
                `;

                // 1. Auto fix button (next available slot)
                const nextFree = findNextAvailableSlot(conflictInfo.date, 60);
                if (nextFree) {
                    suggestionEl.textContent = `Next free 1-hr slot on ${conflictInfo.date}: ${nextFree.start} – ${nextFree.end}. Or choose "Save Anyway" if this is an intentional parallel lab/tutorial batch.`;
                    if (autoFixBtn) {
                        autoFixBtn.classList.remove('hidden');
                        autoFixBtn.innerHTML = '<i data-lucide="zap" class="w-3.5 h-3.5"></i> <span>Auto-Adjust Slot</span>';
                        autoFixBtn.onclick = () => {
                            document.getElementById('slot-start').value = nextFree.start;
                            document.getElementById('slot-end').value = nextFree.end;
                            checkLiveSlotConflict();
                            closeConflictModal();
                            showToast(`Updated time slot to next available window: ${nextFree.start} – ${nextFree.end}`, "info");
                        };
                    }
                } else {
                    suggestionEl.textContent = "You can edit the conflicting log, adjust times manually, or Save Anyway for parallel/multi-batch sessions.";
                }

                // 2. Allow Save Anyway (Force Save)
                if (forceSaveBtn) {
                    forceSaveBtn.classList.remove('hidden');
                    forceSaveBtn.onclick = () => {
                        closeConflictModal();
                        saveFullActivityLog(true);
                    };
                }

                // 3. Allow Edit Existing Entry
                if (editExistingBtn) {
                    editExistingBtn.classList.remove('hidden');
                    editExistingBtn.onclick = () => {
                        closeConflictModal();
                        startEditingActivityLog(c.id);
                    };
                }
            } else if (conflictInfo.reason === "OUTSIDE_CAMPUS_HOURS") {
                titleEl.textContent = "Class Outside Campus Hours";
                descEl.innerHTML = `Session slot (<strong>${escapeHtml(conflictInfo.attemptedStart)} – ${escapeHtml(conflictInfo.attemptedEnd)}</strong>) falls outside ${facultyName}'s logged campus hours (${escapeHtml(conflictInfo.arrivalStr)} – ${escapeHtml(conflictInfo.departureStr)}) on ${conflictInfo.date}.`;
                boxEl.innerHTML = `
                    <div class="text-slate-700">
                        <p class="font-bold text-slate-900 mb-1">Campus Timing Auto-Expansion</p>
                        <p class="text-xs">Your campus attendance timing can be automatically expanded to fit this class schedule.</p>
                    </div>
                `;
                suggestionEl.textContent = 'Click "Auto-Expand Hours & Save" to update campus timings and save this log.';

                if (autoFixBtn) {
                    autoFixBtn.classList.remove('hidden');
                    autoFixBtn.innerHTML = '<i data-lucide="clock" class="w-3.5 h-3.5"></i> <span>Auto-Expand Hours & Save</span>';
                    autoFixBtn.onclick = async () => {
                        const newStartMins = timeToMinutes(conflictInfo.attemptedStart);
                        const newEndMins = timeToMinutes(conflictInfo.attemptedEnd);
                        let arrMins = timeToMinutes(conflictInfo.arrivalStr);
                        let depMins = timeToMinutes(conflictInfo.departureStr);

                        if (newStartMins < arrMins) arrMins = newStartMins;
                        if (newEndMins > depMins) depMins = newEndMins;

                        const newArr = minutesToTime(arrMins);
                        const newDep = minutesToTime(depMins);

                        document.getElementById('arrival-time').value = newArr;
                        document.getElementById('departure-time').value = newDep;
                        updateCampusDurationDisplay();
                        closeConflictModal();
                        saveFullActivityLog(true);
                    };
                }
            } else {
                titleEl.textContent = "Schedule Validation Alert";
                descEl.textContent = conflictInfo.message || "Please verify your Start and End times before saving.";
                boxEl.innerHTML = `
                    <div class="text-rose-900 font-semibold">
                        ${escapeHtml(conflictInfo.message || "End time must be strictly greater than Start time.")}
                    </div>
                `;
                suggestionEl.textContent = "Adjust the session Start/End time or update the Campus Arrival/Departure log above.";
            }

            modal.classList.remove('hidden');
            lucide.createIcons();
        }

        function closeConflictModal() {
            document.getElementById('conflict-alert-modal').classList.add('hidden');
        }

        // --- ACTIVITY TYPE & STUDENT ATTENDANCE SHEET ---
        function setActivityType(type) {
            currentActivityType = type;

            // Highlight active button
            document.querySelectorAll('.duty-type-btn').forEach(btn => {
                if (btn.dataset.type === type) {
                    btn.className = "duty-type-btn flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-semibold transition bg-brand-50 border-brand-600 text-brand-700 shadow-sm";
                } else {
                    btn.className = "duty-type-btn flex items-center gap-2 px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition";
                }
            });

            const classFields = document.getElementById('class-fields-container');
            const examFields = document.getElementById('exam-fields-container');

            if (type === 'Exam Duty') {
                classFields.classList.add('hidden');
                examFields.classList.remove('hidden');
                toggleExamRosterMode();
            } else {
                classFields.classList.remove('hidden');
                examFields.classList.add('hidden');
                document.getElementById('student-attendance-grid').classList.remove('hidden');
                document.getElementById('manual-headcount-box').classList.add('hidden');
                updateAttendanceCounters();
            }
        }

        function toggleExamRosterMode() {
            const useRoster = document.getElementById('exam-track-roster').checked;
            const grid = document.getElementById('student-attendance-grid');
            const manualBox = document.getElementById('manual-headcount-box');

            if (currentActivityType === 'Exam Duty' && !useRoster) {
                grid.classList.add('hidden');
                manualBox.classList.remove('hidden');
                syncManualHeadcount();
            } else {
                grid.classList.remove('hidden');
                manualBox.classList.add('hidden');
                updateAttendanceCounters();
            }
        }

        function syncManualHeadcount() {
            const total = Math.max(1, parseInt(document.getElementById('manual-total-count').value) || 0);
            const present = Math.min(total, Math.max(0, parseInt(document.getElementById('manual-present-count').value) || 0));
            const absent = total - present;
            const pct = total > 0 ? Math.round((present / total) * 100) : 0;

            document.getElementById('stat-total-students').textContent = total;
            document.getElementById('stat-present-students').textContent = present;
            document.getElementById('stat-absent-students').textContent = absent;
            document.getElementById('stat-attendance-pct').textContent = `${pct}%`;
        }

        function handleSemesterChange() {
            const sem = document.getElementById('semester-select').value;
            document.getElementById('active-sem-pill').textContent = sem;

            const students = semesterRosters[sem] || [];
            currentSessionAttendance = {};
            // Default all students to Present (true)
            students.forEach(s => {
                currentSessionAttendance[s.roll] = true;
            });

            renderEntryAttendanceList();
        }

        function renderEntryAttendanceList() {
            const sem = document.getElementById('semester-select').value;
            const students = semesterRosters[sem] || [];
            const query = (document.getElementById('student-search-input').value || '').toLowerCase().trim();
            const grid = document.getElementById('student-attendance-grid');

            const filtered = students.filter(s =>
                s.name.toLowerCase().includes(query) || s.roll.toLowerCase().includes(query)
            );

            if (filtered.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full py-10 text-center text-slate-400 text-xs">
                        No matching students found in ${sem}.
                    </div>
                `;
            } else {
                grid.innerHTML = filtered.map(student => {
                    const isPresent = currentSessionAttendance[student.roll] !== false;
                    return `
                        <div onclick="toggleStudentStatus('${student.roll}')" 
                             class="student-card-transition cursor-pointer select-none flex items-center justify-between p-3 rounded-xl border ${isPresent ? 'bg-emerald-50/50 border-emerald-200 hover:bg-emerald-50' : 'bg-rose-50/60 border-rose-200 hover:bg-rose-50'}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-8 h-8 rounded-lg font-bold text-xs flex items-center justify-center shrink-0 ${isPresent ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'}">
                                    ${isPresent ? 'P' : 'A'}
                                </div>
                                <div class="truncate">
                                    <p class="text-xs sm:text-sm font-bold text-slate-800 truncate">${student.name}</p>
                                    <p class="text-[11px] font-medium text-slate-500">${student.roll}</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0 ${isPresent ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
                                ${isPresent ? 'Present' : 'Absent'}
                            </span>
                        </div>
                    `;
                }).join('');
            }

            updateAttendanceCounters();
        }

        function toggleStudentStatus(roll) {
            currentSessionAttendance[roll] = !currentSessionAttendance[roll];
            renderEntryAttendanceList();
        }

        function markAllAttendance(presentBool) {
            const sem = document.getElementById('semester-select').value;
            const students = semesterRosters[sem] || [];
            students.forEach(s => {
                currentSessionAttendance[s.roll] = presentBool;
            });
            renderEntryAttendanceList();
            showToast(`Marked all ${students.length} students in ${sem} as ${presentBool ? 'Present' : 'Absent'}`, 'info');
        }

        function updateAttendanceCounters() {
            const sem = document.getElementById('semester-select').value;
            const students = semesterRosters[sem] || [];
            const total = students.length;
            let present = 0;
            students.forEach(s => {
                if (currentSessionAttendance[s.roll] !== false) present++;
            });
            const absent = total - present;
            const pct = total > 0 ? Math.round((present / total) * 100) : 0;

            document.getElementById('stat-total-students').textContent = total;
            document.getElementById('stat-present-students').textContent = present;
            document.getElementById('stat-absent-students').textContent = absent;
            document.getElementById('stat-attendance-pct').textContent = `${pct}%`;
        }

        // --- EDITING ACTIVITY LOG WORKFLOW ---
        function startEditingActivityLog(logId) {
            const log = activityLogs.find(l => l.id === logId);
            if (!log) {
                showToast("Log record not found.", "error");
                return;
            }

            window.editingLogId = log.id;
            switchTab('entry');

            // 1. Set Date & Campus Times
            document.getElementById('log-date').value = log.date;
            document.getElementById('arrival-time').value = log.arrivalTime;
            document.getElementById('departure-time').value = log.departureTime;
            updateCampusDurationDisplay();

            // 2. Set Duty Category & Semester
            setActivityType(log.activityType);
            document.getElementById('semester-select').value = log.semester;
            document.getElementById('active-sem-pill').textContent = log.semester;

            // 3. Set Time Slots
            document.getElementById('slot-start').value = log.slotStart;
            document.getElementById('slot-end').value = log.slotEnd;

            // 4. Set Duty Specific Fields
            if (log.activityType === 'Exam Duty') {
                document.getElementById('exam-name').value = log.subjectOrExam;
                document.getElementById('exam-hall').value = log.roomOrHall;
                document.getElementById('exam-role').value = log.topicOrRole;
            } else {
                document.getElementById('subject-name').value = log.subjectOrExam;
                document.getElementById('class-room').value = log.roomOrHall;
                document.getElementById('class-topic').value = log.topicOrRole;
            }

            // 5. Remarks
            document.getElementById('entry-remarks').value = log.remarks || '';

            // 6. Restore Student Attendance marks
            currentSessionAttendance = {};
            const roster = semesterRosters[log.semester] || [];
            if (log.attendanceList && log.attendanceList.length > 0) {
                log.attendanceList.forEach(st => {
                    currentSessionAttendance[st.roll] = (st.status === 'P');
                });
                roster.forEach(st => {
                    if (currentSessionAttendance[st.roll] === undefined) {
                        currentSessionAttendance[st.roll] = true;
                    }
                });
            } else {
                roster.forEach(st => {
                    currentSessionAttendance[st.roll] = true;
                });
            }
            renderEntryAttendanceList();

            // 7. Update UI to Editing State
            const indicator = document.getElementById('editing-log-indicator');
            if (indicator) {
                indicator.classList.remove('hidden');
                document.getElementById('editing-log-code').textContent = log.id;
            }
            const cancelBtn = document.getElementById('cancel-edit-btn');
            if (cancelBtn) cancelBtn.classList.remove('hidden');

            const submitBtn = document.getElementById('save-log-submit-btn');
            const submitText = document.getElementById('save-log-btn-text');
            if (submitText) submitText.textContent = `Update Log Entry (${log.id})`;
            if (submitBtn) {
                submitBtn.className = "flex-1 py-3 px-5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white font-bold text-sm shadow-lg shadow-amber-500/25 flex items-center justify-center gap-2 transition transform active:scale-[0.99]";
            }

            checkLiveSlotConflict();
            window.scrollTo({ top: 120, behavior: 'smooth' });
            showToast(`Loaded ${log.id} (${log.subjectOrExam}) into editor. Modify details and click Update Log Entry.`, "info");
        }

        function cancelEditingActivityLog() {
            window.editingLogId = null;

            const indicator = document.getElementById('editing-log-indicator');
            if (indicator) indicator.classList.add('hidden');

            const cancelBtn = document.getElementById('cancel-edit-btn');
            if (cancelBtn) cancelBtn.classList.add('hidden');

            const submitBtn = document.getElementById('save-log-submit-btn');
            const submitText = document.getElementById('save-log-btn-text');
            if (submitText) submitText.textContent = "Save Log & Student Attendance";
            if (submitBtn) {
                submitBtn.className = "flex-1 py-3 px-5 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 text-white font-bold text-sm shadow-lg shadow-brand-500/25 flex items-center justify-center gap-2 transition transform active:scale-[0.99]";
            }

            document.getElementById('entry-remarks').value = '';
            checkLiveSlotConflict();
            showToast("Exited edit mode. Form ready for new session log.", "info");
        }

        // --- QUICK ADD STUDENT (TAB 1 MODAL) ---
        function openQuickAddStudentModal() {
            const sem = document.getElementById('semester-select').value;
            const titleEl = document.getElementById('quick-add-sem-title');
            if (titleEl) titleEl.textContent = sem;
            document.getElementById('quick-add-roll').value = '';
            document.getElementById('quick-add-name').value = '';
            document.getElementById('quick-add-error').classList.add('hidden');
            document.getElementById('quick-add-student-modal').classList.remove('hidden');
            document.getElementById('quick-add-roll').focus();
            lucide.createIcons();
        }

        function closeQuickAddStudentModal() {
            document.getElementById('quick-add-student-modal').classList.add('hidden');
        }

        async function submitQuickAddStudent(event) {
            if (event) event.preventDefault();
            const sem = document.getElementById('semester-select').value;
            const roll = document.getElementById('quick-add-roll').value.trim().toUpperCase();
            const name = document.getElementById('quick-add-name').value.trim();
            const errorBox = document.getElementById('quick-add-error');
            const submitBtn = document.getElementById('quick-add-submit-btn');

            if (!roll || !name) {
                errorBox.textContent = "Please enter both Roll Number and Full Name.";
                errorBox.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            try {
                const resp = await fetch('api.php?action=add_roster_student', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        semester: sem,
                        roll_no: roll,
                        student_name: name
                    })
                });
                const res = await resp.json();
                if (res.success) {
                    if (!semesterRosters[sem]) semesterRosters[sem] = [];
                    semesterRosters[sem].push({ roll, name });
                    currentSessionAttendance[roll] = true;

                    renderEntryAttendanceList();
                    renderRosterManager();
                    closeQuickAddStudentModal();
                    showToast(`Student ${name} (${roll}) added to ${sem} roster & marked Present!`, "success");
                } else {
                    errorBox.textContent = res.error || "Failed to add student.";
                    errorBox.classList.remove('hidden');
                }
            } catch (e) {
                console.error("Error adding student:", e);
                errorBox.textContent = "Server error while adding student.";
                errorBox.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
            }
        }

        // --- SAVE OR UPDATE ACTIVITY LOG ENTRY ---
        async function saveFullActivityLog(forceSave = false) {
            const dateVal = document.getElementById('log-date').value;
            const arrivalTime = document.getElementById('arrival-time').value;
            const departureTime = document.getElementById('departure-time').value;
            const semester = document.getElementById('semester-select').value;
            const slotStart = document.getElementById('slot-start').value;
            const slotEnd = document.getElementById('slot-end').value;
            const remarks = document.getElementById('entry-remarks').value.trim();

            if (!dateVal || !arrivalTime || !departureTime || !slotStart || !slotEnd) {
                showToast("Please fill in Date, Arrival, Departure, and Slot Start/End times.", "error");
                return;
            }

            // Overlap validation check (skipped if forceSave is true)
            if (!forceSave) {
                const validation = validateSessionTimeSlot(dateVal, slotStart, slotEnd, arrivalTime, departureTime);
                if (!validation.valid) {
                    openConflictModal({
                        ...validation,
                        date: dateVal,
                        attemptedStart: slotStart,
                        attemptedEnd: slotEnd,
                        arrivalStr: arrivalTime,
                        departureStr: departureTime
                    });
                    return;
                }
            }

            let subjectOrExam = "";
            let roomOrHall = "";
            let topicOrRole = "";

            if (currentActivityType === 'Exam Duty') {
                subjectOrExam = document.getElementById('exam-name').value.trim();
                roomOrHall = document.getElementById('exam-hall').value.trim() || "Exam Hall";
                topicOrRole = document.getElementById('exam-role').value;
                if (!subjectOrExam) {
                    showToast("Please enter the Examination Name & Paper Code.", "error");
                    return;
                }
            } else {
                subjectOrExam = document.getElementById('subject-name').value.trim();
                roomOrHall = document.getElementById('class-room').value.trim() || "Physics Classroom";
                topicOrRole = document.getElementById('class-topic').value.trim() || "Regular Physics Session";
                if (!subjectOrExam) {
                    showToast("Please enter the Physics Paper / Course Name.", "error");
                    return;
                }
            }

            // Build student attendance snapshot
            let attendanceList = [];
            let totalStudents = 0;
            let presentCount = 0;
            let absentCount = 0;

            const useRoster = currentActivityType !== 'Exam Duty' || document.getElementById('exam-track-roster').checked;

            if (useRoster) {
                const students = semesterRosters[semester] || [];
                attendanceList = students.map(s => {
                    const isP = currentSessionAttendance[s.roll] !== false;
                    if (isP) presentCount++;
                    return {
                        roll: s.roll,
                        name: s.name,
                        status: isP ? 'P' : 'A'
                    };
                });
                totalStudents = students.length;
                absentCount = totalStudents - presentCount;
            } else {
                totalStudents = Math.max(1, parseInt(document.getElementById('manual-total-count').value) || 0);
                presentCount = Math.min(totalStudents, Math.max(0, parseInt(document.getElementById('manual-present-count').value) || 0));
                absentCount = totalStudents - presentCount;
                attendanceList = [];
            }

            const targetCode = window.editingLogId ? window.editingLogId : ("LOG-" + Date.now().toString().slice(-5));

            const payload = {
                log_code: targetCode,
                log_date: dateVal,
                arrival_time: arrivalTime,
                departure_time: departureTime,
                activity_type: currentActivityType,
                semester: semester,
                slot_start: slotStart,
                slot_end: slotEnd,
                subject_or_exam: subjectOrExam,
                room_or_hall: roomOrHall,
                topic_or_duty: topicOrRole,
                remarks: remarks,
                student_count: totalStudents,
                present_count: presentCount,
                absent_count: absentCount,
                attendance_list: attendanceList
            };

            const submitBtn = document.getElementById('save-log-submit-btn');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const resp = await fetch('api.php?action=save_log', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await resp.json();
                if (res.success) {
                    dailyTimings[dateVal] = { arrival: arrivalTime, departure: departureTime };

                    if (window.editingLogId) {
                        const idx = activityLogs.findIndex(l => l.id === window.editingLogId);
                        if (idx !== -1) {
                            activityLogs[idx] = res.log;
                        } else {
                            activityLogs.unshift(res.log);
                        }
                        showToast(`Log entry ${res.log.id} successfully updated in MySQL database!`, "success");
                        cancelEditingActivityLog();
                    } else {
                        activityLogs.unshift(res.log);
                        showToast(`New log entry ${res.log.id} saved to MySQL database!`, "success");
                        document.getElementById('entry-remarks').value = '';
                    }

                    updateTotalBadge();
                    renderRecentLogsTable();
                    renderReportView();
                    checkLiveSlotConflict();
                } else {
                    showToast("Failed to save log to MySQL: " + (res.error || ''), "error");
                }
            } catch (e) {
                console.error("Error saving log:", e);
                showToast("Server error while saving log to MySQL.", "error");
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        }

        // --- RECENT LOGS TABLE (BOTTOM OF TAB 1) ---
        function getBadgeHtmlForType(type) {
            if (type === 'Exam Duty') {
                return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200"><i data-lucide="shield-check" class="w-3 h-3"></i> Exam Duty</span>`;
            } else if (type === 'Lab Session') {
                return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200"><i data-lucide="flask-conical" class="w-3 h-3"></i> Lab Class</span>`;
            } else if (type === 'Tutorial / Extra') {
                return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200"><i data-lucide="sparkles" class="w-3 h-3"></i> Tutorial</span>`;
            }
            return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"><i data-lucide="presentation" class="w-3 h-3"></i> Lecture</span>`;
        }

        function renderRecentLogsTable() {
            const tbody = document.getElementById('recent-logs-tbody');
            const sorted = [...activityLogs].sort((a, b) => (b.date + b.slotStart).localeCompare(a.date + a.slotStart));
            const recent = sorted.slice(0, 5);

            if (recent.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-slate-400">No logs recorded yet. Use the form above to add your first entry.</td></tr>`;
                return;
            }

            tbody.innerHTML = recent.map(log => {
                const pct = log.totalStudents > 0 ? Math.round((log.presentCount / log.totalStudents) * 100) : 0;
                return `
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-3">
                            <p class="font-bold text-slate-800">${log.date}</p>
                            <p class="text-[11px] text-slate-500">In: ${log.arrivalTime} • Out: ${log.departureTime}</p>
                        </td>
                        <td class="py-3 px-3">${getBadgeHtmlForType(log.activityType)}</td>
                        <td class="py-3 px-3">
                            <span class="font-bold text-slate-800">${log.semester}</span>
                            <p class="text-[11px] text-slate-500">${log.slotStart} - ${log.slotEnd}</p>
                        </td>
                        <td class="py-3 px-3">
                            <p class="font-bold text-slate-800">${log.subjectOrExam}</p>
                            <p class="text-[11px] text-slate-500">${log.roomOrHall} • ${log.topicOrRole}</p>
                        </td>
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-emerald-700">${log.presentCount}/${log.totalStudents}</span>
                                <span class="text-[11px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold">${pct}%</span>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="startEditingActivityLog('${log.id}')" title="Edit Log & Attendance" class="p-1.5 rounded-lg text-slate-500 hover:text-brand-600 hover:bg-brand-50 transition">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <button onclick="openStudentModal('${log.id}')" title="View Students" class="px-2 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700 font-semibold text-xs transition">
                                    Students
                                </button>
                                <button onclick="deleteActivityLog('${log.id}')" title="Delete Log (Password Protected)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            lucide.createIcons();
        }

        // --- REPORTS & FILTERING ENGINE (TAB 2) ---
        function handleFilterModeChange() {
            const mode = document.getElementById('filter-date-mode').value;
            const fromWrapper = document.getElementById('filter-from-wrapper');
            const toWrapper = document.getElementById('filter-to-wrapper');
            const fromLabel = document.getElementById('filter-from-label');

            if (mode === 'single') {
                fromWrapper.classList.remove('hidden');
                toWrapper.classList.add('hidden');
                fromLabel.textContent = 'Select Date';
            } else if (mode === 'range') {
                fromWrapper.classList.remove('hidden');
                toWrapper.classList.remove('hidden');
                fromLabel.textContent = 'From Date';
            } else {
                fromWrapper.classList.add('hidden');
                toWrapper.classList.add('hidden');
            }
            renderReportView();
        }

        function setQuickDatePreset(preset) {
            const today = getRelativeDateStr(0);
            if (preset === 'today') {
                document.getElementById('filter-date-mode').value = 'single';
                document.getElementById('filter-from-date').value = today;
            } else {
                document.getElementById('filter-date-mode').value = 'all';
                document.getElementById('filter-semester').value = 'ALL';
                document.getElementById('filter-type').value = 'ALL';
                document.getElementById('report-search').value = '';
            }
            handleFilterModeChange();
        }

        function getFilteredLogs() {
            const mode = document.getElementById('filter-date-mode').value;
            const fromDate = document.getElementById('filter-from-date').value;
            const toDate = document.getElementById('filter-to-date').value;
            const semFilter = document.getElementById('filter-semester').value;
            const typeFilter = document.getElementById('filter-type').value;
            const search = (document.getElementById('report-search').value || '').toLowerCase().trim();

            return activityLogs.filter(log => {
                // Date check
                if (mode === 'single' && fromDate) {
                    if (log.date !== fromDate) return false;
                } else if (mode === 'range') {
                    if (fromDate && log.date < fromDate) return false;
                    if (toDate && log.date > toDate) return false;
                }

                // Semester check
                if (semFilter !== 'ALL' && log.semester !== semFilter) return false;

                // Activity Type check
                if (typeFilter === 'CLASSES_ONLY') {
                    if (log.activityType === 'Exam Duty') return false;
                } else if (typeFilter !== 'ALL') {
                    if (log.activityType !== typeFilter) return false;
                }

                // Search keyword check (matches subject, room, topic, or even a student's name!)
                if (search) {
                    const hay = `${log.subjectOrExam} ${log.roomOrHall} ${log.topicOrRole} ${log.remarks} ${log.semester}`.toLowerCase();
                    const studentMatch = (log.attendanceList || []).some(s =>
                        s.name.toLowerCase().includes(search) || s.roll.toLowerCase().includes(search)
                    );
                    if (!hay.includes(search) && !studentMatch) return false;
                }

                return true;
            }).sort((a, b) => (b.date + b.slotStart).localeCompare(a.date + a.slotStart));
        }

        function renderReportView() {
            const filtered = getFilteredLogs();
            const tbody = document.getElementById('report-table-body');

            // Compute KPIs
            const uniqueDates = {};
            let classesCount = 0;
            let examDutiesCount = 0;
            let sumPresent = 0;
            let sumTotal = 0;

            filtered.forEach(log => {
                uniqueDates[log.date] = { arrival: log.arrivalTime, departure: log.departureTime };
                if (log.activityType === 'Exam Duty') {
                    examDutiesCount++;
                } else {
                    classesCount++;
                }
                sumPresent += log.presentCount;
                sumTotal += log.totalStudents;
            });

            const uniqueDayKeys = Object.keys(uniqueDates);
            let totalCampusMinutes = 0;
            uniqueDayKeys.forEach(d => {
                totalCampusMinutes += calculateDurationMinutes(uniqueDates[d].arrival, uniqueDates[d].departure);
            });

            const avgAtt = sumTotal > 0 ? Math.round((sumPresent / sumTotal) * 100) : 0;

            document.getElementById('kpi-days').textContent = uniqueDayKeys.length;
            document.getElementById('kpi-hours-sub').textContent = `${formatMinutesToHrs(totalCampusMinutes)} total on campus`;
            document.getElementById('kpi-classes').textContent = classesCount;
            document.getElementById('kpi-exams').textContent = examDutiesCount;
            document.getElementById('kpi-avg-att').textContent = `${avgAtt}%`;
            document.getElementById('kpi-att-counts').textContent = `${sumPresent} present / ${sumTotal} total`;
            document.getElementById('report-count-subtitle').textContent = `Showing ${filtered.length} matching activity record(s) across ${uniqueDayKeys.length} day(s)`;

            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <p class="font-semibold text-sm text-slate-600">No logs match your current date range or filter criteria</p>
                            <p class="text-xs mt-1">Try clicking "Show All" or expanding the From/To date range above.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = filtered.map(log => {
                const pct = log.totalStudents > 0 ? Math.round((log.presentCount / log.totalStudents) * 100) : 0;
                const campusMins = calculateDurationMinutes(log.arrivalTime, log.departureTime);
                return `
                    <tr class="hover:bg-slate-50/90 transition">
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900">${log.date}</span>
                            <span class="block text-[11px] text-slate-400">ID: ${log.id}</span>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="text-xs font-semibold text-slate-800">
                                <span class="text-emerald-700">In: ${log.arrivalTime}</span> • <span class="text-rose-700">Out: ${log.departureTime}</span>
                            </div>
                            <span class="text-[11px] text-slate-500">${formatMinutesToHrs(campusMins)} on campus</span>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            ${getBadgeHtmlForType(log.activityType)}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-bold text-xs">${log.semester}</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">${log.slotStart} - ${log.slotEnd}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <p class="font-bold text-slate-900">${log.subjectOrExam}</p>
                            <p class="text-xs text-slate-500">${log.roomOrHall} — <span class="italic">${log.topicOrRole}</span></p>
                            ${log.remarks ? `<p class="text-[11px] text-slate-400 mt-0.5">Note: ${log.remarks}</p>` : ''}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div>
                                    <span class="font-extrabold text-slate-900">${log.presentCount}</span>
                                    <span class="text-slate-400">/ ${log.totalStudents}</span>
                                    <span class="ml-1 text-xs font-bold ${pct >= 75 ? 'text-emerald-600' : 'text-amber-600'}">(${pct}%)</span>
                                </div>
                            </div>
                            <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden mt-1">
                                <div class="h-full ${pct >= 75 ? 'bg-emerald-500' : 'bg-amber-500'}" style="width: ${pct}%"></div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <button onclick="startEditingActivityLog('${log.id}')" title="Edit Log Entry" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-brand-50 text-slate-700 hover:text-brand-700 font-semibold text-xs flex items-center gap-1 transition">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    <span>Edit</span>
                                </button>
                                <button onclick="openStudentModal('${log.id}')" class="px-2.5 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700 font-semibold text-xs flex items-center gap-1 transition">
                                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                    <span>Students</span>
                                </button>
                                <button onclick="deleteActivityLog('${log.id}')" title="Delete Log (Password Protected)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            lucide.createIcons();
        }

        function deleteActivityLog(logId) {
            const log = activityLogs.find(l => l.id === logId);
            const meta = log ? `${log.date} (${log.slotStart} - ${log.slotEnd}) • ${log.subjectOrExam}` : `Log Code: ${logId}`;
            openDeletePasswordModal({
                type: 'log',
                id: logId,
                title: `Delete Log Entry (${logId})`,
                desc: 'Security Check: Please enter your faculty account password to permanently delete this log from the MySQL database.',
                meta: meta
            });
        }

        // --- STUDENT BREAKDOWN MODAL ---
        function openStudentModal(logId) {
            const log = activityLogs.find(l => l.id === logId);
            if (!log) return;
            activeModalLog = log;
            activeModalFilter = 'ALL';

            document.getElementById('modal-badge-type').textContent = log.activityType;
            document.getElementById('modal-title').textContent = log.subjectOrExam;
            document.getElementById('modal-subtitle').textContent = `Date: ${log.date} | ${log.semester} | Time Slot: ${log.slotStart} - ${log.slotEnd} | Room: ${log.roomOrHall}`;

            const pct = log.totalStudents > 0 ? Math.round((log.presentCount / log.totalStudents) * 100) : 0;
            document.getElementById('modal-meta-bar').innerHTML = `
                <div>
                    <span class="text-slate-400 block">Campus Arrival / Out</span>
                    <span class="font-bold text-slate-800">${log.arrivalTime} — ${log.departureTime}</span>
                </div>
                <div>
                    <span class="text-slate-400 block">Topic / Duty Role</span>
                    <span class="font-bold text-slate-800">${log.topicOrRole}</span>
                </div>
                <div>
                    <span class="text-slate-400 block">Present / Total</span>
                    <span class="font-bold text-emerald-700">${log.presentCount} Present</span> / <span class="font-bold text-rose-600">${log.absentCount} Absent</span>
                </div>
                <div>
                    <span class="text-slate-400 block">Attendance Rate</span>
                    <span class="font-extrabold text-brand-600">${pct}% (${log.totalStudents} Students)</span>
                </div>
            `;

            document.getElementById('modal-single-pdf-btn').onclick = () => exportSingleSessionPDF(log);

            filterModalStudents('ALL');
            document.getElementById('student-detail-modal').classList.remove('hidden');
            lucide.createIcons();
        }

        function filterModalStudents(filterType) {
            activeModalFilter = filterType;
            if (!activeModalLog) return;

            const listContainer = document.getElementById('modal-student-list');
            const list = activeModalLog.attendanceList || [];

            if (list.length === 0) {
                listContainer.innerHTML = `
                    <div class="col-span-full py-8 text-center text-slate-500 text-xs bg-slate-50 rounded-xl border border-slate-200">
                        Manual Headcount Recorded: <strong>${activeModalLog.presentCount} Present</strong> out of <strong>${activeModalLog.totalStudents} Total Candidates</strong>.
                    </div>
                `;
                return;
            }

            const filtered = list.filter(s => filterType === 'ALL' ? true : s.status === filterType);

            listContainer.innerHTML = filtered.map(s => {
                const isP = s.status === 'P';
                return `
                    <div class="flex items-center justify-between p-2.5 rounded-xl border ${isP ? 'bg-emerald-50/40 border-emerald-200' : 'bg-rose-50/50 border-rose-200'}">
                        <div class="flex items-center gap-2.5">
                            <span class="w-6 h-6 rounded-md text-xs font-bold flex items-center justify-center ${isP ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'}">${s.status}</span>
                            <div>
                                <p class="text-xs font-bold text-slate-800">${s.name}</p>
                                <p class="text-[10px] text-slate-500">${s.roll}</p>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full ${isP ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
                            ${isP ? 'Present' : 'Absent'}
                        </span>
                    </div>
                `;
            }).join('');
        }

        function closeStudentModal() {
            document.getElementById('student-detail-modal').classList.add('hidden');
            activeModalLog = null;
        }

        // --- ROSTER MANAGER (TAB 3) ---
        function renderRosterManager() {
            const sem = document.getElementById('roster-sem-select').value;
            const list = semesterRosters[sem] || [];
            document.getElementById('roster-list-heading').textContent = `Enrolled Students in ${sem}`;
            document.getElementById('roster-count-badge').textContent = `${list.length} Students`;

            const container = document.getElementById('roster-manager-list');
            if (list.length === 0) {
                container.innerHTML = `<div class="col-span-full py-8 text-center text-slate-400 text-xs">No students enrolled in ${sem} yet. Use the form on the left to add students.</div>`;
                return;
            }

            container.innerHTML = list.map((s, idx) => `
                <div class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition shadow-xs">
                    <div class="min-w-0 pr-2">
                        <p class="text-xs sm:text-sm font-bold text-slate-800 truncate">${escapeHtml(s.name)}</p>
                        <p class="text-[11px] font-semibold text-slate-500 font-mono">${escapeHtml(s.roll)}</p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" onclick="openEditStudentModal('${sem}', '${escapeJs(s.roll)}', '${escapeJs(s.name)}')" class="p-1.5 text-slate-400 hover:text-brand-600 rounded-lg hover:bg-brand-50 transition" title="Edit Student">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button type="button" onclick="removeStudentFromRoster('${sem}', ${idx})" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Remove Student (Password Protected)">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        }

        async function addStudentToRoster() {
            const sem = document.getElementById('roster-sem-select').value;
            const rollInput = document.getElementById('new-student-roll');
            const nameInput = document.getElementById('new-student-name');
            const roll = rollInput.value.trim().toUpperCase();
            const name = nameInput.value.trim();

            if (!roll || !name) {
                showToast("Please enter both Roll Number and Student Full Name.", "error");
                return;
            }

            try {
                const resp = await fetch('api.php?action=add_roster_student', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        semester: sem,
                        roll_no: roll,
                        student_name: name
                    })
                });
                const res = await resp.json();
                if (res.success) {
                    if (!semesterRosters[sem]) semesterRosters[sem] = [];
                    semesterRosters[sem].push({ roll, name });
                    rollInput.value = '';
                    nameInput.value = '';
                    renderRosterManager();
                    handleSemesterChange();
                    showToast(`Student ${name} (${roll}) added to ${sem} roster in MySQL!`, "success");
                    rollInput.focus();
                } else {
                    showToast("Cannot add student: " + (res.error || 'Server error'), "error");
                }
            } catch (e) {
                console.error("Error adding student to SQL:", e);
                showToast("Server error while saving student to SQL.", "error");
            }
        }

        function openEditStudentModal(sem, roll, name) {
            document.getElementById('edit-student-sem').value = sem;
            document.getElementById('edit-student-old-roll').value = roll;
            document.getElementById('edit-student-new-roll').value = roll;
            document.getElementById('edit-student-new-name').value = name;
            document.getElementById('edit-student-modal-title').textContent = `Edit Student in ${sem}`;
            document.getElementById('edit-student-modal-subtitle').textContent = `Currently: ${name} (${roll})`;
            document.getElementById('edit-student-error').classList.add('hidden');
            document.getElementById('edit-student-modal').classList.remove('hidden');
            document.getElementById('edit-student-new-name').focus();
            lucide.createIcons();
        }

        function closeEditStudentModal() {
            document.getElementById('edit-student-modal').classList.add('hidden');
        }

        async function submitEditStudent(event) {
            if (event) event.preventDefault();
            const sem = document.getElementById('edit-student-sem').value;
            const oldRoll = document.getElementById('edit-student-old-roll').value.trim();
            const newRoll = document.getElementById('edit-student-new-roll').value.trim().toUpperCase();
            const newName = document.getElementById('edit-student-new-name').value.trim();
            const errorBox = document.getElementById('edit-student-error');
            const submitBtn = document.getElementById('edit-student-submit-btn');

            if (!sem || !oldRoll || !newRoll || !newName) {
                errorBox.textContent = "Please fill in both Roll Number and Full Name.";
                errorBox.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            try {
                const resp = await fetch('api.php?action=edit_roster_student', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        semester: sem,
                        old_roll_no: oldRoll,
                        new_roll_no: newRoll,
                        new_student_name: newName
                    })
                });
                const res = await resp.json();
                if (res.success) {
                    const list = semesterRosters[sem] || [];
                    const item = list.find(s => s.roll.toLowerCase().trim() === oldRoll.toLowerCase().trim());
                    if (item) {
                        item.roll = newRoll;
                        item.name = newName;
                    }

                    if (currentSessionAttendance[oldRoll] !== undefined) {
                        const prevStatus = currentSessionAttendance[oldRoll];
                        delete currentSessionAttendance[oldRoll];
                        currentSessionAttendance[newRoll] = prevStatus;
                    }

                    renderRosterManager();
                    renderEntryAttendanceList();
                    closeEditStudentModal();
                    showToast(`Student details updated to ${newName} (${newRoll}) in MySQL!`, "success");
                } else {
                    errorBox.textContent = res.error || "Failed to update student details.";
                    errorBox.classList.remove('hidden');
                }
            } catch (e) {
                console.error("Error editing student:", e);
                errorBox.textContent = "Server error while saving changes.";
                errorBox.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
            }
        }

        function removeStudentFromRoster(sem, index) {
            const student = (semesterRosters[sem] || [])[index];
            if (!student) return;
            openDeletePasswordModal({
                type: 'student',
                sem: sem,
                roll: student.roll,
                title: `Remove Student from ${sem}`,
                desc: `Security Check: Enter your faculty account password to confirm removing ${student.name} (${student.roll}) from the roster.`,
                meta: `Roll: ${student.roll} | Student: ${student.name} | Semester: ${sem}`
            });
        }

        // --- EXCEL (.XLSX) MULTI-SHEET EXPORT ---
        function exportReportToExcel() {
            const filtered = getFilteredLogs();
            if (filtered.length === 0) {
                showToast("No logs in the current filter to export!", "error");
                return;
            }

            try {
                const wb = XLSX.utils.book_new();

                // Sheet 1: Daily Activity & Attendance Summary
                const summaryRows = filtered.map((log, index) => {
                    const pct = log.totalStudents > 0 ? Math.round((log.presentCount / log.totalStudents) * 100) : 0;
                    const campusMins = calculateDurationMinutes(log.arrivalTime, log.departureTime);
                    return {
                        "SL No.": index + 1,
                        "Faculty Name": FACULTY_PROFILE.name,
                        "Department & Institution": FACULTY_PROFILE.designation,
                        "Log ID": log.id,
                        "Date": log.date,
                        "Arrival Time": log.arrivalTime,
                        "Departure Time": log.departureTime,
                        "Campus Duration": formatMinutesToHrs(campusMins),
                        "Activity / Duty Type": log.activityType,
                        "Semester": log.semester,
                        "Slot Start": log.slotStart,
                        "Slot End": log.slotEnd,
                        "Physics Paper / Exam Name": log.subjectOrExam,
                        "Room / Exam Hall": log.roomOrHall,
                        "Topic / Invigilation Role": log.topicOrRole,
                        "Total Students": log.totalStudents,
                        "Students Present": log.presentCount,
                        "Students Absent": log.absentCount,
                        "Attendance %": `${pct}%`,
                        "Remarks": log.remarks || "-"
                    };
                });

                const wsSummary = XLSX.utils.json_to_sheet(summaryRows);
                XLSX.utils.book_append_sheet(wb, wsSummary, "Summary Log Report");

                // Sheet 2: Each Student's Attendance Breakdown
                const studentDetailRows = [];
                filtered.forEach(log => {
                    if (log.attendanceList && log.attendanceList.length > 0) {
                        log.attendanceList.forEach(st => {
                            studentDetailRows.push({
                                "Faculty": FACULTY_PROFILE.name,
                                "Department": FACULTY_PROFILE.designation,
                                "Date": log.date,
                                "Log ID": log.id,
                                "Activity Type": log.activityType,
                                "Semester": log.semester,
                                "Time Slot": `${log.slotStart} - ${log.slotEnd}`,
                                "Physics Course / Exam": log.subjectOrExam,
                                "Roll Number": st.roll,
                                "Student Name": st.name,
                                "Attendance Status": st.status === 'P' ? 'Present' : 'Absent'
                            });
                        });
                    }
                });

                if (studentDetailRows.length > 0) {
                    const wsStudents = XLSX.utils.json_to_sheet(studentDetailRows);
                    XLSX.utils.book_append_sheet(wb, wsStudents, "Student-Wise Attendance");
                }

                const safeFacName = (FACULTY_PROFILE.name || 'Faculty').replace(/[^a-zA-Z0-9_-]/g, '_');
                const fileName = `${safeFacName}_Log_Report_${getRelativeDateStr(0)}.xlsx`;
                XLSX.writeFile(wb, fileName);
                showToast(`Excel report exported (${fileName})!`, "success");
            } catch (err) {
                console.error(err);
                showToast("Failed to generate Excel file.", "error");
            }
        }

        // --- OFFICIAL PDF REPORT EXPORT ---
        function exportReportToPDF() {
            const filtered = getFilteredLogs();
            if (filtered.length === 0) {
                showToast("No logs in the current filter to export!", "error");
                return;
            }

            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('landscape', 'pt', 'a4');

                // Header Banner
                doc.setFillColor(30, 41, 59);
                doc.rect(0, 0, doc.internal.pageSize.getWidth(), 74, 'F');

                doc.setTextColor(255, 255, 255);
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                const deptTitle = (FACULTY_PROFILE.designation || 'FACULTY ATTENDANCE & LOG PORTAL').toUpperCase();
                doc.text(`${deptTitle} — DAILY ACTIVITY & ATTENDANCE REPORT`, 40, 30);

                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text(`Faculty: ${FACULTY_PROFILE.name}   |   Designation: ${FACULTY_PROFILE.designation}   |   Generated: ${new Date().toLocaleString()}`, 40, 52);

                // Summary Table
                const tableBody = filtered.map((log, i) => {
                    const pct = log.totalStudents > 0 ? Math.round((log.presentCount / log.totalStudents) * 100) : 0;
                    const presentNames = (log.attendanceList || [])
                        .filter(s => s.status === 'A')
                        .map(s => `${s.name} (${s.roll.split('/').pop()})`)
                        .join(', ');

                    return [
                        i + 1,
                        `${log.date}\nIn: ${log.arrivalTime}\nOut: ${log.departureTime}`,
                        log.activityType,
                        `${log.semester}\n(${log.slotStart}-${log.slotEnd})`,
                        `${log.subjectOrExam}\n[${log.roomOrHall} - ${log.topicOrRole}]`,
                        `${log.presentCount} / ${log.totalStudents}\n(${pct}%)`,
                        presentNames ? `Absent: ${presentNames}` : 'All Present'
                    ];
                });

                doc.autoTable({
                    startY: 90,
                    head: [['#', 'Date & Timing', 'Duty Type', 'Sem & Slot', 'Physics Course / Exam Details', 'Attendance', 'Absentees / Roll-Call Summary']],
                    body: tableBody,
                    theme: 'grid',
                    headStyles: { fillColor: [79, 70, 229], textColor: 255, fontSize: 9, fontStyle: 'bold' },
                    bodyStyles: { fontSize: 8.5, cellPadding: 6, valign: 'middle' },
                    columnStyles: {
                        0: { cellWidth: 28 },
                        1: { cellWidth: 85 },
                        2: { cellWidth: 75 },
                        3: { cellWidth: 75 },
                        4: { cellWidth: 210 },
                        5: { cellWidth: 65 },
                        6: { cellWidth: 'auto' }
                    }
                });

                const safeFacName = (FACULTY_PROFILE.name || 'Faculty').replace(/[^a-zA-Z0-9_-]/g, '_');
                const fileName = `${safeFacName}_Report_${getRelativeDateStr(0)}.pdf`;
                doc.save(fileName);
                showToast(`Official PDF report downloaded (${fileName})!`, "success");
            } catch (err) {
                console.error(err);
                showToast("Error generating PDF report.", "error");
            }
        }

        // Single Session Detailed Student Roll-Call PDF
        function exportSingleSessionPDF(log) {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('portrait', 'pt', 'a4');

                doc.setFillColor(79, 70, 229);
                doc.rect(0, 0, doc.internal.pageSize.getWidth(), 82, 'F');

                doc.setTextColor(255, 255, 255);
                doc.setFontSize(13.5);
                doc.setFont('helvetica', 'bold');
                doc.text(`${log.activityType.toUpperCase()} — STUDENT ATTENDANCE SHEET`, 40, 26);

                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text(`Faculty: ${FACULTY_PROFILE.name} (${FACULTY_PROFILE.designation})`, 40, 43);
                doc.text(`${log.subjectOrExam}  |  ${log.semester}  |  Date: ${log.date} (${log.slotStart} - ${log.slotEnd})`, 40, 58);
                doc.text(`Campus Arrival: ${log.arrivalTime}  |  Departure: ${log.departureTime}  |  Present: ${log.presentCount}/${log.totalStudents}`, 40, 72);

                const rows = (log.attendanceList || []).map((st, idx) => [
                    idx + 1,
                    st.roll,
                    st.name,
                    st.status === 'P' ? 'PRESENT' : 'ABSENT'
                ]);

                doc.autoTable({
                    startY: 98,
                    head: [['SL', 'Roll Number', 'Student Full Name', 'Attendance Status']],
                    body: rows,
                    theme: 'striped',
                    headStyles: { fillColor: [30, 41, 59], textColor: 255, fontSize: 9.5 },
                    bodyStyles: { fontSize: 9, cellPadding: 6 },
                    didParseCell: function (data) {
                        if (data.section === 'body' && data.column.index === 3) {
                            if (data.cell.raw === 'ABSENT') {
                                data.cell.styles.textColor = [225, 29, 72];
                                data.cell.styles.fontStyle = 'bold';
                            } else {
                                data.cell.styles.textColor = [5, 150, 105];
                                data.cell.styles.fontStyle = 'bold';
                            }
                        }
                    }
                });

                const safeSubj = (log.subjectOrExam || 'Attendance').replace(/[^a-zA-Z0-9_-]/g, '_').slice(0, 25);
                doc.save(`${safeSubj}_${log.semester}_${log.date}.pdf`);
                showToast("Session attendance sheet downloaded as PDF!", "success");
            } catch (e) {
                console.error(e);
                showToast("Could not export session PDF.", "error");
            }
        }

        // --- UI UTILITIES ---
        function updateTotalBadge() {
            const badge = document.getElementById('badge-total-logs');
            if (badge) badge.textContent = activityLogs.length;
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');

            let colors = 'bg-slate-900 text-white border-slate-700';
            let icon = 'check-circle-2';
            if (type === 'error') {
                colors = 'bg-rose-600 text-white border-rose-500';
                icon = 'alert-circle';
            } else if (type === 'info') {
                colors = 'bg-indigo-600 text-white border-indigo-500';
                icon = 'info';
            }

            toast.className = `pointer-events-auto flex items-center gap-2.5 px-4 py-3 rounded-xl border shadow-lg text-xs sm:text-sm font-medium transition-all duration-300 ${colors}`;
            toast.innerHTML = `
                <i data-lucide="${icon}" class="w-4 h-4 shrink-0"></i>
                <span>${message}</span>
            `;

            container.appendChild(toast);
            lucide.createIcons();

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';
                setTimeout(() => toast.remove(), 300);
            }, 3400);
        }

        // --- INITIALIZE ON LOAD ---
        

        // --- INITIALIZE ON LOAD ---
        window.onload = async function () {
            initDefaultDates();
            await loadStateFromStorage();
            lucide.createIcons();
        };
    </script>
</body>
<?php endif; ?>
</html>
