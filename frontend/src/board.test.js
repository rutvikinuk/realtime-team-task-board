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

import { addColumn, addTask, removeColumn, removeTask, updateColumn, updateTask } from './board.js'

test('addTask appends to the right column and numbers positions', () => {
  const next = addTask(make(), { id: 9, task_column_id: 20, title: 'new' })
  assert.deepEqual(ids(next, 20), [4, 9])
  assert.equal(next.columns[1].tasks[1].position, 1)
  assert.deepEqual(ids(next, 10), [1, 2, 3])
})

test('updateTask changes only the matching task and does not mutate', () => {
  const board = make()
  const next = updateTask(board, 2, { title: 'renamed' })
  assert.equal(next.columns[0].tasks[1].title, 'renamed')
  assert.equal(board.columns[0].tasks[1].title, undefined)
})

test('removeTask closes the gap', () => {
  const next = removeTask(make(), 2)
  assert.deepEqual(ids(next, 10), [1, 3])
  assert.deepEqual(next.columns[0].tasks.map((t) => t.position), [0, 1])
})

test('addColumn defaults to an empty task list', () => {
  const next = addColumn(make(), { id: 30, title: 'Review' })
  assert.equal(next.columns.length, 3)
  assert.deepEqual(next.columns[2].tasks, [])
})

test('updateColumn renames and removeColumn drops it with its tasks', () => {
  assert.equal(updateColumn(make(), 10, { title: 'Backlog' }).columns[0].title, 'Backlog')
  const next = removeColumn(make(), 10)
  assert.deepEqual(next.columns.map((c) => c.id), [20])
})
