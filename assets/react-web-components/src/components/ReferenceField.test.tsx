import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import {
  render,
  screen,
  fireEvent,
  waitFor,
  act,
} from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { ReferenceField } from "./ReferenceField";

// モック検索結果
const mockSearchResults = {
  records: [
    { id: "1", label: "テスト企業A", module: "Accounts" },
    { id: "2", label: "テスト企業B", module: "Accounts" },
    { id: "3", label: "テスト企業C", module: "Accounts" },
  ],
};

// fetchのモック
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe("ReferenceField", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers({ shouldAdvanceTime: true });
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(mockSearchResults),
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

  describe("初期表示", () => {
    it("ラベルが正しく表示される", () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
        />,
      );

      expect(screen.getByText("取引先")).toBeInTheDocument();
    });

    it("必須フィールドにアスタリスクが表示される", () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
          mandatory={true}
        />,
      );

      const label = screen.getByText("取引先");
      // ラベルはspan要素として描画される
      const labelElement = label.closest("span");
      expect(labelElement).toBeInTheDocument();
      // 必須マークのspan（*）が兄弟要素として存在することを確認
      const container = labelElement?.parentElement;
      expect(container?.textContent).toContain("*");
    });

    it("初期表示値がセットされる", () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          value="1"
          displayValue="テスト企業A"
          onChange={onChange}
        />,
      );

      const input = screen.getByPlaceholderText("取引先を検索...");
      expect(input).toHaveValue("テスト企業A");
    });

    it("無効化状態で入力不可", () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
          disabled={true}
        />,
      );

      const input = screen.getByPlaceholderText("取引先を検索...");
      expect(input).toBeDisabled();
    });

    it("エラーメッセージが表示される", () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
          error="取引先は必須です"
        />,
      );

      expect(screen.getByText("取引先は必須です")).toBeInTheDocument();
    });
  });

  describe("複数参照モジュール", () => {
    it("モジュール選択ドロップダウンが表示される", async () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="related_id"
          label="関連先"
          referenceModules={["Accounts", "Contacts", "Leads"]}
          onChange={onChange}
        />,
      );

      // モジュール選択用のInputが表示されている（value="Accounts"）
      const moduleInput = screen.getByDisplayValue("Accounts");
      expect(moduleInput).toBeInTheDocument();

      // クリックしてドロップダウンを開く
      await act(async () => {
        fireEvent.click(moduleInput);
      });

      // ドロップダウン内にモジュール一覧が表示される
      expect(screen.getByText("Contacts")).toBeInTheDocument();
      expect(screen.getByText("Leads")).toBeInTheDocument();
    });

    it("モジュール変更で選択がクリアされる", async () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="related_id"
          label="関連先"
          referenceModules={["Accounts", "Contacts"]}
          value="1"
          displayValue="テスト企業A"
          onChange={onChange}
        />,
      );

      // モジュール選択用のInputをクリックしてドロップダウンを開く
      const moduleInput = screen.getByDisplayValue("Accounts");

      await act(async () => {
        fireEvent.click(moduleInput);
      });

      // Contactsを選択
      await act(async () => {
        fireEvent.click(screen.getByText("Contacts"));
      });

      // クリアされてonChangeが呼ばれる
      expect(onChange).toHaveBeenCalledWith("related_id", "");
    });

    it("initialModuleが参照先に含まれる場合はそのモジュールが選択される", () => {
      render(
        <ReferenceField
          name="parent_id"
          label="関連"
          referenceModules={["Accounts", "Potentials", "HelpDesk"]}
          value="36"
          displayValue="テスト案件"
          initialModule="Potentials"
          onChange={vi.fn()}
        />,
      );

      expect(screen.getByDisplayValue("Potentials")).toBeInTheDocument();
      expect(screen.getByDisplayValue("テスト案件")).toBeInTheDocument();
    });

    it("initialModuleが参照先に含まれない場合は先頭のモジュールが選択される", () => {
      render(
        <ReferenceField
          name="parent_id"
          label="関連"
          referenceModules={["Accounts", "Potentials"]}
          initialModule="Leads"
          onChange={vi.fn()}
        />,
      );

      expect(screen.getByDisplayValue("Accounts")).toBeInTheDocument();
    });

    it("initialModuleが後から渡された場合に選択モジュールが追従する", () => {
      const { rerender } = render(
        <ReferenceField
          name="parent_id"
          label="関連"
          referenceModules={["Accounts", "Potentials"]}
          onChange={vi.fn()}
        />,
      );
      expect(screen.getByDisplayValue("Accounts")).toBeInTheDocument();

      rerender(
        <ReferenceField
          name="parent_id"
          label="関連"
          referenceModules={["Accounts", "Potentials"]}
          value="36"
          displayValue="テスト案件"
          initialModule="Potentials"
          onChange={vi.fn()}
        />,
      );

      expect(screen.getByDisplayValue("Potentials")).toBeInTheDocument();
    });

    it("initialModuleが変わらない再描画ではユーザーが選んだモジュールを維持する", async () => {
      const props = {
        name: "parent_id",
        label: "関連",
        referenceModules: ["Accounts", "Potentials", "HelpDesk"],
        initialModule: "Potentials",
        onChange: vi.fn(),
      };
      const { rerender } = render(<ReferenceField {...props} />);

      await act(async () => {
        fireEvent.click(screen.getByDisplayValue("Potentials"));
      });
      await act(async () => {
        fireEvent.click(screen.getByText("HelpDesk"));
      });
      expect(screen.getByDisplayValue("HelpDesk")).toBeInTheDocument();

      // referenceModules が新しい配列になっても、initialModule が同じなら上書きしない
      rerender(
        <ReferenceField
          {...props}
          referenceModules={["Accounts", "Potentials", "HelpDesk"]}
        />,
      );
      expect(screen.getByDisplayValue("HelpDesk")).toBeInTheDocument();
    });
  });

  describe("検索機能", () => {
    it("フォーカス時に検索が実行される", async () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
        />,
      );

      const input = screen.getByPlaceholderText("取引先を検索...");

      await act(async () => {
        fireEvent.focus(input);
        await vi.advanceTimersByTimeAsync(100);
      });

      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining("api=SearchRecords"),
        expect.any(Object),
      );
    });

    it("検索結果がドロップダウンに表示される", async () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
        />,
      );

      const input = screen.getByPlaceholderText("取引先を検索...");

      await act(async () => {
        fireEvent.focus(input);
        await vi.advanceTimersByTimeAsync(100);
      });

      expect(screen.getByText("テスト企業A")).toBeInTheDocument();
      expect(screen.getByText("テスト企業B")).toBeInTheDocument();
      expect(screen.getByText("テスト企業C")).toBeInTheDocument();
    });

    it("入力でデバウンス検索が実行される", async () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
        />,
      );

      const input = screen.getByPlaceholderText("取引先を検索...");

      await act(async () => {
        fireEvent.focus(input);
        await vi.advanceTimersByTimeAsync(100);
      });

      // 最初のフォーカス時の呼び出しをクリア
      mockFetch.mockClear();

      // 入力
      await act(async () => {
        fireEvent.change(input, { target: { value: "テスト" } });
        // デバウンス時間経過後
        await vi.advanceTimersByTimeAsync(350);
      });

      expect(mockFetch).toHaveBeenLastCalledWith(
        expect.stringContaining("search=%E3%83%86%E3%82%B9%E3%83%88"),
        expect.any(Object),
      );
    });
  });

  describe("レコード選択", () => {
    it("レコードをクリックでonChangeが呼ばれる", async () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
        />,
      );

      const input = screen.getByPlaceholderText("取引先を検索...");

      await act(async () => {
        fireEvent.focus(input);
        await vi.advanceTimersByTimeAsync(100);
      });

      expect(screen.getByText("テスト企業A")).toBeInTheDocument();

      await act(async () => {
        fireEvent.click(screen.getByText("テスト企業A"));
      });

      expect(onChange).toHaveBeenCalledWith("account_id", "1", {
        id: "1",
        label: "テスト企業A",
        module: "Accounts",
      });
    });

    it("選択後に入力フィールドに表示される", async () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
        />,
      );

      const input = screen.getByPlaceholderText("取引先を検索...");

      await act(async () => {
        fireEvent.focus(input);
        await vi.advanceTimersByTimeAsync(100);
      });

      expect(screen.getByText("テスト企業A")).toBeInTheDocument();

      await act(async () => {
        fireEvent.click(screen.getByText("テスト企業A"));
      });

      expect(input).toHaveValue("テスト企業A");
    });
  });

  describe("クリア機能", () => {
    it("クリアボタンで選択がクリアされる", async () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          value="1"
          displayValue="テスト企業A"
          onChange={onChange}
        />,
      );

      // クリアボタン（Xアイコン）を取得 - p-1 hover:bg-gray-100 rounded クラスのボタン
      const clearButtons = screen.getAllByRole("button");
      const clearButton = clearButtons.find((btn) =>
        btn.className.includes("hover:bg-gray-100"),
      );

      expect(clearButton).toBeDefined();

      await act(async () => {
        fireEvent.click(clearButton!);
      });

      expect(onChange).toHaveBeenCalledWith("account_id", "");
    });
  });

  describe("Drawerボタン", () => {
    it("検索ボタンが表示される", () => {
      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
        />,
      );

      // Search icon button
      const buttons = screen.getAllByRole("button");
      expect(buttons.length).toBeGreaterThanOrEqual(1);
    });
  });

  describe("検索結果なし", () => {
    it("検索結果がない場合メッセージが表示される", async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ records: [] }),
      });

      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
        />,
      );

      const input = screen.getByPlaceholderText("取引先を検索...");

      await act(async () => {
        fireEvent.focus(input);
        await vi.advanceTimersByTimeAsync(100);
      });

      expect(screen.getByText("レコードがありません")).toBeInTheDocument();
    });
  });

  describe("APIエラー", () => {
    it("APIエラー時は検索結果が空になる", async () => {
      mockFetch.mockRejectedValue(new Error("Network error"));
      const consoleSpy = vi
        .spyOn(console, "error")
        .mockImplementation(() => {});

      const onChange = vi.fn();

      render(
        <ReferenceField
          name="account_id"
          label="取引先"
          referenceModules={["Accounts"]}
          onChange={onChange}
        />,
      );

      const input = screen.getByPlaceholderText("取引先を検索...");

      await act(async () => {
        fireEvent.focus(input);
        await vi.advanceTimersByTimeAsync(100);
      });

      expect(consoleSpy).toHaveBeenCalled();

      consoleSpy.mockRestore();
    });
  });
});
