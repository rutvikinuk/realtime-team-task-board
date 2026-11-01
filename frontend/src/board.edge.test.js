import assert from 'node:assert/strict'
import { test } from 'node:test'
import { addColumn, addTask, moveTask, removeColumn, removeTask, updateColumn, updateTask } from './board.js'

const make = () => ({
  id: 1,
  columns: [
    { id: 10, title: 'To Do', tasks: [
      { id: 1, task_column_id: 10, position: 0 },
      { id: 2, task_column_id: 10, position: 1 },
      { id: 3, task_column_id: 10, position: 2 },
    ] },
    { id: 20, title: 'Done', tasks: [] },
  ],
})
const ids = (board, col) => board.columns.find((c) => c.id === col).tasks.map((t) => t.id)
const positions = (board, col) => board.columns.find((c) => c.id === col).tasks.map((t) => t.position)

test('moveTask into an empty column', () => {
  const next = moveTask(make(), 2, 20, 0)
  assert.deepEqual(ids(next, 20), [2])
  assert.deepEqual(positions(next, 20), [0])
  assert.deepEqual(positions(next, 10), [0, 1])
})

test('moveTask clamps a negative index to the top', () => {
  assert.deepEqual(ids(moveTask(make(), 3, 10, -5), 10), [3, 1, 2])
})

test('moveTask to the same spot keeps order and positions', () => {
  const next = moveTask(make(), 2, 10, 1)
  assert.deepEqual(ids(next, 10), [1, 2, 3])
  assert.deepEqual(positions(next, 10), [0, 1, 2])
})

test('moveTask leaves untouched columns as equal data', () => {
  const board = make()
  board.columns.push({ id: 30, title: 'Other', tasks: [{ id: 7, task_column_id: 30, position: 0 }] })
  const next = moveTask(board, 1, 20, 0)
  assert.deepEqual(next.columns[2], board.columns[2])
})

test('moveTask keeps other task fields', () => {
  const board = make()
  board.columns[0].tasks[0].title = 'keep me'
  assert.equal(moveTask(board, 1, 20, 0).columns[1].tasks[0].title, 'keep me')
})

test('addTask to an unknown column changes nothing', () => {
  const next = addTask(make(), { id: 9, task_column_id: 999, title: 'lost' })
  assert.deepEqual(next, make())
})

test('addTask into an empty column gets position 0', () => {
  const next = addTask(make(), { id: 9, task_column_id: 20, title: 'first' })
  assert.deepEqual(positions(next, 20), [0])
})

test('updateTask with an unknown id changes nothing', () => {
  assert.deepEqual(updateTask(make(), 999, { title: 'x' }), make())
})

test('removeTask with an unknown id changes nothing', () => {
  assert.deepEqual(removeTask(make(), 999), make())
})

test('removeTask does not mutate the original board', () => {
  const board = make()
  removeTask(board, 1)
  assert.deepEqual(board, make())
})

test('addColumn keeps tasks passed in and does not mutate', () => {
  const board = make()
  const next = addColumn(board, { id: 30, title: 'R', tasks: [{ id: 5, task_column_id: 30, position: 0 }] })
  assert.deepEqual(ids(next, 30), [5])
  assert.equal(board.columns.length, 2)
})

test('updateColumn and removeColumn ignore unknown ids', () => {
  assert.deepEqual(updateColumn(make(), 999, { title: 'x' }), make())
  assert.deepEqual(removeColumn(make(), 999), make())
})

test('updateColumn does not mutate and keeps tasks', () => {
  const board = make()
  const next = updateColumn(board, 10, { title: 'Backlog' })
  assert.equal(board.columns[0].title, 'To Do')
  assert.deepEqual(ids(next, 10), [1, 2, 3])
})
