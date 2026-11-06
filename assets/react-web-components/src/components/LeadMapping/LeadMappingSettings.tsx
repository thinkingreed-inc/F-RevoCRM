import { useState } from "react";
import { Loader2, XCircle } from "lucide-react";
import { TranslationProvider } from "../../contexts/TranslationContext";
import { useTranslation } from "../../hooks/useTranslation";
import { cn } from "../../lib/utils";
import { useLeadMappingSettings } from "./hooks/useLeadMappingSettings";
import { MappingTab } from "./MappingTab";
import { LayoutTab } from "./LayoutTab";
import { DefaultsTab } from "./DefaultsTab";
import { MatchRulesTab } from "./MatchRulesTab";

type TabKey = "mapping" | "layout" | "defaults" | "match";

const TABS: Array<{ key: TabKey; label: string }> = [
  { key: "mapping", label: "LBL_CONVERT_SETTINGS_TAB_MAPPING" },
  { key: "layout", label: "LBL_CONVERT_SETTINGS_TAB_LAYOUT" },
  { key: "defaults", label: "LBL_CONVERT_SETTINGS_TAB_DEFAULTS" },
  { key: "match", label: "LBL_CONVERT_SETTINGS_TAB_MATCH" },
];

/**
 * リードマッピング設定画面（<lead-mapping-settings>）。設定 > リードマッピング
 */
export function LeadMappingSettings() {
  return (
    <TranslationProvider module="Leads" parent="Settings">
      <LeadMappingSettingsPage />
    </TranslationProvider>
  );
}

function LeadMappingSettingsPage() {
  const { t } = useTranslation();
  const { info, loading, error, save } = useLeadMappingSettings();
  const [tab, setTab] = useState<TabKey>("mapping");

  return (
    <div
      className="mx-auto max-w-[1200px] px-3 py-4 sm:px-4"
      data-testid="lead-mapping-settings"
    >
      <h2 className="mt-0 mb-4 text-xl font-semibold text-gray-800">
        {t("LBL_CONVERT_SETTINGS_TITLE")}
      </h2>
      <div
        role="tablist"
        // overflow-x だけを指定すると縦方向も auto になり、タブの下線が 1px でもはみ出すと
        // 縦スクロールバーが出る。縦は hidden にし、区切り線は内側の影で描いてはみ出さないようにする。
        // スマホ幅でタブが収まらないときは横スクロールだけ許す
        className="mb-4 flex gap-1 overflow-x-auto overflow-y-hidden shadow-[inset_0_-1px_0_#e5e7eb]"
      >
        {TABS.map((item) => (
          <button
            key={item.key}
            type="button"
            role="tab"
            aria-selected={tab === item.key}
            onClick={() => setTab(item.key)}
            className={cn(
              "shrink-0 border-0 border-b-2 bg-transparent px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors",
              tab === item.key
                ? "border-blue-500 font-medium text-blue-600"
                : "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700",
            )}
          >
            {t(item.label)}
          </button>
        ))}
      </div>
      {loading && !info && (
        <div className="flex items-center gap-2 py-8 text-gray-500">
          <Loader2 className="h-5 w-5 animate-spin" aria-hidden="true" />
          {t("LBL_LOADING")}
        </div>
      )}
      {error && (
        <p
          role="alert"
          className="flex items-center gap-2 rounded border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800"
        >
          <XCircle className="h-4 w-4" aria-hidden="true" />
          {t("LBL_CONVERT_SETTINGS_LOAD_FAILED")}: {error}
        </p>
      )}
      {info && (
        <div
          role="tabpanel"
          className="rounded-lg border border-gray-200 bg-white px-4 pt-4"
        >
          {/* 各タブは表示中だけマウントする（タブを切り替えると保存済みの内容から作り直す） */}
          {tab === "mapping" && <MappingTab info={info} t={t} save={save} />}
          {tab === "layout" && <LayoutTab info={info} t={t} save={save} />}
          {tab === "defaults" && <DefaultsTab info={info} t={t} save={save} />}
          {tab === "match" && <MatchRulesTab info={info} t={t} save={save} />}
        </div>
      )}
    </div>
  );
}

export default LeadMappingSettings;
