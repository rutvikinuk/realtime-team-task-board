const BASE = import.meta.env.VITE_API_URL ?? '/api'

async function request(path, options = {}) {
  const res = await fetch(`${BASE}${path}`, {
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    ...options,
  })
  if (!res.ok) throw new Error(`API ${res.status} on ${path}`)
  return res.status === 204 ? null : res.json()
}

export const listBoards = () => request('/boards').then((r) => r.data)
export const getBoard = (id) => request(`/boards/${id}`).then((r) => r.data)
export const moveTaskOnServer = (taskId, columnId, position) =>
  request(`/tasks/${taskId}/move`, {
    method: 'PATCH',
    body: JSON.stringify({ task_column_id: columnId, position }),
  })

const json = (method, body) => ({ method, body: JSON.stringify(body) })

// Tasks endpoints return the bare task; column endpoints wrap it in `data`.
export const createTask = (columnId, title) =>
  request('/tasks', json('POST', { task_column_id: columnId, title }))
export const updateTaskOnServer = (id, changes) => request(`/tasks/${id}`, json('PATCH', changes))
export const deleteTaskOnServer = (id) => request(`/tasks/${id}`, { method: 'DELETE' })

export const createColumn = (boardId, title) =>
  request('/columns', json('POST', { board_id: boardId, title })).then((r) => r.data)
export const updateColumnOnServer = (id, changes) =>
  request(`/columns/${id}`, json('PATCH', changes)).then((r) => r.data)
export const deleteColumnOnServer = (id) => request(`/columns/${id}`, { method: 'DELETE' })
