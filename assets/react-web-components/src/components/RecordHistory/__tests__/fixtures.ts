import type { GetHistoryResponse, HistoryValue } from "@/types/history";

const v = (raw: string, display = raw): HistoryValue => ({ raw, display });

/** e2e dump 環境のシード（ミライ精密工業株式会社）を縮めた履歴 */
export const historyFixture: GetHistoryResponse = {
  success: true,
  now: "2026-11-02T16:00:00",
  format: { dateFormat: "yyyy-mm-dd", hourFormat: "24" },
  users: {
    "1": { id: 1, name: "システム管理者", imageUrl: null },
    "5": { id: 5, name: "山田 太郎", imageUrl: null },
    "6": { id: 6, name: "佐藤 花子", imageUrl: null },
  },
  fields: {
    phone: {
      name: "phone",
      label: "代表電話番号",
      type: "phone",
      system: false,
      order: 3,
    },
    rating: {
      name: "rating",
      label: "格付け",
      type: "picklist",
      system: false,
      order: 13,
    },
    annual_revenue: {
      name: "annual_revenue",
      label: "年間売上高",
      type: "currency",
      system: false,
      order: 17,
    },
    description: {
      name: "description",
      label: "詳細内容",
      type: "text",
      system: false,
      order: 40,
    },
    website: {
      name: "website",
      label: "Webサイト",
      type: "url",
      system: false,
      order: 2,
    },
    modifiedby: {
      name: "modifiedby",
      label: "最終更新者",
      type: "owner",
      system: true,
      order: 22,
    },
  },
  events: [
    {
      id: 8,
      type: "update",
      userId: 5,
      changedOn: "2026-11-01T09:30:00",
      changes: [
        { field: "phone", before: v("03-1111-2222"), after: v("03-3333-4444") },
        {
          field: "modifiedby",
          before: v("6", "佐藤 花子"),
          after: v("5", "山田 太郎"),
        },
      ],
    },
    {
      id: 7,
      type: "update",
      userId: 6,
      changedOn: "2026-10-28T16:03:00",
      changes: [
        {
          field: "website",
          before: v("https://mirai.example.jp"),
          after: null,
        },
        {
          field: "rating",
          before: v("Market Failed", "市場失敗"),
          after: v("Active", "有効"),
        },
      ],
    },
    {
      id: 101,
      type: "link",
      userId: 5,
      changedOn: "2026-10-20T13:15:00",
      changes: [],
      related: {
        module: "Potentials",
        moduleLabel: "商談",
        recordId: 50,
        label: "2027年度 保守契約更新",
        url: "index.php?module=Potentials&view=Detail&record=50",
      },
    },
    {
      id: 6,
      type: "update",
      userId: 5,
      changedOn: "2026-10-15T11:48:00",
      changes: [
        { field: "phone", before: v("03-9876-5432"), after: v("03-1111-2222") },
        {
          field: "annual_revenue",
          before: v("500000000.00000000", "500,000,000.00"),
          after: v("720000000.00000000", "720,000,000.00"),
          delta: { sign: "+", display: "220,000,000.00", percent: 44 },
        },
      ],
    },
    {
      id: 4,
      type: "update",
      userId: 6,
      changedOn: "2026-09-18T09:05:00",
      changes: [
        {
          field: "description",
          before: v("担当窓口は総務部。"),
          after: v("担当窓口は購買部。"),
        },
      ],
    },
    {
      id: 2,
      type: "create",
      userId: 1,
      changedOn: "2026-09-01T10:12:00",
      changes: [
        { field: "phone", before: null, after: v("03-9876-5432") },
        {
          field: "rating",
          before: null,
          after: v("Market Failed", "市場失敗"),
        },
        { field: "modifiedby", before: null, after: v("1", "システム管理者") },
      ],
    },
  ],
};
