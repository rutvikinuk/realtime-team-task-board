import assert from 'node:assert/strict'
import { test } from 'node:test'
import { moveTask } from './board.js'

const make = () => ({
  id: 1,
  columns: [
    { id: 10, title: 'To Do', tasks: [
      { id: 1, task_column_id: 10, position: 0 },
      { id: 2, task_column_id: 10, position: 1 },
      { id: 3, task_column_id: 10, position: 2 },
    ] },
    { id: 20, title: 'Done', tasks: [{ id: 4, task_column_id: 20, position: 0 }] },
  ],
})
const ids = (board, col) => board.columns.find((c) => c.id === col).tasks.map((t) => t.id)

test('moves across columns and renumbers both', () => {
  const next = moveTask(make(), 2, 20, 0)
  assert.deepEqual(ids(next, 10), [1, 3])
  assert.deepEqual(ids(next, 20), [2, 4])
  assert.deepEqual(next.columns[0].tasks.map((t) => t.position), [0, 1])
  assert.equal(next.columns[1].tasks[0].task_column_id, 20)
})

test('reorders inside one column', () => {
  assert.deepEqual(ids(moveTask(make(), 1, 10, 2), 10), [2, 3, 1])
})

test('clamps an out-of-range index', () => {
  assert.deepEqual(ids(moveTask(make(), 1, 20, 99), 20), [4, 1])
})

test('does not mutate the original board', () => {
  const board = make()
  moveTask(board, 1, 20, 0)
  assert.deepEqual(ids(board, 10), [1, 2, 3])
})

test('unknown task or column returns the board unchanged', () => {
  const board = make()
  assert.equal(moveTask(board, 999, 20, 0), board)
  assert.equal(moveTask(board, 1, 999, 0), board)
})
