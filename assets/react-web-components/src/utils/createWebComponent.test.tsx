import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { createWebComponent } from './createWebComponent';
import React from 'react';

const DummyComponent = () => React.createElement('div', { 'data-testid': 'dummy' }, 'test');

describe('createWebComponent - ホスト要素スタイル', () => {
  const TAG_NAME = 'test-web-component-style';

  beforeEach(() => {
    if (!customElements.get(TAG_NAME)) {
      createWebComponent(DummyComponent, TAG_NAME, []);
    }
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('connectedCallback後にdisplayがblockになること', async () => {
    const el = document.createElement(TAG_NAME);
    document.body.appendChild(el);
    await customElements.whenDefined(TAG_NAME);
    expect(el.style.display).toBe('block');
  });

  it('connectedCallback後にwidthが100%になること', async () => {
    const el = document.createElement(TAG_NAME);
    document.body.appendChild(el);
    await customElements.whenDefined(TAG_NAME);
    expect(el.style.width).toBe('100%');
  });

  it('connectedCallback後にmaxWidthが100%になること', async () => {
    const el = document.createElement(TAG_NAME);
    document.body.appendChild(el);
    await customElements.whenDefined(TAG_NAME);
    expect(el.style.maxWidth).toBe('100%');
  });

  // NOTE: 'connectedCallback後にoverflowがhiddenになること' テストは削除済み（2026-04-14）
  // 削除理由:
  //   CSS `.tiptap-editor-content { overflow: hidden }` でコンテンツエリアを制御済み。
  //   ツールバーは flex-wrap:wrap（デスクトップ）/ overflow-x:auto（モバイル）で対応。
  //   ホスト要素へのインラインスタイル追加は上記CSSと重複するため不要と判断。
  //   （CTO確認済み: 2026-04-14）

  it('connectedCallback後にboxSizingがborder-boxになること', async () => {
    const el = document.createElement(TAG_NAME);
    document.body.appendChild(el);
    await customElements.whenDefined(TAG_NAME);
    expect(el.style.boxSizing).toBe('border-box');
  });
});
