import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import { DeadlineStatusBadge } from "../DeadlineStatusBadge";
import { TranslationProvider } from "../../../contexts/TranslationContext";
import type { DeadlineStatus } from "../types/documents";

/**
 * 入力期限状態のバッジ（TS-03 / TS-10 TC-UI-069）
 *
 * 状態が色だけでなく文字でも区別できることを担保する。
 * 色覚特性や白黒印刷でも判別できる必要があるため、ラベル文字は必須。
 */

const TRANSLATIONS: Record<string, string> = {
  LBL_DEADLINE_WITHIN: "期限内",
  LBL_DEADLINE_WARNING: "期限間近",
  LBL_DEADLINE_OVERDUE: "期限超過",
  LBL_INPUT_DEADLINE: "入力期限",
};

function renderBadge(
  status: DeadlineStatus | null | undefined,
  deadline?: string | null,
) {
  return render(
    <TranslationProvider module="Documents" initialTranslations={TRANSLATIONS}>
      <DeadlineStatusBadge status={status} deadline={deadline} />
    </TranslationProvider>,
  );
}

describe("DeadlineStatusBadge", () => {
  it("状態ごとにラベル文字を出す（色だけに依存しない）", () => {
    const cases: Array<[DeadlineStatus, string]> = [
      ["within", "期限内"],
      ["warning", "期限間近"],
      ["overdue", "期限超過"],
    ];
    for (const [status, label] of cases) {
      const { unmount } = renderBadge(status, "2026-08-25");
      expect(screen.getByText(label)).toBeTruthy();
      unmount();
    }
  });

  it("状態ごとに文字色と背景色を変える", () => {
    const { container: within } = renderBadge("within", "2026-09-01");
    const { container: overdue } = renderBadge("overdue", "2026-08-20");
    const withinStyle = within.querySelector("span")!.getAttribute("style");
    const overdueStyle = overdue.querySelector("span")!.getAttribute("style");
    expect(withinStyle).not.toBe(overdueStyle);
  });

  it("期限日を渡すとツールチップに出す", () => {
    renderBadge("overdue", "2026-08-14 00:00:00");
    expect(screen.getByTitle("期限超過（入力期限: 2026-08-14）")).toBeTruthy();
  });

  it("期限日が無ければ状態が入っていても何も出さない", () => {
    // input_deadline_status 列の既定値が 'within' のため、期限を持たない
    // ドキュメントにも状態だけが入っている。状態は期限から導く値なので出さない
    const { container: nullDeadline } = renderBadge("within", null);
    expect(nullDeadline.querySelector("span")).toBeNull();

    const { container: noDeadline } = renderBadge("warning");
    expect(noDeadline.querySelector("span")).toBeNull();

    const { container: emptyDeadline } = renderBadge("overdue", "");
    expect(emptyDeadline.querySelector("span")).toBeNull();
  });

  it("状態が無い（スキャナ保存以外）なら何も出さない", () => {
    const { container: nullCase } = renderBadge(null, "2026-08-25");
    expect(nullCase.querySelector("span")).toBeNull();

    const { container: undefinedCase } = renderBadge(undefined, "2026-08-25");
    expect(undefinedCase.querySelector("span")).toBeNull();

    // サーバーが空文字を返した場合も表示しない
    const { container: emptyCase } = renderBadge(
      "" as unknown as DeadlineStatus,
      "2026-08-25",
    );
    expect(emptyCase.querySelector("span")).toBeNull();
  });
});
