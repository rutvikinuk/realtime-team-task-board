import { useCallback, useEffect, useState } from 'react'
import { getBoard, listBoards, moveTaskOnServer } from './api.js'
import { moveTask } from './board.js'
import './App.css'

function Card({ task, onDragStart }) {
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
      {task.title}
    </li>
  )
}

function Column({ column, dragging, onDragStart, onDropAt }) {
  const [over, setOver] = useState(false)

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
      <h2>
        {column.title} <span className="count">{column.tasks.length}</span>
      </h2>
      <ul>
        {column.tasks.map((t) => (
          <Card key={t.id} task={t} onDragStart={onDragStart} />
        ))}
      </ul>
    </section>
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
          />
        ))}
      </div>
    </main>
  )
}
