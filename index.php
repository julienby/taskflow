<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

// Initial data
$todos = todoList();
$taskCountHtml = renderTaskCount();
?>
<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Taskflow</title>

    <!-- Dark mode: prevent flash -->
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <!-- Tailwind v4 CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@2.0.4"></script>

    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="style.css" />
</head>
<body class="min-h-screen p-4 sm:p-6 lg:p-8">

    <div class="max-w-2xl mx-auto space-y-5">

        <!-- =============================================================== -->
        <!-- Header -->
        <!-- =============================================================== -->
        <header class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Taskflow</h1>
            </div>

            <!-- Dark mode toggle -->
            <button id="dark-toggle"
                    class="p-2.5 rounded-xl bg-white/20 backdrop-blur text-white
                           hover:bg-white/30 transition-colors"
                    title="Toggle dark mode">
                <!-- Sun icon (shown in dark mode) -->
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <!-- Moon icon (shown in light mode) -->
                <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>
        </header>

        <!-- =============================================================== -->
        <!-- Add Task Card -->
        <!-- =============================================================== -->
        <div class="glass rounded-2xl p-5 shadow-lg">
            <form hx-post="api.php?action=create"
                  hx-target="#task-list"
                  hx-swap="afterbegin"
                  class="space-y-3">

                <!-- Title row -->
                <div class="flex gap-2">
                    <input id="add-title-input" name="title" type="text" required
                           placeholder="What needs to be done?"
                           class="flex-1 min-w-0 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-white/50 dark:bg-gray-800/50 text-gray-800 dark:text-gray-100
                                  placeholder-gray-400 dark:placeholder-gray-500
                                  text-sm focus:ring-2 focus:ring-indigo-300 focus:border-transparent outline-none" />
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-500 text-white text-sm font-semibold
                                   hover:bg-indigo-600 active:scale-95 transition-all shadow-md
                                   shadow-indigo-500/25">
                        <svg class="w-5 h-5 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="hidden sm:inline">Add Task</span>
                    </button>
                </div>

                <!-- Options row -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Category -->
                    <select name="category"
                            class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700
                                   bg-white/50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 outline-none">
                        <option value="personal">Personal</option>
                        <option value="work">Work</option>
                        <option value="shopping">Shopping</option>
                        <option value="health">Health</option>
                        <option value="education">Education</option>
                        <option value="finance">Finance</option>
                    </select>

                    <!-- Priority pills -->
                    <div class="flex gap-1">
                        <label class="priority-pill cursor-pointer">
                            <input type="radio" name="priority" value="low" class="sr-only" />
                            <span class="pill-low text-xs px-2.5 py-1 rounded-full border border-green-300 dark:border-green-700
                                         text-green-600 dark:text-green-400 transition-all font-medium">L</span>
                        </label>
                        <label class="priority-pill cursor-pointer">
                            <input type="radio" name="priority" value="medium" class="sr-only" checked />
                            <span class="pill-medium text-xs px-2.5 py-1 rounded-full border border-amber-300 dark:border-amber-700
                                         text-amber-600 dark:text-amber-400 transition-all font-medium
                                         bg-amber-400 !text-white shadow-sm">M</span>
                        </label>
                        <label class="priority-pill cursor-pointer">
                            <input type="radio" name="priority" value="high" class="sr-only" />
                            <span class="pill-high text-xs px-2.5 py-1 rounded-full border border-red-300 dark:border-red-700
                                         text-red-600 dark:text-red-400 transition-all font-medium">H</span>
                        </label>
                    </div>

                    <!-- Due date -->
                    <input type="date" name="due_date"
                           class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700
                                  bg-white/50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 outline-none" />
                </div>
            </form>
        </div>

        <!-- =============================================================== -->
        <!-- Filter Bar -->
        <!-- =============================================================== -->
        <div class="glass rounded-2xl p-4 shadow-lg">
            <div id="filter-bar" class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative flex-1 min-w-[180px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" placeholder="Search tasks..."
                           hx-get="api.php?action=search"
                           hx-trigger="input changed delay:300ms, search"
                           hx-target="#task-list"
                           hx-swap="innerHTML"
                           hx-include="#filter-bar"
                           hx-params="*"
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-white/50 dark:bg-gray-800/50 text-gray-800 dark:text-gray-100
                                  placeholder-gray-400 dark:placeholder-gray-500
                                  text-sm outline-none" />
                </div>

                <!-- Category filter -->
                <select name="filter_category"
                        hx-get="api.php?action=list"
                        hx-trigger="change"
                        hx-target="#task-list"
                        hx-swap="innerHTML"
                        hx-include="#filter-bar"
                        hx-params="*"
                        class="text-xs px-2.5 py-2 rounded-lg border border-gray-200 dark:border-gray-700
                               bg-white/50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 outline-none">
                    <option value="">All Categories</option>
                    <option value="work">Work</option>
                    <option value="personal">Personal</option>
                    <option value="shopping">Shopping</option>
                    <option value="health">Health</option>
                    <option value="education">Education</option>
                    <option value="finance">Finance</option>
                </select>

                <!-- Priority filter -->
                <select name="filter_priority"
                        hx-get="api.php?action=list"
                        hx-trigger="change"
                        hx-target="#task-list"
                        hx-swap="innerHTML"
                        hx-include="#filter-bar"
                        hx-params="*"
                        class="text-xs px-2.5 py-2 rounded-lg border border-gray-200 dark:border-gray-700
                               bg-white/50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 outline-none">
                    <option value="">All Priorities</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>

                <!-- Status segmented control -->
                <div id="status-segments" class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <label class="status-segment cursor-pointer">
                        <input type="radio" name="filter_status" value="all" class="sr-only" checked
                               hx-get="api.php?action=list"
                               hx-trigger="change"
                               hx-target="#task-list"
                               hx-swap="innerHTML"
                               hx-include="#filter-bar" />
                        <span class="seg-label block text-xs px-3 py-1.5 font-medium transition-all active">All</span>
                    </label>
                    <label class="status-segment cursor-pointer">
                        <input type="radio" name="filter_status" value="pending" class="sr-only"
                               hx-get="api.php?action=list"
                               hx-trigger="change"
                               hx-target="#task-list"
                               hx-swap="innerHTML"
                               hx-include="#filter-bar" />
                        <span class="seg-label block text-xs px-3 py-1.5 font-medium transition-all">Active</span>
                    </label>
                    <label class="status-segment cursor-pointer">
                        <input type="radio" name="filter_status" value="completed" class="sr-only"
                               hx-get="api.php?action=list"
                               hx-trigger="change"
                               hx-target="#task-list"
                               hx-swap="innerHTML"
                               hx-include="#filter-bar" />
                        <span class="seg-label block text-xs px-3 py-1.5 font-medium transition-all">Done</span>
                    </label>
                </div>

                <!-- Task count -->
                <?= $taskCountHtml ?>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- Task List -->
        <!-- =============================================================== -->
        <div class="glass rounded-2xl p-3 shadow-lg min-h-[120px]">
            <div id="task-list" class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">
                <?php if (empty($todos)): ?>
                    <?= renderEmptyState() ?>
                <?php else: ?>
                    <?php foreach ($todos as $todo): ?>
                        <?= renderTaskRow($todo) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center text-white/40 text-xs pb-4">
            Taskflow &mdash; Built with PHP, HTMX &amp; Tailwind
        </footer>

    </div>

    <!-- ================================================================= -->
    <!-- Inline JavaScript -->
    <!-- ================================================================= -->
    <script>
    (function() {
        // -----------------------------------------------------------------
        // Dark mode toggle
        // -----------------------------------------------------------------
        const toggle = document.getElementById('dark-toggle');
        toggle.addEventListener('click', () => {
            const html = document.documentElement;
            html.classList.toggle('dark');
            localStorage.setItem('darkMode', html.classList.contains('dark'));
        });

        // -----------------------------------------------------------------
        // Status segment toggle
        // -----------------------------------------------------------------
        document.getElementById('status-segments').addEventListener('change', (e) => {
            if (e.target.name === 'filter_status') {
                document.querySelectorAll('#status-segments .seg-label').forEach(s => s.classList.remove('active'));
                e.target.nextElementSibling.classList.add('active');
            }
        });

        // -----------------------------------------------------------------
        // Priority pill visual feedback
        // -----------------------------------------------------------------
        function updatePriorityPills() {
            document.querySelectorAll('.priority-pill input[name="priority"]').forEach(input => {
                const span = input.nextElementSibling;
                if (!span) return;
                // Reset styles
                span.classList.remove('!text-white', 'shadow-sm');
                if (span.classList.contains('pill-low')) {
                    span.style.backgroundColor = '';
                    span.style.color = '';
                }
                if (span.classList.contains('pill-medium')) {
                    span.style.backgroundColor = '';
                    span.style.color = '';
                }
                if (span.classList.contains('pill-high')) {
                    span.style.backgroundColor = '';
                    span.style.color = '';
                }
                // Apply active styles
                if (input.checked) {
                    span.classList.add('shadow-sm');
                    if (span.classList.contains('pill-low')) {
                        span.style.backgroundColor = 'rgb(34, 197, 94)';
                        span.style.color = 'white';
                    } else if (span.classList.contains('pill-medium')) {
                        span.style.backgroundColor = 'rgb(245, 158, 11)';
                        span.style.color = 'white';
                    } else if (span.classList.contains('pill-high')) {
                        span.style.backgroundColor = 'rgb(239, 68, 68)';
                        span.style.color = 'white';
                    }
                }
            });
        }

        document.addEventListener('change', (e) => {
            if (e.target.name === 'priority' && e.target.closest('.priority-pill')) {
                updatePriorityPills();
            }
        });

        // Initial state
        updatePriorityPills();

        // -----------------------------------------------------------------
        // SortableJS initialization (re-init after HTMX swaps)
        // -----------------------------------------------------------------
        let sortableInstance = null;

        function initSortable() {
            const list = document.getElementById('task-list');
            if (!list) return;

            if (sortableInstance) {
                sortableInstance.destroy();
            }

            sortableInstance = new Sortable(list, {
                handle: '.drag-handle',
                animation: 200,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function() {
                    const ids = Array.from(list.querySelectorAll('[data-id]'))
                        .map(el => el.dataset.id);

                    const body = ids.map(id => 'order[]=' + encodeURIComponent(id)).join('&');

                    fetch('api.php?action=reorder', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body
                    });
                }
            });
        }

        // Init on page load
        initSortable();

        // Re-init after HTMX settles (new content)
        document.body.addEventListener('htmx:afterSettle', (e) => {
            if (e.detail.target && e.detail.target.id === 'task-list') {
                initSortable();
            }
        });

        // -----------------------------------------------------------------
        // HTMX: Map filter param names for API
        // The filter inputs use names like filter_category but the API
        // expects category, priority, status, search.
        // -----------------------------------------------------------------
        document.body.addEventListener('htmx:configRequest', (e) => {
            const params = e.detail.parameters;

            // Map filter_ prefixed params to non-prefixed
            if ('filter_category' in params) {
                params['category'] = params['filter_category'];
                delete params['filter_category'];
            }
            if ('filter_priority' in params) {
                params['priority'] = params['filter_priority'];
                delete params['filter_priority'];
            }
            if ('filter_status' in params) {
                params['status'] = params['filter_status'];
                delete params['filter_status'];
            }
        });

        // -----------------------------------------------------------------
        // Remove empty state when first task is added
        // -----------------------------------------------------------------
        document.body.addEventListener('htmx:afterSwap', (e) => {
            const list = document.getElementById('task-list');
            if (!list) return;

            // If list now has todo-rows, remove empty state placeholder
            if (list.querySelector('.todo-row')) {
                const emptyState = list.querySelector('.flex.flex-col.items-center.justify-center');
                if (emptyState) emptyState.remove();
            }
        });

    })();
    </script>

</body>
</html>
