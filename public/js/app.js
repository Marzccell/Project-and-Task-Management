'use strict';

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
let lastOpener = null;

function showClientToast(message, type = 'success') {
  const existing = $('.client-toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = `toast client-toast ${type === 'error' ? 'is-error' : ''}`;
  toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
  toast.innerHTML = `<span aria-hidden="true">${type === 'error' ? '!' : '✓'}</span><span></span><button class="icon-btn" type="button" data-dismiss aria-label="Tutup">×</button>`;
  toast.children[1].textContent = message;
  $('.content')?.prepend(toast);
  window.setTimeout(() => toast.remove(), 4500);
}

function showDialog(dialog, opener = null) {
  if (!dialog) return;
  lastOpener = opener || document.activeElement;
  dialog.showModal();
}

$$('dialog').forEach(dialog => {
  dialog.addEventListener('close', () => lastOpener?.focus());
  dialog.addEventListener('click', event => {
    if (event.target !== dialog) return;
    const box = dialog.getBoundingClientRect();
    if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
  });
});

document.addEventListener('click', event => {
  const close = event.target.closest('[data-close-dialog]');
  if (close) close.closest('dialog')?.close();
  const dismiss = event.target.closest('[data-dismiss]');
  if (dismiss) dismiss.closest('.toast')?.remove();
});

$$('[data-submit-change]').forEach(control => control.addEventListener('change', () => control.form.requestSubmit()));
$$('form').forEach(form => form.addEventListener('submit', event => {
  if (!form.checkValidity()) return;
  if (form.dataset.confirm && !confirm(form.dataset.confirm)) {
    event.preventDefault();
    return;
  }
  $$('button[type="submit"]', form).forEach(button => {
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
  });
}));

// Task create/edit dialog.
const taskDialog = $('#task-dialog');
const taskForm = $('#task-form');
const taskData = JSON.parse($('#task-data')?.textContent || '{}');
function openTask(task = null, opener = null, preserveErrors = false) {
  if (!taskForm || !taskDialog) return;
  taskForm.reset();
  const editing = Boolean(task?.id || task?._task_id);
  const id = task?.id || task?._task_id || '';
  taskForm.action = editing ? (task.url || `${taskForm.dataset.storeUrl}/${encodeURIComponent(id)}`) : taskForm.dataset.storeUrl;
  $('#task-method').value = editing ? 'PUT' : 'POST';
  $('#task-id').value = id;
  taskForm.dataset.existingAttachments = task?.attachment_count || 0;
  $('#task-dialog-title').textContent = editing ? 'Edit tugas' : 'Buat tugas';
  $('#save-task').textContent = editing ? 'Simpan perubahan' : 'Buat tugas';
  const defaults = { title: '', description: '', project_id: $('#task-project_id option')?.value || '', status: 'todo', due_date: '' };
  Object.entries(defaults).forEach(([key, value]) => {
    const field = $(`#task-${key}`);
    if (field) field.value = task?.[key] ?? value;
  });
  $('#task-attachments')?.setCustomValidity('');
  showDialog(taskDialog, opener);
  if (!preserveErrors) $('#task-title')?.focus();
}

document.addEventListener('click', event => {
  const create = event.target.closest('[data-new-task]');
  if (create) openTask(null, create);
  const edit = event.target.closest('[data-edit-task]');
  if (edit) openTask(taskData[edit.dataset.editTask], edit);
  const remove = event.target.closest('[data-delete-task]');
  if (remove) {
    const task = taskData[remove.dataset.deleteTask];
    if (!task) return;
    $('#delete-form').action = task.url;
    $('#delete-task-title').textContent = task.title;
    showDialog($('#delete-dialog'), remove);
  }
});

const oldTaskInput = JSON.parse($('#old-input')?.textContent || 'null');
if (oldTaskInput && ('title' in oldTaskInput || '_task_id' in oldTaskInput)) {
  const originalTask = taskData[oldTaskInput._task_id] || {};
  openTask({ ...originalTask, ...oldTaskInput }, null, true);
}

$('#task-attachments')?.addEventListener('change', event => {
  const files = [...event.target.files];
  const existingFiles = Number(taskForm?.dataset.existingAttachments || 0);
  const tooLarge = files.find(file => file.size > 10 * 1024 * 1024);
  let message = '';
  if (existingFiles + files.length > 5) message = `Tugas ini hanya dapat memiliki 5 lampiran (saat ini ${existingFiles}).`;
  if (tooLarge) message = `${tooLarge.name} melebihi batas 10 MB.`;
  event.target.setCustomValidity(message);
  if (message) event.target.reportValidity();
});

// Project create/edit dialog.
const projectDialog = $('#project-dialog');
const projectForm = $('#project-form');
const projectData = JSON.parse($('#project-data')?.textContent || '{}');
function openProject(project = null, opener = null) {
  if (!projectForm || !projectDialog) return;
  projectForm.reset();
  const editing = Boolean(project?.id || project?._project_id);
  const id = project?.id || project?._project_id || '';
  projectForm.action = editing ? (project.url || `${projectForm.dataset.storeUrl}/${encodeURIComponent(id)}`) : projectForm.dataset.storeUrl;
  $('#project-method').value = editing ? 'PUT' : 'POST';
  $('#project-id').value = id;
  $('#project-dialog-title').textContent = editing ? 'Edit proyek' : 'Buat proyek';
  $('#save-project').textContent = editing ? 'Simpan perubahan' : 'Buat proyek';
  const defaults = { name: '', description: '', deadline: '', status: 'active' };
  Object.entries(defaults).forEach(([key, value]) => {
    const field = $(`#project-${key}`);
    if (field) field.value = project?.[key] ?? value;
  });
  showDialog(projectDialog, opener);
  $('#project-name')?.focus();
}

const oldProjectInput = JSON.parse($('#project-old-input')?.textContent || 'null');
if (oldProjectInput && ('name' in oldProjectInput || '_project_id' in oldProjectInput)) openProject(oldProjectInput);

document.addEventListener('click', event => {
  const create = event.target.closest('[data-new-project]');
  if (create) openProject(null, create);
  const edit = event.target.closest('[data-edit-project]');
  if (edit) openProject(projectData[edit.dataset.editProject], edit);
  const remove = event.target.closest('[data-delete-project]');
  if (remove) {
    const project = projectData[remove.dataset.deleteProject];
    if (!project) return;
    $('#delete-project-form').action = project.url;
    $('#delete-project-title').textContent = `“${project.name}”`;
    showDialog($('#delete-project-dialog'), remove);
  }
});

// Accessible keyboard shortcuts.
document.addEventListener('keydown', event => {
  if (event.ctrlKey || event.metaKey || event.altKey || /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) || $('dialog[open]')) return;
  if (event.key.toLowerCase() === 'n' && taskDialog) { event.preventDefault(); openTask(); }
  if (event.key === '/' && $('#search')) { event.preventDefault(); $('#search').focus(); }
});

// Responsive navigation.
const toggleMenu = () => {
  const sidebar = $('#sidebar');
  if (!sidebar) return;
  const opened = sidebar.classList.toggle('is-open');
  $('#sidebar-backdrop').hidden = !opened;
  $('#menu-toggle').setAttribute('aria-expanded', String(opened));
};
$('#menu-toggle')?.addEventListener('click', toggleMenu);
$('#sidebar-backdrop')?.addEventListener('click', toggleMenu);

// Persisted dark mode.
const preferredTheme = localStorage.getItem('campusflow-theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
document.documentElement.dataset.theme = preferredTheme;
const themeToggle = $('[data-theme-toggle]');
themeToggle?.setAttribute('aria-label', preferredTheme === 'dark' ? 'Gunakan tema terang' : 'Gunakan tema gelap');
themeToggle?.addEventListener('click', () => {
  const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
  document.documentElement.dataset.theme = next;
  localStorage.setItem('campusflow-theme', next);
  themeToggle.setAttribute('aria-label', next === 'dark' ? 'Gunakan tema terang' : 'Gunakan tema gelap');
});

// Drag and drop task status on the board.
let draggedTask = null;
$$('[data-drag-task][draggable="true"]').forEach(card => {
  card.addEventListener('dragstart', event => {
    draggedTask = card;
    card.classList.add('is-dragging');
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', card.dataset.dragTask);
  });
  card.addEventListener('dragend', () => {
    card.classList.remove('is-dragging');
    $$('.kanban-column').forEach(column => column.classList.remove('is-drag-over'));
    draggedTask = null;
  });
});

$$('[data-drop-status]').forEach(column => {
  column.addEventListener('dragover', event => {
    event.preventDefault();
    column.classList.add('is-drag-over');
    event.dataTransfer.dropEffect = 'move';
  });
  column.addEventListener('dragleave', event => {
    if (!column.contains(event.relatedTarget)) column.classList.remove('is-drag-over');
  });
  column.addEventListener('drop', async event => {
    event.preventDefault();
    column.classList.remove('is-drag-over');
    if (!draggedTask) return;
    const card = draggedTask;
    const oldColumn = card.closest('[data-drop-status]');
    const newStatus = column.dataset.dropStatus;
    const previousStatus = taskData[card.dataset.dragTask]?.status;
    if (previousStatus === newStatus) return;
    column.querySelector('.kanban-dropzone').append(card);
    try {
      const response = await fetch(card.dataset.statusUrl, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ status: newStatus }),
      });
      if (!response.ok) throw new Error('Status update failed');
      const statusSelect = card.querySelector('.status-select');
      statusSelect.value = newStatus;
      statusSelect.className = `status-select status-${newStatus}`;
      const checkInput = card.querySelector('.check-form input[name="status"]');
      const checkButton = card.querySelector('.task-check');
      const isDone = newStatus === 'done';
      card.classList.toggle('is-done', isDone);
      if (checkInput) checkInput.value = isDone ? 'todo' : 'done';
      if (checkButton) {
        checkButton.classList.toggle('checked', isDone);
        checkButton.innerHTML = isDone ? '<span aria-hidden="true">✓</span>' : '';
        checkButton.setAttribute('aria-label', `${isDone ? 'Buka kembali' : 'Selesaikan'} ${taskData[card.dataset.dragTask]?.title || 'tugas'}`);
      }
      if (taskData[card.dataset.dragTask]) taskData[card.dataset.dragTask].status = newStatus;
      $$('.kanban-column').forEach(updateColumnCount);
      showClientToast('Status tugas diperbarui.');
    } catch (error) {
      oldColumn?.querySelector('.kanban-dropzone').append(card);
      showClientToast('Status gagal diperbarui. Coba lagi.', 'error');
    }
  });
});

function updateColumnCount(column) {
  const count = column.querySelectorAll('[data-drag-task]').length;
  const label = column.querySelector('[data-column-count]');
  if (label) label.textContent = count;
}
