<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Database connection & initialization
// ---------------------------------------------------------------------------

function getDb(): SQLite3 {
    static $db = null;
    if ($db !== null) {
        return $db;
    }

    $dbPath = __DIR__ . '/todo.db';
    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    // Create table
    $db->exec("
        CREATE TABLE IF NOT EXISTS todos (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT NOT NULL,
            description TEXT DEFAULT '',
            category    TEXT NOT NULL DEFAULT 'personal'
                        CHECK(category IN ('work','personal','shopping','health','education','finance')),
            priority    TEXT NOT NULL DEFAULT 'medium'
                        CHECK(priority IN ('low','medium','high')),
            status      TEXT NOT NULL DEFAULT 'pending'
                        CHECK(status IN ('pending','completed')),
            due_date    TEXT,
            sort_order  INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT NOT NULL DEFAULT (datetime('now','localtime')),
            updated_at  TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        )
    ");

    // Indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_todos_status     ON todos(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_todos_category   ON todos(category)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_todos_priority   ON todos(priority)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_todos_sort_order ON todos(sort_order)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_todos_due_date   ON todos(due_date)");

    // Auto-update trigger
    $db->exec("
        CREATE TRIGGER IF NOT EXISTS trg_todos_updated_at
        AFTER UPDATE ON todos
        FOR EACH ROW
        BEGIN
            UPDATE todos SET updated_at = datetime('now','localtime') WHERE id = OLD.id;
        END
    ");

    return $db;
}

// ---------------------------------------------------------------------------
// CRUD helpers
// ---------------------------------------------------------------------------

function todoCreate(string $title, string $category, string $priority, ?string $dueDate): array {
    $db = getDb();

    // Set sort_order to max+1
    $maxOrder = (int) $db->querySingle("SELECT COALESCE(MAX(sort_order),0) FROM todos");

    $stmt = $db->prepare("
        INSERT INTO todos (title, category, priority, due_date, sort_order)
        VALUES (:title, :category, :priority, :due_date, :sort_order)
    ");
    $stmt->bindValue(':title',      $title,          SQLITE3_TEXT);
    $stmt->bindValue(':category',   $category,       SQLITE3_TEXT);
    $stmt->bindValue(':priority',   $priority,       SQLITE3_TEXT);
    $stmt->bindValue(':due_date',   $dueDate ?: null, SQLITE3_TEXT);
    $stmt->bindValue(':sort_order', $maxOrder + 1,   SQLITE3_INTEGER);
    $stmt->execute();

    $id = $db->lastInsertRowID();
    return todoGetById((int) $id);
}

function todoGetById(int $id): ?array {
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM todos WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row ?: null;
}

function todoUpdate(int $id, array $data): ?array {
    $db = getDb();
    $sets = [];
    $params = [];
    $allowed = ['title', 'description', 'category', 'priority', 'status', 'due_date'];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $data)) {
            $sets[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    if (empty($sets)) return todoGetById($id);

    $sql = "UPDATE todos SET " . implode(', ', $sets) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, SQLITE3_TEXT);
    }
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();

    return todoGetById($id);
}

function todoToggle(int $id): ?array {
    $db = getDb();
    $db->exec("UPDATE todos SET status = CASE WHEN status='pending' THEN 'completed' ELSE 'pending' END WHERE id = $id");
    return todoGetById($id);
}

function todoDelete(int $id): bool {
    $db = getDb();
    $stmt = $db->prepare("DELETE FROM todos WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    return $db->changes() > 0;
}

function todoReorder(array $orderedIds): void {
    $db = getDb();
    $stmt = $db->prepare("UPDATE todos SET sort_order = :order WHERE id = :id");
    foreach ($orderedIds as $i => $id) {
        $stmt->bindValue(':order', $i, SQLITE3_INTEGER);
        $stmt->bindValue(':id', (int) $id, SQLITE3_INTEGER);
        $stmt->execute();
        $stmt->reset();
    }
}

function todoList(array $filters = []): array {
    $db = getDb();
    $where = [];
    $params = [];

    if (!empty($filters['search'])) {
        $where[] = "title LIKE :search";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['category'])) {
        $where[] = "category = :category";
        $params[':category'] = $filters['category'];
    }
    if (!empty($filters['priority'])) {
        $where[] = "priority = :priority";
        $params[':priority'] = $filters['priority'];
    }
    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $where[] = "status = :status";
        $params[':status'] = $filters['status'];
    }

    $sql = "SELECT * FROM todos";
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY sort_order ASC, created_at DESC";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, SQLITE3_TEXT);
    }

    $result = $stmt->execute();
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

function todoCount(array $filters = []): int {
    $db = getDb();
    $where = [];
    $params = [];

    if (!empty($filters['search'])) {
        $where[] = "title LIKE :search";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['category'])) {
        $where[] = "category = :category";
        $params[':category'] = $filters['category'];
    }
    if (!empty($filters['priority'])) {
        $where[] = "priority = :priority";
        $params[':priority'] = $filters['priority'];
    }
    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $where[] = "status = :status";
        $params[':status'] = $filters['status'];
    }

    $sql = "SELECT COUNT(*) FROM todos";
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, SQLITE3_TEXT);
    }

    return (int) $stmt->execute()->fetchArray()[0];
}

// ---------------------------------------------------------------------------
// Shared HTML render helpers
// ---------------------------------------------------------------------------

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function categoryLabel(string $cat): string {
    $labels = [
        'work'      => 'Work',
        'personal'  => 'Personal',
        'shopping'  => 'Shopping',
        'health'    => 'Health',
        'education' => 'Education',
        'finance'   => 'Finance',
    ];
    return $labels[$cat] ?? ucfirst($cat);
}

function categoryColor(string $cat): string {
    $colors = [
        'work'      => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'personal'  => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        'shopping'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'health'    => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'education' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
        'finance'   => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    ];
    return $colors[$cat] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300';
}

function priorityBorder(string $pri): string {
    $borders = [
        'low'    => 'border-l-green-400',
        'medium' => 'border-l-amber-400',
        'high'   => 'border-l-red-500',
    ];
    return $borders[$pri] ?? 'border-l-gray-400';
}

function isOverdue(?string $dueDate, string $status): bool {
    if (!$dueDate || $status === 'completed') return false;
    return $dueDate < date('Y-m-d');
}

function renderTaskRow(array $todo): string {
    $id = (int) $todo['id'];
    $completed = $todo['status'] === 'completed';
    $overdue = isOverdue($todo['due_date'], $todo['status']);
    $border = priorityBorder($todo['priority']);
    $catColor = categoryColor($todo['category']);
    $catLabel = categoryLabel($todo['category']);

    $checkedAttr = $completed ? 'checked' : '';
    $titleClass = $completed ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-100';

    $dueDateHtml = '';
    if ($todo['due_date']) {
        $formatted = date('M j', strtotime($todo['due_date']));
        $overdueClass = $overdue ? 'text-red-500 animate-pulse font-semibold' : 'text-gray-400 dark:text-gray-500';
        $dueDateHtml = '<span class="text-xs ' . $overdueClass . ' whitespace-nowrap"><svg class="w-3 h-3 inline -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' . e($formatted) . '</span>';
    }

    $priorityLabel = ucfirst($todo['priority']);
    $jsTitle = htmlspecialchars(addslashes($todo['title']), ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <div id="todo-{$id}" class="todo-row group flex items-center gap-3 px-4 py-3 border-l-4 {$border}
                bg-white/60 dark:bg-white/5 backdrop-blur-sm rounded-lg
                hover:bg-white/80 dark:hover:bg-white/10 transition-all duration-200"
         data-id="{$id}">
        <!-- Drag handle -->
        <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 dark:text-gray-600
                    hover:text-gray-500 dark:hover:text-gray-400 touch-none">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
                <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
            </svg>
        </div>

        <!-- Checkbox -->
        <label class="relative flex items-center cursor-pointer">
            <input type="checkbox" {$checkedAttr}
                   hx-post="api.php?action=toggle&id={$id}"
                   hx-target="#todo-{$id}"
                   hx-swap="outerHTML swap:150ms"
                   class="peer sr-only" />
            <div class="w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600
                        peer-checked:border-indigo-500 peer-checked:bg-indigo-500
                        flex items-center justify-center transition-all duration-200">
                <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </label>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="{$titleClass} text-sm font-medium truncate">{$todo['title']}</span>
                <span class="{$catColor} text-xs px-2 py-0.5 rounded-full font-medium">{$catLabel}</span>
                {$dueDateHtml}
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1">
            <button hx-get="api.php?action=edit_form&id={$id}"
                    hx-target="#todo-{$id}"
                    hx-swap="outerHTML"
                    class="p-1.5 rounded-md text-gray-400 hover:text-indigo-500 hover:bg-indigo-50
                           dark:hover:text-indigo-400 dark:hover:bg-indigo-900/30 transition-colors"
                    title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>
            <button onclick="openDeleteModal({$id}, '{$jsTitle}')"
                    class="p-1.5 rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50
                           dark:hover:text-red-400 dark:hover:bg-red-900/30 transition-colors"
                    title="Delete">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>
    HTML;
}

function renderEditForm(array $todo): string {
    $id = (int) $todo['id'];
    $categories = ['work','personal','shopping','health','education','finance'];
    $priorities = ['low','medium','high'];

    $catOptions = '';
    foreach ($categories as $c) {
        $sel = $todo['category'] === $c ? 'selected' : '';
        $catOptions .= '<option value="' . $c . '" ' . $sel . '>' . categoryLabel($c) . '</option>';
    }

    $priOptions = '';
    foreach ($priorities as $p) {
        $sel = $todo['priority'] === $p ? 'selected' : '';
        $priOptions .= '<option value="' . $p . '" ' . $sel . '>' . ucfirst($p) . '</option>';
    }

    $dueVal = e($todo['due_date'] ?? '');

    return <<<HTML
    <form id="todo-{$id}" class="flex flex-col gap-3 p-4 border-l-4 border-l-indigo-400
                bg-white/80 dark:bg-white/10 backdrop-blur-sm rounded-lg ring-2 ring-indigo-300/50"
          hx-post="api.php?action=update&id={$id}"
          hx-target="#todo-{$id}"
          hx-swap="outerHTML swap:150ms">

        <input type="text" name="title" value="{$todo['title']}"
               class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700
                      bg-white/50 dark:bg-gray-800/50 text-gray-800 dark:text-gray-100
                      text-sm focus:ring-2 focus:ring-indigo-300 focus:border-transparent outline-none"
               required />

        <div class="flex flex-wrap gap-2 items-center">
            <select name="category"
                    class="text-xs px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700
                           bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 outline-none">
                {$catOptions}
            </select>
            <select name="priority"
                    class="text-xs px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700
                           bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 outline-none">
                {$priOptions}
            </select>
            <input type="date" name="due_date" value="{$dueVal}"
                   class="text-xs px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700
                          bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 outline-none" />

            <div class="flex gap-1 ml-auto">
                <button type="submit"
                        class="text-xs px-3 py-1.5 rounded-lg bg-indigo-500 text-white
                               hover:bg-indigo-600 transition-colors font-medium">
                    Save
                </button>
                <button type="button"
                        hx-get="api.php?action=list"
                        hx-target="#task-list"
                        hx-swap="innerHTML"
                        class="text-xs px-3 py-1.5 rounded-lg bg-gray-200 dark:bg-gray-700
                               text-gray-600 dark:text-gray-300 hover:bg-gray-300
                               dark:hover:bg-gray-600 transition-colors font-medium">
                    Cancel
                </button>
            </div>
        </div>
    </form>
    HTML;
}

function renderTaskCount(array $filters = []): string {
    $count = todoCount($filters);
    $label = $count === 1 ? 'task' : 'tasks';
    return <<<HTML
    <span id="task-count" hx-swap-oob="true"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                 bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
        {$count} {$label}
    </span>
    HTML;
}

function renderEmptyState(): string {
    return <<<HTML
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        <p class="text-gray-400 dark:text-gray-500 text-lg font-medium mb-1">No tasks yet</p>
        <p class="text-gray-300 dark:text-gray-600 text-sm">Add your first task above to get started!</p>
    </div>
    HTML;
}
