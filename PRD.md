# Taskflow - Product Requirements Document

## Overview
Taskflow is a single-page todo application built with PHP, SQLite, HTMX, and Tailwind CSS. It features a modern glassmorphism UI with no full page reloads.

## Tech Stack
- **Backend**: PHP 8+ with SQLite (WAL mode)
- **Frontend**: HTMX 2.x, Tailwind CSS v4 (browser CDN), SortableJS
- **Pattern**: HTML-over-the-Wire (endpoints return HTML fragments, not JSON)

## Features

### Task Management
- Create tasks with title, category, priority, and optional due date
- Inline editing of existing tasks
- Toggle task completion status
- Delete tasks with confirmation
- Drag-to-reorder tasks via SortableJS

### Filtering & Search
- Real-time search with 300ms debounce
- Filter by category (work, personal, shopping, health, education, finance)
- Filter by priority (low, medium, high)
- Filter by status (all, active, done)
- Live task count badge

### Visual Design
- Animated gradient background (indigo/purple/pink)
- Glassmorphism card effects (backdrop blur, translucent backgrounds)
- Dark mode with localStorage persistence and flash prevention
- Priority-colored left borders on task rows
- Overdue date indicators with red pulse animation
- HTMX transition animations (fade-in, slide-out, flash)

### Responsive Design
- Desktop: hover-reveal action buttons, comfortable spacing
- Mobile: always-visible action buttons, touch-friendly targets

## Database
- Single `todos` table in SQLite
- Auto-creates `todo.db` on first request
- WAL journal mode for concurrent reads

## Endpoints
All served via `api.php`, returning HTML fragments for HTMX swaps.

## Running
```bash
php -S localhost:8000
```
Open `http://localhost:8000` in a browser.
