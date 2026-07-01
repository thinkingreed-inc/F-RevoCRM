import js from '@eslint/js';
import globals from 'globals';
import tsParser from '@typescript-eslint/parser';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import jsxA11y from 'eslint-plugin-jsx-a11y';

/**
 * ESLint flat config (ESLint 9)
 * これまで設定ファイルが無く lint が動作していなかったため新規作成。
 */
export default [
  {
    ignores: ['dist/**', 'coverage/**', 'node_modules/**'],
  },
  js.configs.recommended,
  {
    files: ['**/*.{ts,tsx}'],
    languageOptions: {
      parser: tsParser,
      ecmaVersion: 2020,
      sourceType: 'module',
      parserOptions: {
        ecmaFeatures: { jsx: true },
      },
      globals: {
        ...globals.browser,
        ...globals.node,
      },
    },
    plugins: {
      '@typescript-eslint': tsPlugin,
      react,
      'react-hooks': reactHooks,
      'react-refresh': reactRefresh,
      'jsx-a11y': jsxA11y,
    },
    settings: {
      react: { version: 'detect' },
    },
    rules: {
      ...tsPlugin.configs.recommended.rules,
      ...react.configs.recommended.rules,
      // TypeScript が未定義参照を型検査で担保するため no-undef は無効化
      // (typescript-eslint 公式推奨。React 等の誤検知を防ぐ)
      'no-undef': 'off',
      // 既存コードで any が多用されており、型付けの再整備は別対応。
      // lint を導入するにあたり当面は警告に留める
      '@typescript-eslint/no-explicit-any': 'warn',
      // 未使用変数も当面は警告。_ 始まりは意図的な無視として許可
      '@typescript-eslint/no-unused-vars': [
        'warn',
        {
          argsIgnorePattern: '^_',
          varsIgnorePattern: '^_',
          caughtErrorsIgnorePattern: '^_',
        },
      ],
      // 既存の大規模 switch で case 内 const が多数。各 case は return で
      // 抜けるため実害はなく、brace 化は段階対応とし当面は警告
      'no-case-declarations': 'warn',
      // React 17+ の JSX transform では import React 不要
      'react/react-in-jsx-scope': 'off',
      // TypeScript で型を担保しているため prop-types は不要
      'react/prop-types': 'off',
      // Hooks の基本ルール
      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',
      // Fast Refresh (Vite) 向け
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
    },
  },
  // テスト・設定ファイル: Vitest のグローバルを許可
  {
    files: [
      '**/*.test.{ts,tsx}',
      '**/__tests__/**/*.{ts,tsx}',
      'src/setupTests.ts',
      '*.config.{ts,js}',
    ],
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.node,
        describe: 'readonly',
        it: 'readonly',
        test: 'readonly',
        expect: 'readonly',
        vi: 'readonly',
        beforeAll: 'readonly',
        beforeEach: 'readonly',
        afterAll: 'readonly',
        afterEach: 'readonly',
      },
    },
  },
];
