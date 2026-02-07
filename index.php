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

            <div class="flex items-center gap-2">
            <!-- Focus mode toggle (hide add form) -->
            <button id="focus-toggle"
                    class="p-2.5 rounded-xl bg-white/20 backdrop-blur text-white
                           hover:bg-white/30 transition-colors"
                    title="Toggle focus mode">
                <!-- Eye icon (form visible) -->
                <svg id="icon-eye-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <!-- Eye-off icon (form hidden) -->
                <svg id="icon-eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
            </button>

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
            </div>
        </header>

        <!-- =============================================================== -->
        <!-- Add Task Card -->
        <!-- =============================================================== -->
        <div id="add-task-card" class="glass rounded-2xl p-5 shadow-lg transition-all duration-300">
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
                    <input type="date" name="due_date" value="<?= date('Y-m-d') ?>"
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
    <!-- Delete Confirmation Modal -->
    <!-- ================================================================= -->
    <div id="delete-modal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div id="delete-modal-backdrop"
             class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-200 opacity-0"></div>
        <!-- Dialog -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div id="delete-modal-dialog"
                 class="relative glass rounded-2xl shadow-2xl p-6 w-full max-w-sm
                        transform transition-all duration-200 scale-95 opacity-0">
                <!-- Icon -->
                <div class="mx-auto w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30
                            flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <!-- Text -->
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 text-center mb-1">Delete Task</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
                    Are you sure you want to delete<br>
                    <span id="delete-modal-title" class="font-medium text-gray-700 dark:text-gray-200"></span>?
                </p>
                <!-- Buttons -->
                <div class="flex gap-3">
                    <button id="delete-modal-cancel"
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium
                                   bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                   hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button id="delete-modal-confirm"
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium
                                   bg-red-500 text-white hover:bg-red-600
                                   active:scale-95 transition-all shadow-md shadow-red-500/25">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- Inline JavaScript -->
    <!-- ================================================================= -->
    <script>
    // -----------------------------------------------------------------
    // Delete modal (global so onclick can call it)
    // -----------------------------------------------------------------
    let deleteTargetId = null;

    function openDeleteModal(id, title) {
        deleteTargetId = id;
        const modal = document.getElementById('delete-modal');
        const backdrop = document.getElementById('delete-modal-backdrop');
        const dialog = document.getElementById('delete-modal-dialog');
        document.getElementById('delete-modal-title').textContent = title;

        modal.classList.remove('hidden');
        // Trigger animation on next frame
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            dialog.classList.remove('scale-95', 'opacity-0');
            dialog.classList.add('scale-100', 'opacity-100');
        });
    }

    function closeDeleteModal() {
        const modal = document.getElementById('delete-modal');
        const backdrop = document.getElementById('delete-modal-backdrop');
        const dialog = document.getElementById('delete-modal-dialog');

        backdrop.classList.add('opacity-0');
        dialog.classList.remove('scale-100', 'opacity-100');
        dialog.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            deleteTargetId = null;
        }, 200);
    }

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
        // Delete modal events
        // -----------------------------------------------------------------
        document.getElementById('delete-modal-cancel').addEventListener('click', closeDeleteModal);
        document.getElementById('delete-modal-backdrop').addEventListener('click', closeDeleteModal);
        document.getElementById('delete-modal-confirm').addEventListener('click', () => {
            if (!deleteTargetId) return;
            const target = document.getElementById('todo-' + deleteTargetId);
            if (target) {
                htmx.ajax('DELETE', 'api.php?action=delete&id=' + deleteTargetId, {
                    target: target,
                    swap: 'outerHTML swap:300ms'
                });
            }
            closeDeleteModal();
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && deleteTargetId) closeDeleteModal();
        });

        // -----------------------------------------------------------------
        // Focus mode toggle (hide/show add task form)
        // -----------------------------------------------------------------
        const focusBtn = document.getElementById('focus-toggle');
        const addCard = document.getElementById('add-task-card');
        const iconOpen = document.getElementById('icon-eye-open');
        const iconClosed = document.getElementById('icon-eye-closed');

        function applyFocusMode(hidden) {
            if (hidden) {
                addCard.style.maxHeight = '0';
                addCard.style.padding = '0';
                addCard.style.marginBottom = '0';
                addCard.style.overflow = 'hidden';
                addCard.style.opacity = '0';
                addCard.style.border = 'none';
                iconOpen.classList.add('hidden');
                iconClosed.classList.remove('hidden');
            } else {
                addCard.style.maxHeight = '';
                addCard.style.padding = '';
                addCard.style.marginBottom = '';
                addCard.style.overflow = '';
                addCard.style.opacity = '';
                addCard.style.border = '';
                iconOpen.classList.remove('hidden');
                iconClosed.classList.add('hidden');
            }
        }

        // Restore from localStorage
        if (localStorage.getItem('focusMode') === 'true') {
            applyFocusMode(true);
        }

        focusBtn.addEventListener('click', () => {
            const isHidden = localStorage.getItem('focusMode') === 'true';
            localStorage.setItem('focusMode', !isHidden);
            applyFocusMode(!isHidden);
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
