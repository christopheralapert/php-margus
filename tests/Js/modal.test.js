import test from 'node:test';
import assert from 'node:assert/strict';
import accessibleModal from '../../resources/js/modal.js';

test('dialog contains tab focus, excludes disabled and hidden controls, and restores focus and inert state', (t) => {
    const element = (options = {}) => ({
        disabled: false, tabIndex: 0, inert: false,
        getClientRects: () => [1],
        focus() { document.activeElement = this; },
        ...options,
    });
    const trigger = element();
    const first = element();
    const last = element();
    const hidden = element({ getClientRects: () => [] });
    const disabled = element({ disabled: true });
    const background = element();
    const alreadyInert = element({ inert: true });
    const body = { classList: { add() {}, remove() {} } };
    const dialog = element({ parentElement: body, querySelectorAll: () => [first, hidden, disabled, last] });
    body.children = [dialog, background, alreadyInert];
    const previousDocument = globalThis.document;
    globalThis.document = { body, activeElement: trigger };
    t.after(() => {
        if (previousDocument === undefined) delete globalThis.document;
        else globalThis.document = previousDocument;
    });

    const modal = accessibleModal();
    modal.$el = dialog;
    modal.$refs = { dialog };
    modal.$nextTick = (callback) => callback();
    modal.activate();
    assert.equal(document.activeElement, first);
    assert.equal(background.inert, true);
    assert.deepEqual(modal.focusables(), [first, last]);

    modal.trap({ shiftKey: true });
    assert.equal(document.activeElement, last);
    modal.trap({ shiftKey: false });
    assert.equal(document.activeElement, first);

    modal.deactivate();
    assert.equal(document.activeElement, trigger);
    assert.equal(background.inert, false);
    assert.equal(alreadyInert.inert, true);
});
