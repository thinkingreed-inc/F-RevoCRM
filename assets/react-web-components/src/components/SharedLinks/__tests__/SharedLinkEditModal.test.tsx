import React from "react";
import { describe, expect, it, vi } from "vitest";
import { render, screen, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import type {
  SharedLink,
  SharedLinkInfo,
  SharedLinkInput,
  SharedLinkSaveResult,
} from "@/types/sharedLinks";
import { SharedLinkEditModal } from "../SharedLinkEditModal";

/**
 * 共有リンクの編集モーダルのテスト
 *
 * 文言はキーをそのまま出す（labels を空にしている）。
 * 仕様: docs/specs/shared_links_widget.md 3.1 / 3.3 / 3.4 / 4
 */

const INFO: SharedLinkInfo = {
  user_fields: [
    { name: "id", label: "ユーザー ID" },
    { name: "email1", label: "メールアドレス" },
    { name: "user_name", label: "ユーザー名" },
  ],
  roles: [
    { id: "H1", name: "Organization", depth: 0 },
    { id: "H2", name: "CEO", depth: 1 },
    { id: "H3", name: "営業部長", depth: 2 },
  ],
  groups: [{ id: "10", name: "営業チーム" }],
  users: [
    { id: "1", name: "管理者" },
    { id: "5", name: "山田 太郎" },
  ],
  limits: {
    title_max: 100,
    url_max: 2000,
    description_max: 500,
    params_max: 20,
    param_name_max: 100,
    param_value_max: 1000,
    window_min: 200,
    window_max: 4000,
    default_window_width: 1024,
    default_window_height: 768,
  },
  labels: {},
};

const savedLink = (overrides: Partial<SharedLink> = {}): SharedLink => ({
  id: 7,
  sequence: 1,
  title: "勤怠",
  url: "https://kintai.example.com/",
  description: "",
  method: "GET",
  open_mode: "tab",
  window_width: null,
  window_height: null,
  noreferrer: false,
  active: true,
  visibility: "all",
  params: [],
  targets: [],
  has_warning: false,
  post_blocked: false,
  ...overrides,
});

const setup = (
  link: SharedLink | null = null,
  result: SharedLinkSaveResult = { saved: true, id: 1 },
) => {
  const onSave = vi.fn(
    async (input: SharedLinkInput): Promise<SharedLinkSaveResult> => {
      void input;
      return result;
    },
  );
  const onClose = vi.fn();
  const user = userEvent.setup();
  render(
    <SharedLinkEditModal
      isOpen
      info={INFO}
      link={link}
      isSaving={false}
      onSave={onSave}
      onClose={onClose}
    />,
  );
  return { onSave, onClose, user };
};

const fillRequired = async (user: ReturnType<typeof userEvent.setup>) => {
  await user.type(screen.getByLabelText(/LBL_TITLE/), "勤怠システム");
  await user.type(
    screen.getByLabelText(/LBL_URL/),
    "https://kintai.example.com/",
  );
};

const submit = (user: ReturnType<typeof userEvent.setup>) =>
  user.click(screen.getByRole("button", { name: "LBL_SAVE" }));

const paramRows = () => screen.queryAllByTestId("shared-link-param-row");

describe("SharedLinkEditModal", () => {
  it("isOpen が false なら何も描画しない", () => {
    const { container } = render(
      <SharedLinkEditModal
        isOpen={false}
        info={INFO}
        link={null}
        isSaving={false}
        onSave={vi.fn()}
        onClose={vi.fn()}
      />,
    );
    expect(container).toBeEmptyDOMElement();
  });

  it("TC-UI-054 新規追加の既定値で保存する（GET・別タブ・全員・有効）", async () => {
    const { onSave, user } = setup();
    await fillRequired(user);
    await submit(user);

    expect(onSave).toHaveBeenCalledTimes(1);
    expect(onSave.mock.calls[0][0]).toEqual({
      title: "勤怠システム",
      url: "https://kintai.example.com/",
      description: "",
      method: "GET",
      open_mode: "tab",
      window_width: null,
      window_height: null,
      noreferrer: false,
      active: true,
      visibility: "all",
      params: [],
      targets: [],
    });
  });

  it("TC-UI-050 必須が空なら保存せず、項目の下にエラーを出す", async () => {
    const { onSave, user } = setup();
    await submit(user);

    expect(onSave).not.toHaveBeenCalled();
    expect(screen.getByText("LBL_ERROR_TITLE_REQUIRED")).toBeInTheDocument();
    expect(screen.getByText("LBL_ERROR_URL_REQUIRED")).toBeInTheDocument();
    expect(screen.getByText("LBL_ERROR_HAS_ERRORS")).toBeInTheDocument();
  });

  it("TC-UI-050 TC-VAL-014 javascript: の URL は保存しない", async () => {
    const { onSave, user } = setup();
    await user.type(screen.getByLabelText(/LBL_TITLE/), "x");
    await user.type(screen.getByLabelText(/LBL_URL/), "javascript:alert(1)");
    await submit(user);

    expect(onSave).not.toHaveBeenCalled();
    expect(screen.getByText("LBL_ERROR_URL_INVALID")).toBeInTheDocument();
  });

  it("TC-UI-024 POST を選ぶと、送り先の登録が必要なことを注記する", async () => {
    const { user } = setup();
    expect(screen.queryByText("LBL_POST_CSP_NOTE")).toBeNull();

    await user.click(screen.getByLabelText("POST"));
    expect(screen.getByRole("note")).toHaveTextContent("LBL_POST_CSP_NOTE");

    await user.click(screen.getByLabelText("GET"));
    expect(screen.queryByText("LBL_POST_CSP_NOTE")).toBeNull();
  });

  describe("開き方", () => {
    it("TC-UI-020 TC-UI-021 幅・高さは別ウィンドウのときだけ出し、既定のサイズを入れる", async () => {
      const { onSave, user } = setup();
      expect(screen.queryByLabelText(/LBL_WINDOW_WIDTH/)).toBeNull();

      await user.click(screen.getByLabelText("LBL_OPEN_MODE_WINDOW"));
      expect(screen.getByLabelText(/LBL_WINDOW_WIDTH/)).toHaveValue(1024);
      expect(screen.getByLabelText(/LBL_WINDOW_HEIGHT/)).toHaveValue(768);

      await fillRequired(user);
      const width = screen.getByLabelText(/LBL_WINDOW_WIDTH/);
      await user.clear(width);
      await user.type(width, "800");
      await submit(user);

      expect(onSave.mock.calls[0][0]).toMatchObject({
        open_mode: "window",
        window_width: 800,
        window_height: 768,
      });
    });

    it("TC-UI-023 範囲外のサイズは保存しない", async () => {
      const { onSave, user } = setup();
      await fillRequired(user);
      await user.click(screen.getByLabelText("LBL_OPEN_MODE_WINDOW"));
      const height = screen.getByLabelText(/LBL_WINDOW_HEIGHT/);
      await user.clear(height);
      await user.type(height, "199");
      await submit(user);

      expect(onSave).not.toHaveBeenCalled();
      expect(screen.getByText("LBL_ERROR_WINDOW_SIZE")).toBeInTheDocument();
    });

    it("TC-UI-022 別タブに戻したらサイズは送らない", async () => {
      const { onSave, user } = setup();
      await fillRequired(user);
      await user.click(screen.getByLabelText("LBL_OPEN_MODE_WINDOW"));
      await user.click(screen.getByLabelText("LBL_OPEN_MODE_TAB"));
      expect(screen.queryByLabelText(/LBL_WINDOW_WIDTH/)).toBeNull();
      await submit(user);

      expect(onSave.mock.calls[0][0]).toMatchObject({
        open_mode: "tab",
        window_width: null,
        window_height: null,
      });
    });
  });

  describe("リクエストパラメータ", () => {
    it("TC-UI-036 行を追加・削除できる", async () => {
      const { user } = setup();
      await user.click(screen.getByRole("button", { name: /LBL_ADD_PARAM/ }));
      await user.click(screen.getByRole("button", { name: /LBL_ADD_PARAM/ }));
      expect(paramRows()).toHaveLength(2);

      await user.type(
        within(paramRows()[0]).getByLabelText("LBL_PARAM_NAME"),
        "first",
      );
      await user.click(
        within(paramRows()[1]).getByRole("button", { name: "LBL_REMOVE" }),
      );

      expect(paramRows()).toHaveLength(1);
      expect(
        within(paramRows()[0]).getByLabelText("LBL_PARAM_NAME"),
      ).toHaveValue("first");
    });

    it("TC-UI-035 20 行に達したら追加できない", async () => {
      const { user } = setup();
      const add = screen.getByRole("button", { name: /LBL_ADD_PARAM/ });
      for (let i = 0; i < 20; i++) {
        await user.click(add);
      }
      expect(paramRows()).toHaveLength(20);
      expect(add).toBeDisabled();
    });

    it("TC-UI-030 TC-UI-031 ユーザー情報を選ぶと、値の欄が項目のプルダウンになり、記法を手で書かずに保存できる", async () => {
      const { onSave, user } = setup();
      await fillRequired(user);
      await user.click(screen.getByRole("button", { name: /LBL_ADD_PARAM/ }));
      const row = paramRows()[0];
      await user.type(within(row).getByLabelText("LBL_PARAM_NAME"), "mail");

      await user.click(within(row).getByLabelText("LBL_VALUE_TYPE_USER"));
      expect(
        within(row).queryByRole("textbox", { name: "LBL_PARAM_VALUE" }),
      ).toBeNull();
      const select = within(row).getByRole("combobox", {
        name: "LBL_PARAM_VALUE",
      });
      // 内部の項目名ではなく表示ラベルで並ぶ
      expect(
        within(select).getByRole("option", { name: "メールアドレス" }),
      ).toBeInTheDocument();

      await user.selectOptions(select, "email1");
      await submit(user);

      expect(onSave.mock.calls[0][0].params).toEqual([
        { name: "mail", value_type: "user", value: "email1" },
      ]);
    });

    it("TC-UI-034 ユーザー情報の項目を選んでいなければ保存しない", async () => {
      const { onSave, user } = setup();
      await fillRequired(user);
      await user.click(screen.getByRole("button", { name: /LBL_ADD_PARAM/ }));
      const row = paramRows()[0];
      await user.type(within(row).getByLabelText("LBL_PARAM_NAME"), "mail");
      await user.click(within(row).getByLabelText("LBL_VALUE_TYPE_USER"));
      await submit(user);

      expect(onSave).not.toHaveBeenCalled();
      expect(
        within(row).getByText("LBL_ERROR_USER_FIELD_REQUIRED"),
      ).toBeInTheDocument();
    });

    it("TC-UI-032 値の種類を切り替えたら値を空に戻す", async () => {
      const { user } = setup();
      await user.click(screen.getByRole("button", { name: /LBL_ADD_PARAM/ }));
      const row = paramRows()[0];
      await user.type(within(row).getByLabelText("LBL_PARAM_VALUE"), "固定");
      await user.click(within(row).getByLabelText("LBL_VALUE_TYPE_USER"));
      expect(
        within(row).getByRole("combobox", { name: "LBL_PARAM_VALUE" }),
      ).toHaveValue("");

      await user.selectOptions(
        within(row).getByRole("combobox", { name: "LBL_PARAM_VALUE" }),
        "id",
      );
      await user.click(within(row).getByLabelText("LBL_VALUE_TYPE_FIXED"));
      expect(
        within(row).getByRole("textbox", { name: "LBL_PARAM_VALUE" }),
      ).toHaveValue("");
    });

    it("TC-UI-033 絞り込み欄に入力すると、項目を表示名で絞り込める", async () => {
      const { user } = setup();
      await user.click(screen.getByRole("button", { name: /LBL_ADD_PARAM/ }));
      const row = paramRows()[0];
      await user.click(within(row).getByLabelText("LBL_VALUE_TYPE_USER"));
      await user.type(within(row).getByRole("searchbox"), "メール");

      const options = within(
        within(row).getByRole("combobox", { name: "LBL_PARAM_VALUE" }),
      ).getAllByRole("option");
      expect(options.map((o) => o.textContent)).toEqual([
        "LBL_SELECT_USER_FIELD",
        "メールアドレス",
      ]);
    });

    it("TC-UI-037 削除された項目を選んでいる行は、削除された項目と表示する", () => {
      setup(
        savedLink({
          params: [
            {
              name: "x",
              value_type: "user",
              value: "cf_deleted",
              label: null,
              missing: true,
            },
          ],
        }),
      );
      const select = within(paramRows()[0]).getByRole("combobox", {
        name: "LBL_PARAM_VALUE",
      });
      expect(select).toHaveValue("cf_deleted");
      expect(
        within(select).getByRole("option", { name: "LBL_DELETED_FIELD" }),
      ).toBeInTheDocument();
    });
  });

  describe("公開範囲", () => {
    const chips = () => screen.getByTestId("shared-link-targets");

    it("TC-UI-040 対象の欄は「指定した対象のみ」のときだけ出す", async () => {
      const { user } = setup();
      expect(screen.queryByLabelText("LBL_TARGET_TYPE")).toBeNull();
      await user.click(screen.getByLabelText("LBL_VISIBILITY_TARGETS"));
      expect(screen.getByLabelText("LBL_TARGET_TYPE")).toBeInTheDocument();
    });

    it("TC-UI-041 TC-UI-042 「下位の役割も含む」は種類が役割のときだけ出す", async () => {
      const { user } = setup();
      await user.click(screen.getByLabelText("LBL_VISIBILITY_TARGETS"));
      expect(
        screen.getByLabelText("LBL_INCLUDE_SUBORDINATES"),
      ).toBeInTheDocument();

      await user.selectOptions(
        screen.getByLabelText("LBL_TARGET_TYPE"),
        "Groups",
      );
      expect(screen.queryByLabelText("LBL_INCLUDE_SUBORDINATES")).toBeNull();
      await user.selectOptions(
        screen.getByLabelText("LBL_TARGET_TYPE"),
        "Users",
      );
      expect(screen.queryByLabelText("LBL_INCLUDE_SUBORDINATES")).toBeNull();
    });

    it("TC-UI-047 役割・役割と下位・グループ・ユーザーを追加して保存する", async () => {
      const { onSave, user } = setup();
      await fillRequired(user);
      await user.click(screen.getByLabelText("LBL_VISIBILITY_TARGETS"));
      const target = screen.getByRole("combobox", { name: "LBL_TARGET" });
      const add = screen.getByRole("button", { name: "LBL_ADD_TARGET" });

      await user.selectOptions(target, "H3");
      await user.click(add);
      await user.selectOptions(target, "H2");
      await user.click(screen.getByLabelText("LBL_INCLUDE_SUBORDINATES"));
      await user.click(add);
      await user.selectOptions(
        screen.getByLabelText("LBL_TARGET_TYPE"),
        "Groups",
      );
      await user.selectOptions(
        screen.getByRole("combobox", { name: "LBL_TARGET" }),
        "10",
      );
      await user.click(add);
      await user.selectOptions(
        screen.getByLabelText("LBL_TARGET_TYPE"),
        "Users",
      );
      await user.selectOptions(
        screen.getByRole("combobox", { name: "LBL_TARGET" }),
        "5",
      );
      await user.click(add);

      expect(chips()).toHaveTextContent("LBL_TARGET_ROLES: 営業部長");
      expect(chips()).toHaveTextContent(
        "LBL_TARGET_ROLE_AND_SUBORDINATES: CEO",
      );
      expect(chips()).toHaveTextContent("LBL_TARGET_GROUPS: 営業チーム");
      expect(chips()).toHaveTextContent("LBL_TARGET_USERS: 山田 太郎");

      await submit(user);
      expect(onSave.mock.calls[0][0]).toMatchObject({
        visibility: "targets",
        targets: [
          { type: "Roles", id: "H3" },
          { type: "RoleAndSubordinates", id: "H2" },
          { type: "Groups", id: "10" },
          { type: "Users", id: "5" },
        ],
      });
    });

    it("TC-UI-044 TC-UI-045 TC-UI-046 同じ対象は重ねて追加できないが、役割と役割と下位は両方追加できる", async () => {
      const { user } = setup();
      await user.click(screen.getByLabelText("LBL_VISIBILITY_TARGETS"));
      const target = screen.getByRole("combobox", { name: "LBL_TARGET" });
      const add = screen.getByRole("button", { name: "LBL_ADD_TARGET" });

      await user.selectOptions(target, "H2");
      await user.click(add);
      await user.selectOptions(target, "H2");
      expect(add).toBeDisabled();

      await user.click(screen.getByLabelText("LBL_INCLUDE_SUBORDINATES"));
      expect(add).toBeEnabled();
      await user.click(add);
      expect(within(chips()).getAllByRole("button")).toHaveLength(2);
    });

    it("TC-UI-043 対象を選ばなければ追加できない", async () => {
      const { user } = setup();
      await user.click(screen.getByLabelText("LBL_VISIBILITY_TARGETS"));
      expect(
        screen.getByRole("button", { name: "LBL_ADD_TARGET" }),
      ).toBeDisabled();
    });

    it("TC-UI-048 チップの × で外せる", async () => {
      const { user } = setup(
        savedLink({
          visibility: "targets",
          targets: [
            { type: "Groups", id: "10", name: "営業チーム", deleted: false },
            { type: "Users", id: "5", name: "山田 太郎", deleted: false },
          ],
        }),
      );
      await user.click(
        screen.getByRole("button", { name: "LBL_REMOVE 営業チーム" }),
      );

      expect(chips()).not.toHaveTextContent("営業チーム");
      expect(chips()).toHaveTextContent("山田 太郎");
    });

    it("TC-UI-050 TC-VAL-041 対象が 0 件なら保存しない", async () => {
      const { onSave, user } = setup();
      await fillRequired(user);
      await user.click(screen.getByLabelText("LBL_VISIBILITY_TARGETS"));
      await submit(user);

      expect(onSave).not.toHaveBeenCalled();
      expect(
        screen.getByText("LBL_ERROR_TARGETS_REQUIRED"),
      ).toBeInTheDocument();
    });

    it("TC-UI-049 削除された対象は削除済みと表示し、無効になったユーザーは保存済みの名前で表示する", () => {
      setup(
        savedLink({
          visibility: "targets",
          targets: [
            { type: "Groups", id: "99", name: null, deleted: true },
            { type: "Users", id: "8", name: "退職 花子", deleted: false },
          ],
        }),
      );

      expect(chips()).toHaveTextContent(
        "LBL_TARGET_GROUPS: LBL_DELETED_TARGET",
      );
      expect(chips()).toHaveTextContent("LBL_TARGET_USERS: 退職 花子");
    });

    it("TC-UI-04A 全員に戻したら対象は送らない", async () => {
      const { onSave, user } = setup(
        savedLink({
          visibility: "targets",
          targets: [
            { type: "Groups", id: "10", name: "営業チーム", deleted: false },
          ],
        }),
      );
      await user.click(screen.getByLabelText("LBL_VISIBILITY_ALL"));
      await submit(user);

      expect(onSave.mock.calls[0][0]).toMatchObject({
        visibility: "all",
        targets: [],
      });
    });
  });

  it("TC-UI-055 編集時は保存済みの内容を表示し、変更して保存できる", async () => {
    const { onSave, user } = setup(
      savedLink({ noreferrer: true, active: false, method: "POST" }),
    );
    expect(screen.getByLabelText(/LBL_TITLE/)).toHaveValue("勤怠");
    expect(screen.getByLabelText("POST")).toBeChecked();
    expect(screen.getByLabelText("LBL_NOREFERRER")).toBeChecked();
    expect(screen.getByLabelText("LBL_ACTIVE")).not.toBeChecked();

    await user.click(screen.getByLabelText("LBL_NOREFERRER"));
    await user.click(screen.getByLabelText("LBL_ACTIVE"));
    await submit(user);

    expect(onSave.mock.calls[0][0]).toMatchObject({
      method: "POST",
      noreferrer: false,
      active: true,
    });
  });

  it("TC-UI-052 サーバーが返した項目ごとのエラーを表示する", async () => {
    const { user } = setup(null, {
      saved: false,
      errors: {
        url: "サーバーの URL エラー",
        "targets.0": "対象が見つかりません",
      },
    });
    await fillRequired(user);
    await submit(user);

    expect(
      await screen.findByText("サーバーの URL エラー"),
    ).toBeInTheDocument();
    expect(screen.getByText("LBL_ERROR_HAS_ERRORS")).toBeInTheDocument();
  });

  it("TC-UI-053 保存でエラーが起きたらメッセージを出す", async () => {
    const onSave = vi.fn(async () => {
      throw new Error("通信に失敗しました");
    });
    const user = userEvent.setup();
    render(
      <SharedLinkEditModal
        isOpen
        info={INFO}
        link={null}
        isSaving={false}
        onSave={onSave}
        onClose={vi.fn()}
      />,
    );
    await fillRequired(user);
    await submit(user);

    expect(await screen.findByText("通信に失敗しました")).toBeInTheDocument();
  });

  it("TC-UI-056 キャンセル・×・背景のクリックで閉じ、ダイアログの中のクリックでは閉じない", async () => {
    const { onClose, user } = setup();
    await user.click(screen.getByLabelText(/LBL_TITLE/));
    expect(onClose).not.toHaveBeenCalled();

    await user.click(screen.getByRole("button", { name: "LBL_CANCEL" }));
    expect(onClose).toHaveBeenCalledTimes(1);

    await user.click(screen.getByRole("button", { name: "LBL_CLOSE" }));
    expect(onClose).toHaveBeenCalledTimes(2);

    const backdrop = screen.getByRole("dialog").parentElement;
    expect(backdrop).not.toBeNull();
    await user.click(backdrop as HTMLElement);
    expect(onClose).toHaveBeenCalledTimes(3);
  });
  describe("切り替えたときの値", () => {
    it("TC-UI-020 入力済みの幅・高さは、別タブと別ウィンドウを行き来しても上書きしない", async () => {
      const { onSave, user } = setup();
      await fillRequired(user);
      await user.click(screen.getByLabelText("LBL_OPEN_MODE_WINDOW"));
      const width = screen.getByLabelText(/LBL_WINDOW_WIDTH/);
      await user.clear(width);
      await user.type(width, "640");
      await user.click(screen.getByLabelText("LBL_OPEN_MODE_TAB"));
      await user.click(screen.getByLabelText("LBL_OPEN_MODE_WINDOW"));

      expect(screen.getByLabelText(/LBL_WINDOW_WIDTH/)).toHaveValue(640);
      await submit(user);
      expect(onSave.mock.calls[0][0]).toMatchObject({
        open_mode: "window",
        window_width: 640,
        window_height: 768,
      });
    });

    it("TC-UI-04B 対象の種類を切り替えると、選んだ対象と「下位の役割も含む」が戻る", async () => {
      const { user } = setup();
      await user.click(screen.getByLabelText("LBL_VISIBILITY_TARGETS"));
      await user.selectOptions(
        screen.getByRole("combobox", { name: "LBL_TARGET" }),
        "H2",
      );
      await user.click(screen.getByLabelText("LBL_INCLUDE_SUBORDINATES"));

      await user.selectOptions(
        screen.getByLabelText("LBL_TARGET_TYPE"),
        "Groups",
      );
      expect(screen.getByRole("combobox", { name: "LBL_TARGET" })).toHaveValue(
        "",
      );
      await user.selectOptions(
        screen.getByLabelText("LBL_TARGET_TYPE"),
        "Roles",
      );
      expect(screen.getByRole("combobox", { name: "LBL_TARGET" })).toHaveValue(
        "",
      );
      expect(
        screen.getByLabelText("LBL_INCLUDE_SUBORDINATES"),
      ).not.toBeChecked();
      expect(
        screen.getByRole("button", { name: "LBL_ADD_TARGET" }),
      ).toBeDisabled();
    });

    it("TC-UI-033 絞り込みで外れても、選んでいる値は選択肢に残る", async () => {
      const { user } = setup();
      await user.click(screen.getByRole("button", { name: /LBL_ADD_PARAM/ }));
      const row = paramRows()[0];
      await user.click(within(row).getByLabelText("LBL_VALUE_TYPE_USER"));
      const select = within(row).getByRole("combobox", {
        name: "LBL_PARAM_VALUE",
      });
      await user.selectOptions(select, "user_name");
      await user.type(within(row).getByRole("searchbox"), "メール");

      expect(select).toHaveValue("user_name");
      expect(
        within(select)
          .getAllByRole("option")
          .map((o) => o.textContent),
      ).toEqual(["LBL_SELECT_USER_FIELD", "メールアドレス", "ユーザー名"]);
    });

    it("TC-UI-041 役割は階層の深さに応じて字下げして並ぶ", async () => {
      const { user } = setup();
      await user.click(screen.getByLabelText("LBL_VISIBILITY_TARGETS"));
      const options = within(
        screen.getByRole("combobox", { name: "LBL_TARGET" }),
      ).getAllByRole("option");

      expect(options.map((o) => o.textContent)).toEqual([
        "LBL_SELECT_TARGET",
        "Organization",
        "CEO",
        "\u3000営業部長",
      ]);
    });
  });
});
