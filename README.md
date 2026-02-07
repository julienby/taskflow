# Taskflow

A single-page todo application built with PHP, SQLite, HTMX and Tailwind CSS. Features a modern glassmorphism UI with no full page reloads.

![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![HTMX](https://img.shields.io/badge/HTMX-2.x-3366CC)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-06B6D4?logo=tailwindcss&logoColor=white)

## Features

- **Add / Edit / Delete / Toggle** tasks with instant HTMX updates
- **Drag-to-reorder** via SortableJS
- **Real-time search** with 300ms debounce
- **Filter** by category, priority, and status (All / Active / Done)
- **Dark mode** with localStorage persistence
- **Glassmorphism UI** with animated gradient background
- **Overdue indicators** with red pulse animation
- **Responsive** design (mobile-friendly)
- **Zero dependencies** server-side — just PHP + SQLite

## Quick Start

```bash
git clone https://github.com/julienby/taskflow.git
cd taskflow
php -S localhost:8080
```

Open [http://localhost:8080](http://localhost:8080) in your browser. The SQLite database (`todo.db`) is created automatically on first request.

## Project Structure

```
taskflow/
├── index.php    # Main page (layout, add form, filters, task list, inline JS)
├── api.php      # HTMX endpoint router (returns HTML fragments)
├── db.php       # SQLite init, CRUD helpers, shared HTML render functions
├── style.css    # Glassmorphism, gradient, transitions, SortableJS styles
└── PRD.md       # Product requirements document
```

## Tech Stack

| Layer    | Technology |
|----------|------------|
| Backend  | PHP 8+ with SQLite (WAL mode) |
| Frontend | HTMX 2.x, Tailwind CSS v4 (CDN), SortableJS |
| Pattern  | HTML-over-the-Wire — every endpoint returns HTML, not JSON |

## License

MIT
