import test from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { useMaxStore } from '../../resources/js/stores/max.js';
import { useTriageStore } from '../../resources/js/stores/triage.js';
import { brandTokens } from '../../resources/js/utils/brandColor.js';
import { consultationStatuses } from '../../resources/js/constants/consultationStatus.js';

test('MAX discards a response after the tenant or profile changes', async () => {
    setActivePinia(createPinia());
    const store = useMaxStore();
    store.reset('tenant-a:patient');
    let finish;
    const pending = store.send(
        {
            respond: () =>
                new Promise((resolve) => {
                    finish = resolve;
                }),
        },
        'Consulta',
        '/consultas',
    );
    store.reset('tenant-b:manager');
    finish({ text: 'Dados do contexto anterior' });
    await pending;
    assert.deepEqual(store.messages, []);
    assert.equal(store.pending, false);
});

test('MAX prevents repeated submission and allows retry after failure', async () => {
    setActivePinia(createPinia());
    const store = useMaxStore();
    let calls = 0,
        reject;
    const service = {
        respond: () => {
            calls++;
            return new Promise((resolve, failure) => {
                reject = failure;
            });
        },
    };
    const pending = store.send(service, 'Consulta', '/consultas');
    await store.send(service, 'Consulta', '/consultas');
    assert.equal(calls, 1);
    reject(new Error('offline'));
    await pending;
    assert.equal(store.pending, false);
    assert.ok(store.error);
    await store.send(
        { respond: async () => ({ text: 'Resposta' }) },
        'Consulta',
        '/consultas',
    );
    assert.equal(store.messages.length, 2);
});

test('triage requires a session, prevents repeated sends, and closes safely', async () => {
    setActivePinia(createPinia());
    const store = useTriageStore();
    store.reset('tenant-a:patient-a');

    await store.start({
        start: async () => ({
            id: 'session-a',
            status: 'active',
            messages: [],
            routing: null,
        }),
    });

    let finish;
    let calls = 0;
    const service = {
        send: () => {
            calls++;
            return new Promise((resolve) => {
                finish = resolve;
            });
        },
    };
    const pending = store.send(service, 'Minha mensagem');
    await store.send(service, 'Mensagem repetida');
    assert.equal(calls, 1);

    finish({
        session: { status: 'human_review' },
        message: { role: 'assistant', text: 'Encaminhado.' },
        state: { classification: 'human_review' },
    });
    await pending;

    assert.equal(store.closed, true);
    assert.equal(store.messages.length, 2);
});

test('white label accepts a single color and maintains readable button text', () => {
    assert.equal(brandTokens('#ffffff')['--marca-on'], '#000000');
    assert.equal(brandTokens('#111111')['--marca-on'], '#ffffff');
    assert.equal(brandTokens('url(javascript:bad)')['--marca'], '#5E5212');
});

test('consultation presentation preserves all eight provider statuses', () => {
    assert.deepEqual(Object.keys(consultationStatuses), [
        'SCHEDULED',
        'PENDING',
        'WAITING_HELPDESK',
        'ONGOING_HELPDESK',
        'WAITING_DOCTOR',
        'ONGOING_DOCTOR',
        'FINISHED',
        'CANCELED',
    ]);
});
