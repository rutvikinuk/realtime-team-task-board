import { useCallback, useEffect, useState } from 'react'
import {
  createColumn, createTask, deleteColumnOnServer, deleteTaskOnServer, getBoard, listBoards,
  moveTaskOnServer, updateColumnOnServer, updateTaskOnServer,
} from './api.js'
import {
  addColumn, addTask, moveTask, removeColumn, removeTask, updateColumn, updateTask,
} from './board.js'
import './App.css'

// Small form used for adding items and for inline renaming.
function TitleForm({ initial = '', placeholder, submitLabel, onSubmit, onCancel }) {
  const [value, setValue] = useState(initial)
  return (
    <form
      className="title-form"
      onSubmit={(e) => {
        e.preventDefault()
        const title = value.trim()
        if (title) onSubmit(title)
      }}
    >
      <input
        autoFocus
        value={value}
        maxLength={255}
        placeholder={placeholder}
        aria-label={placeholder}
        onChange={(e) => setValue(e.target.value)}
        onKeyDown={(e) => e.key === 'Escape' && onCancel()}
      />
      <button type="submit" disabled={!value.trim()}>{submitLabel}</button>
      <button type="button" onClick={onCancel}>Cancel</button>
    </form>
  )
}

function Card({ task, onDragStart, onRename, onDelete }) {
  const [editing, setEditing] = useState(false)
  if (editing) {
    return (
      <li className="card editing">
        <TitleForm
          initial={task.title}
          placeholder="Task title"
          submitLabel="Save"
          onSubmit={(title) => { setEditing(false); onRename(task.id, title) }}
          onCancel={() => setEditing(false)}
        />
      </li>
    )
  }
  return (
    <li
      className="card"
      draggable
      onDragStart={(e) => {
        e.dataTransfer.effectAllowed = 'move'
        e.dataTransfer.setData('text/plain', String(task.id))
        onDragStart(task.id)
      }}
    >
      <span className="card-title">{task.title}</span>
      <span className="card-actions">
        <button type="button" aria-label={`Edit ${task.title}`} onClick={() => setEditing(true)}>Edit</button>
        <button type="button" aria-label={`Delete ${task.title}`} onClick={() => onDelete(task.id)}>Delete</button>
      </span>
    </li>
  )
}

function Column({ column, dragging, onDragStart, onDropAt, actions }) {
  const [over, setOver] = useState(false)
  const [adding, setAdding] = useState(false)
  const [renaming, setRenaming] = useState(false)

  // Work out the drop index from the pointer's Y relative to each card.
  function indexFromEvent(e) {
    const cards = [...e.currentTarget.querySelectorAll('.card')]
    const i = cards.findIndex((el) => {
      const r = el.getBoundingClientRect()
      return e.clientY < r.top + r.height / 2
    })
    return i === -1 ? cards.length : i
  }

  return (
    <section
      className={`column${over ? ' over' : ''}`}
      onDragOver={(e) => {
        if (dragging == null) return
        e.preventDefault()
        setOver(true)
      }}
      onDragLeave={() => setOver(false)}
      onDrop={(e) => {
        e.preventDefault()
        setOver(false)
        onDropAt(column.id, indexFromEvent(e))
      }}
    >
      {renaming ? (
        <TitleForm
          initial={column.title}
          placeholder="Column title"
          submitLabel="Save"
          onSubmit={(title) => { setRenaming(false); actions.renameColumn(column.id, title) }}
          onCancel={() => setRenaming(false)}
        />
      ) : (
        <h2>
          {column.title} <span className="count">{column.tasks.length}</span>
          <span className="card-actions">
            <button type="button" aria-label={`Rename ${column.title}`} onClick={() => setRenaming(true)}>Rename</button>
            <button
              type="button"
              aria-label={`Delete column ${column.title}`}
              onClick={() => {
                const n = column.tasks.length
                if (n === 0 || window.confirm(`Delete "${column.title}" and its ${n} task(s)?`)) {
                  actions.deleteColumn(column.id)
                }
              }}
            >
              Delete
            </button>
          </span>
        </h2>
      )}
      <ul>
        {column.tasks.map((t) => (
          <Card
            key={t.id}
            task={t}
            onDragStart={onDragStart}
            onRename={actions.renameTask}
            onDelete={actions.deleteTask}
          />
        ))}
      </ul>
      {adding ? (
        <TitleForm
          placeholder="Task title"
          submitLabel="Add"
          onSubmit={(title) => { setAdding(false); actions.createTask(column.id, title) }}
          onCancel={() => setAdding(false)}
        />
      ) : (
        <button type="button" className="add" onClick={() => setAdding(true)}>+ Add task</button>
      )}
    </section>
  )
}

function AddColumn({ onAdd }) {
  const [open, setOpen] = useState(false)
  return (
    <div className="add-column">
      {open ? (
        <TitleForm
          placeholder="Column title"
          submitLabel="Add column"
          onSubmit={(title) => { setOpen(false); onAdd(title) }}
          onCancel={() => setOpen(false)}
        />
      ) : (
        <button type="button" onClick={() => setOpen(true)}>+ Add column</button>
      )}
    </div>
  )
}

export default function App() {
  const [board, setBoard] = useState(null)
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState(null)
  const [dragging, setDragging] = useState(null)

  const load = useCallback(async () => {
    try {
      const boards = await listBoards()
      if (boards.length === 0) {
        setStatus('empty')
        return
      }
      setBoard(await getBoard(boards[0].id))
      setStatus('ready')
    } catch (e) {
      setError(e.message)
      setStatus('error')
    }
  }, [])

  useEffect(() => {
    load()
  }, [load])

  async function handleDrop(columnId, index) {
    const taskId = dragging
    setDragging(null)
    if (taskId == null || !board) return
    const previous = board
    setBoard(moveTask(board, taskId, columnId, index)) // optimistic
    try {
      await moveTaskOnServer(taskId, columnId, index)
    } catch (e) {
      setBoard(previous) // roll back
      setError(`Could not move card: ${e.message}`)
    }
  }

  // Run `optimistic` on the board right away; call the server; roll back on failure.
  async function mutate(optimistic, call, failMessage) {
    const previous = board
    setBoard(optimistic)
    try {
      return await call()
    } catch (e) {
      setBoard(previous)
      setError(`${failMessage}: ${e.message}`)
      return undefined
    }
  }

  const actions = {
    async createTask(columnId, title) {
      // The server assigns the id, so we wait for it instead of guessing.
      try {
        const task = await createTask(columnId, title)
        setBoard((b) => addTask(b, task))
      } catch (e) {
        setError(`Could not add task: ${e.message}`)
      }
    },
    renameTask: (id, title) =>
      mutate(updateTask(board, id, { title }), () => updateTaskOnServer(id, { title }), 'Could not rename task'),
    deleteTask: (id) =>
      mutate(removeTask(board, id), () => deleteTaskOnServer(id), 'Could not delete task'),
    renameColumn: (id, title) =>
      mutate(updateColumn(board, id, { title }), () => updateColumnOnServer(id, { title }), 'Could not rename column'),
    deleteColumn: (id) =>
      mutate(removeColumn(board, id), () => deleteColumnOnServer(id), 'Could not delete column'),
  }

  async function handleAddColumn(title) {
    try {
      const column = await createColumn(board.id, title)
      setBoard((b) => addColumn(b, column))
    } catch (e) {
      setError(`Could not add column: ${e.message}`)
    }
  }

  if (status === 'loading') return <p className="note">Loading board…</p>
  if (status === 'empty') return <p className="note">No boards yet. Run <code>php artisan db:seed</code>.</p>
  if (status === 'error') {
    return (
      <p className="note">
        Could not load the board ({error}). <button onClick={() => { setStatus('loading'); load() }}>Retry</button>
      </p>
    )
  }

  return (
    <main>
      <h1>{board.title}</h1>
      {error && (
        <p className="error" role="alert" onClick={() => setError(null)}>
          {error}
        </p>
      )}
      <div className="columns">
        {board.columns.map((c) => (
          <Column
            key={c.id}
            column={c}
            dragging={dragging}
            onDragStart={setDragging}
            onDropAt={handleDrop}
            actions={actions}
          />
        ))}
        <AddColumn onAdd={handleAddColumn} />
      </div>
    </main>
  )
}
