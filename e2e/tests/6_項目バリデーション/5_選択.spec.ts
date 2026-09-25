import { test } from "@playwright/test";
import { readFileSync } from "fs";
import { sessionNameFile } from "../../utils/util";
import { FieldValidation } from "./support/FieldValidation";
import { CASES, FIELD_GROUP, KNOWN_DIVERGENCES } from "./fixtures/validation-matrix";

const GROUP = FIELD_GROUP["選択"];

const divergent = (c: { field: string; scenario: string }) =>
  KNOWN_DIVERGENCES.some((d) => d.field === c.field && d.scenario === c.scenario);

test.describe("項目バリデーション: 選択(選択肢単数/複数/チェック)", () => {
  let fv: FieldValidation;
  test.beforeAll(async () => {
    fv = await FieldValidation.create(readFileSync(sessionNameFile, "utf-8"));
  });

  for (const c of CASES.filter((c) => GROUP.includes(c.field))) {
    test(`${c.field} / ${c.scenario}`, async ({ page }) => {
      test.fixme(
        divergent(c),
        KNOWN_DIVERGENCES.find((d) => d.field === c.field && d.scenario === c.scenario)?.reason
      );
      await fv.runCase(page, c);
    });
  }
});
