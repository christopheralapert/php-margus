import assert from 'node:assert/strict';
import test from 'node:test';
import procedurePicker, { normalizeSearch } from '../../resources/js/wizard.js';

test('search ignores case and Estonian diacritics', () => {
    assert.equal(normalizeSearch('ÕÄÖÜŠŽ'), 'oaousz');
    const picker = procedurePicker({ 1: ['Õmblus eemaldamine'], 2: ['Naha ravi sünnimärk'] });
    picker.query = ' OMBLUS ';
    assert.equal(picker.resultCount, 1);
    assert.equal(picker.categoryVisible(1), true);
    assert.equal(picker.categoryVisible(2), false);
    picker.query = 'sunnimark';
    assert.equal(picker.categoryVisible(2), true);
});

test('no results and clearing search preserve hidden selections', () => {
    const picker = procedurePicker({ 1: ['Tooth extraction'], 2: ['Skin biopsy'] }, [7]);
    picker.query = 'unmatched';
    assert.equal(picker.resultCount, 0);
    assert.deepEqual(picker.selected, ['7']);
    picker.query = '';
    assert.equal(picker.resultCount, 2);
    assert.deepEqual(picker.selected, ['7']);
    assert.equal(procedurePicker({}).resultCount, 0);
});
