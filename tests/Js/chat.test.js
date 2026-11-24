import test from 'node:test';
import assert from 'node:assert/strict';
import leafletChat, { buildPayload, trimHistory } from '../../resources/js/chat.js';

test('history keeps six items, strips display flags and bounds Unicode content without mutating input', () => {
    const history = Array.from({ length: 10 }, (_, index) => ({
        role: index % 2 ? 'assistant' : 'user', content: `${index}😀abcd`, redFlag: true,
    }));
    const trimmed = trimHistory(history, 3);
    assert.equal(trimmed.length, 6);
    assert.deepEqual(trimmed[0], { role: 'user', content: '4😀a' });
    assert.equal(history[0].content, '0😀abcd');
    assert.deepEqual(trimHistory([], 500), []);
    assert.deepEqual(buildPayload(['tooth', 'skin', 'tooth'], '  Question?  ', history, 3), {
        procedures: ['tooth', 'skin'], message: 'Question?', history: trimmed,
    });
});

const createChat = () => {
    const chat = leafletChat({
        endpoint: '/et/chat', procedures: ['tooth'], maxChars: 500,
        validation: 'Validation error', networkError: 'Please retry',
    });
    chat.$refs = { input: { focus() {} } };
    chat.message = 'Question?';
    return chat;
};

test('send posts JSON with CSRF, preserves reply flags and limits history across repeated sends', async (t) => {
    t.mock.method(globalThis, 'fetch', async (endpoint, request) => {
        assert.equal(endpoint, '/et/chat');
        assert.equal(request.headers['X-CSRF-TOKEN'], 'csrf');
        assert.equal(request.method, 'POST');
        const payload = JSON.parse(request.body);
        assert.ok(payload.history.length <= 6);
        assert.deepEqual(payload.procedures, ['tooth']);
        return { ok: true, json: async () => ({ reply: '<script>untrusted</script>', red_flag: true, fallback: false }) };
    });
    globalThis.document = { querySelector: () => ({ content: 'csrf' }) };
    t.after(() => { delete globalThis.document; });
    const chat = createChat();
    for (let index = 0; index < 5; index++) {
        chat.message = 'Question?';
        await chat.send();
    }
    assert.equal(chat.messages.length, 10);
    assert.equal(chat.history.length, 6);
    assert.equal(chat.messages[1].content, '<script>untrusted</script>');
    assert.equal(chat.messages[1].redFlag, true);
    assert.equal(chat.message, '');
    assert.equal(chat.loading, false);
});

test('validation, rate limit and network failures keep the question available to retry and restore focus', async (t) => {
    globalThis.document = { querySelector: () => ({ content: 'csrf' }) };
    t.after(() => { delete globalThis.document; });
    for (const status of [422, 429, 500]) {
        const mock = t.mock.method(globalThis, 'fetch', async () => ({
            ok: false, status, json: async () => ({ message: 'Server message' }),
        }));
        const chat = createChat();
        let focused = 0;
        chat.$refs.input.focus = () => { focused++; };
        await chat.send();
        assert.equal(chat.error, status === 500 ? 'Please retry' : 'Server message');
        assert.equal(chat.message, 'Question?');
        assert.equal(chat.messages.length, 0);
        assert.equal(chat.history.length, 0);
        assert.equal(chat.loading, false);
        assert.equal(focused, 2);
        mock.mock.restore();
    }
    t.mock.method(globalThis, 'fetch', async () => { throw new Error('Offline'); });
    const chat = createChat();
    await chat.send();
    assert.equal(chat.error, 'Please retry');
    assert.equal(chat.loading, false);
});

test('empty, oversized and concurrent sends do not issue requests', async (t) => {
    const mock = t.mock.method(globalThis, 'fetch', () => { throw new Error('Unexpected request'); });
    const chat = createChat();
    chat.message = '  ';
    await chat.send();
    chat.message = 'a'.repeat(501);
    await chat.send();
    assert.equal(chat.error, 'Validation error');
    chat.message = 'Question?';
    chat.loading = true;
    await chat.send();
    assert.equal(mock.mock.callCount(), 0);
});
