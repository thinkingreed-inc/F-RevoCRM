import React from "react";
import { createRoot } from "react-dom/client";

type WebComponentProps = {
  [key: string]: any;
};

/**
 * ReactコンポーネントをWeb Componentとして登録
 * @param Component Reactコンポーネント
 * @param tagName カスタム要素名（ケバブケース）
 * @param observedAttributes 監視する属性名（ケバブケース）
 * @param eventCallbacks カスタムイベントとして発火するコールバック名（キャメルケース、例: ['onSave', 'onCancel']）
 */
export function createWebComponent(
  Component: React.ComponentType<any>,
  tagName: string,
  observedAttributes: string[] = [],
  eventCallbacks: string[] = []
) {
  class ReactWebComponent extends HTMLElement {
    private root: ReturnType<typeof createRoot> | null = null;
    private props: WebComponentProps = {};
    private container: HTMLDivElement | null = null;

    static get observedAttributes() {
      return observedAttributes;
    }

    connectedCallback() {
      // Reactのマウントポイントを作成
      this.container = document.createElement("div");
      this.container.className = "web-components-wrapper";
      this.appendChild(this.container);

      this.root = createRoot(this.container);

      // 初期属性値を設定
      (this.constructor as any).observedAttributes.forEach((attrName: string) => {
        const attrValue = this.getAttribute(attrName);
        if (attrValue !== null) {
          const propName = attrName.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
          try {
            this.props[propName] = JSON.parse(attrValue);
          } catch {
            this.props[propName] = attrValue;
          }
        }
      });

      this.updateComponent();
    }

    disconnectedCallback() {
      if (this.root) {
        this.root.unmount();
      }
    }

    attributeChangedCallback(name: string, _oldValue: string, newValue: string) {
      // ケバブケースをキャメルケースに変換
      const propName = name.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());

      try {
        this.props[propName] = JSON.parse(newValue);
      } catch {
        this.props[propName] = newValue;
      }
      this.updateComponent();
    }

    /**
     * コールバックをカスタムイベントに変換
     * onSave -> 'save' イベント
     * onCancel -> 'cancel' イベント
     * onGoToFullForm -> 'go-to-full-form' イベント
     */
    private createEventCallbacks(): WebComponentProps {
      const callbacks: WebComponentProps = {};
      const element = this;

      eventCallbacks.forEach(callbackName => {
        // onSave -> save, onGoToFullForm -> go-to-full-form
        // 1. "on"プレフィックスを除去
        // 2. キャメルケースをケバブケースに変換
        const withoutOn = callbackName.replace(/^on/, '');
        const eventName = withoutOn
          .replace(/([A-Z])/g, '-$1')
          .toLowerCase()
          .replace(/^-/, ''); // 先頭のハイフンを除去

        callbacks[callbackName] = (...args: any[]) => {
          const event = new CustomEvent(eventName, {
            detail: args.length === 1 ? args[0] : args,
            bubbles: true,
            composed: true
          });
          element.dispatchEvent(event);
        };
      });

      return callbacks;
    }

    private updateComponent() {
      if (!this.root) return;

      // イベントコールバックを生成
      const eventProps = this.createEventCallbacks();
      const props = { ...this.props, ...eventProps };

      console.log(`Rendering ${tagName} with props:`, props);
      this.root.render(
        <React.StrictMode>
          <Component {...props} />
        </React.StrictMode>
      );
    }
  }

  if (!customElements.get(tagName)) {
    customElements.define(tagName, ReactWebComponent);
  }
}
