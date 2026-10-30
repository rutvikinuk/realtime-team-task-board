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
