// Pure helpers for the board state. No React, no network: easy to unit test.

/**
 * Return a new board with `taskId` moved into column `toColumnId` at
 * zero-based `index`. Positions in affected columns are renumbered 0..n-1,
 * mirroring what the API does, so optimistic UI matches the server.
 */
export function moveTask(board, taskId, toColumnId, index) {
  let moving = null
  const stripped = board.columns.map((col) => {
    const tasks = col.tasks.filter((t) => {
      if (t.id === taskId) {
        moving = t
        return false
      }
      return true
    })
    return { ...col, tasks }
  })
  if (!moving || !stripped.some((c) => c.id === toColumnId)) return board

  const columns = stripped.map((col) => {
    if (col.id !== toColumnId) return renumber(col)
    const tasks = [...col.tasks]
    tasks.splice(Math.max(0, Math.min(index, tasks.length)), 0, {
      ...moving,
      task_column_id: toColumnId,
    })
    return renumber({ ...col, tasks })
  })
  return { ...board, columns }
}

function renumber(col) {
  return { ...col, tasks: col.tasks.map((t, i) => ({ ...t, position: i })) }
}

// ---- CRUD helpers (all pure: return a new board, never mutate) ----

export function addTask(board, task) {
  return mapColumns(board, (col) =>
    col.id === task.task_column_id ? renumber({ ...col, tasks: [...col.tasks, task] }) : col,
  )
}

export function updateTask(board, taskId, changes) {
  return mapColumns(board, (col) => ({
    ...col,
    tasks: col.tasks.map((t) => (t.id === taskId ? { ...t, ...changes } : t)),
  }))
}

export function removeTask(board, taskId) {
  return mapColumns(board, (col) =>
    col.tasks.some((t) => t.id === taskId)
      ? renumber({ ...col, tasks: col.tasks.filter((t) => t.id !== taskId) })
      : col,
  )
}

export function addColumn(board, column) {
  return { ...board, columns: [...board.columns, { ...column, tasks: column.tasks ?? [] }] }
}

export function updateColumn(board, columnId, changes) {
  return mapColumns(board, (col) => (col.id === columnId ? { ...col, ...changes } : col))
}

export function removeColumn(board, columnId) {
  return { ...board, columns: board.columns.filter((c) => c.id !== columnId) }
}

function mapColumns(board, fn) {
  return { ...board, columns: board.columns.map(fn) }
}
