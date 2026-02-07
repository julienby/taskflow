<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Build current filter context from query params
function currentFilters(): array {
    return [
        'search'   => trim($_GET['search'] ?? ''),
        'category' => $_GET['category'] ?? ($_GET['filter_category'] ?? ''),
        'priority' => $_GET['priority'] ?? ($_GET['filter_priority'] ?? ''),
        'status'   => $_GET['status'] ?? ($_GET['filter_status'] ?? 'all'),
    ];
}

switch ($action) {

    // -----------------------------------------------------------------------
    // GET: list tasks (with optional filters)
    // -----------------------------------------------------------------------
    case 'list':
    case 'search':
        $filters = currentFilters();
        $todos = todoList($filters);

        if (empty($todos)) {
            echo renderEmptyState();
        } else {
            foreach ($todos as $todo) {
                echo renderTaskRow($todo);
            }
        }

        // Always include OOB count update
        echo renderTaskCount($filters);
        break;

    // -----------------------------------------------------------------------
    // GET: inline edit form
    // -----------------------------------------------------------------------
    case 'edit_form':
        $id = (int) ($_GET['id'] ?? 0);
        $todo = todoGetById($id);
        if (!$todo) {
            http_response_code(404);
            echo '<div class="text-red-500 text-sm p-2">Task not found</div>';
            break;
        }
        echo renderEditForm($todo);
        break;

    // -----------------------------------------------------------------------
    // POST: create task
    // -----------------------------------------------------------------------
    case 'create':
        if ($method !== 'POST') { http_response_code(405); break; }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            http_response_code(422);
            echo '<div class="text-red-500 text-sm p-2">Title is required</div>';
            break;
        }

        $category = $_POST['category'] ?? 'personal';
        $priority = $_POST['priority'] ?? 'medium';
        $dueDate  = $_POST['due_date'] ?? null;
        if ($dueDate === '') $dueDate = null;

        $todo = todoCreate($title, $category, $priority, $dueDate);

        // Return the new task row (will be prepended/appended to list)
        echo renderTaskRow($todo);

        // OOB: update task count
        $filters = currentFilters();
        echo renderTaskCount($filters);

        // OOB: reset the add form
        echo <<<HTML
        <input id="add-title-input" name="title" type="text" required
               placeholder="What needs to be done?"
               hx-swap-oob="true"
               class="flex-1 min-w-0 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700
                      bg-white/50 dark:bg-gray-800/50 text-gray-800 dark:text-gray-100
                      placeholder-gray-400 dark:placeholder-gray-500
                      text-sm focus:ring-2 focus:ring-indigo-300 focus:border-transparent outline-none"
               value="" />
        HTML;
        break;

    // -----------------------------------------------------------------------
    // POST: update task
    // -----------------------------------------------------------------------
    case 'update':
        if ($method !== 'POST') { http_response_code(405); break; }

        $id = (int) ($_GET['id'] ?? 0);
        $data = [];
        if (isset($_POST['title']))    $data['title']    = trim($_POST['title']);
        if (isset($_POST['category'])) $data['category'] = $_POST['category'];
        if (isset($_POST['priority'])) $data['priority'] = $_POST['priority'];
        if (isset($_POST['due_date'])) $data['due_date'] = $_POST['due_date'] ?: null;

        $todo = todoUpdate($id, $data);
        if (!$todo) {
            http_response_code(404);
            echo '<div class="text-red-500 text-sm p-2">Task not found</div>';
            break;
        }

        echo renderTaskRow($todo);
        break;

    // -----------------------------------------------------------------------
    // POST: toggle complete/incomplete
    // -----------------------------------------------------------------------
    case 'toggle':
        if ($method !== 'POST') { http_response_code(405); break; }

        $id = (int) ($_GET['id'] ?? 0);
        $todo = todoToggle($id);
        if (!$todo) {
            http_response_code(404);
            break;
        }

        echo renderTaskRow($todo);

        // OOB count update
        $filters = currentFilters();
        echo renderTaskCount($filters);
        break;

    // -----------------------------------------------------------------------
    // POST: reorder tasks (from SortableJS)
    // -----------------------------------------------------------------------
    case 'reorder':
        if ($method !== 'POST') { http_response_code(405); break; }

        $input = file_get_contents('php://input');
        parse_str($input, $parsed);
        $ids = $parsed['order'] ?? [];

        if (is_array($ids) && !empty($ids)) {
            todoReorder($ids);
        }

        http_response_code(204);
        break;

    // -----------------------------------------------------------------------
    // DELETE: remove task
    // -----------------------------------------------------------------------
    case 'delete':
        if ($method !== 'DELETE') { http_response_code(405); break; }

        $id = (int) ($_GET['id'] ?? 0);
        todoDelete($id);

        // Return empty string (target will be removed) + OOB count
        $filters = currentFilters();
        echo renderTaskCount($filters);
        break;

    // -----------------------------------------------------------------------
    // Unknown action
    // -----------------------------------------------------------------------
    default:
        http_response_code(400);
        echo '<div class="text-red-500 text-sm p-2">Unknown action</div>';
        break;
}
