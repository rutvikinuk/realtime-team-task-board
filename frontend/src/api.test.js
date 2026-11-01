import assert from 'node:assert/strict'
import { afterEach, beforeEach, test } from 'node:test'
import * as api from './api.js'

const realFetch = globalThis.fetch
let calls

function reply(status, body) {
  globalThis.fetch = async (url, options = {}) => {
    calls.push({ url, options })
    return { ok: status >= 200 && status < 300, status, json: async () => body }
  }
}

beforeEach(() => {
  calls = []
})
afterEach(() => {
  globalThis.fetch = realFetch
})

test('getBoard calls /api/boards/:id and unwraps data', async () => {
  reply(200, { data: { id: 3, columns: [] } })
  assert.deepEqual(await api.getBoard(3), { id: 3, columns: [] })
  assert.equal(calls[0].url, '/api/boards/3')
  assert.equal(calls[0].options.headers.Accept, 'application/json')
})

test('moveTaskOnServer sends PATCH with column and position', async () => {
  reply(200, { data: {} })
  await api.moveTaskOnServer(5, 20, 2)
  assert.equal(calls[0].url, '/api/tasks/5/move')
  assert.equal(calls[0].options.method, 'PATCH')
  assert.deepEqual(JSON.parse(calls[0].options.body), { task_column_id: 20, position: 2 })
})

test('createTask returns the bare task body', async () => {
  reply(201, { id: 9, title: 'x' })
  assert.deepEqual(await api.createTask(10, 'x'), { id: 9, title: 'x' })
  assert.deepEqual(JSON.parse(calls[0].options.body), { task_column_id: 10, title: 'x' })
})

test('createColumn unwraps the data envelope', async () => {
  reply(201, { data: { id: 4, title: 'Review' } })
  assert.deepEqual(await api.createColumn(1, 'Review'), { id: 4, title: 'Review' })
  assert.equal(calls[0].options.method, 'POST')
})

test('a 204 response resolves to null', async () => {
  reply(204, undefined)
  assert.equal(await api.deleteTaskOnServer(7), null)
  assert.equal(calls[0].options.method, 'DELETE')
})

test('a failing status throws an error naming status and path', async () => {
  reply(422, { message: 'bad' })
  await assert.rejects(api.updateTaskOnServer(1, { title: '' }), /API 422 on \/tasks\/1/)
})

test('column update and delete use the columns endpoints', async () => {
  reply(200, { data: { id: 2, title: 'New' } })
  await api.updateColumnOnServer(2, { title: 'New' })
  assert.equal(calls[0].url, '/api/columns/2')
  assert.equal(calls[0].options.method, 'PATCH')
  reply(204, undefined)
  await api.deleteColumnOnServer(2)
  assert.equal(calls[1].options.method, 'DELETE')
})
