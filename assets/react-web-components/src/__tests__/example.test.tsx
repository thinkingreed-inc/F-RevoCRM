import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';

// 簡単なコンポーネントのテスト例
function TestComponent() {
  return <div>Hello, World!</div>;
}

describe('TestComponent', () => {
  it('renders hello world', () => {
    render(<TestComponent />);
    expect(screen.getByText('Hello, World!')).toBeInTheDocument();
  });
});

// 基本的な関数のテスト例
function add(a: number, b: number) {
  return a + b;
}

describe('add function', () => {
  it('adds two numbers correctly', () => {
    expect(add(2, 3)).toBe(5);
    expect(add(-1, 1)).toBe(0);
    expect(add(0, 0)).toBe(0);
  });
});
