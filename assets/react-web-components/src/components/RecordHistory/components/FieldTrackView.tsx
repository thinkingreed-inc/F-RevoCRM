import React, { useMemo } from "react";
import { cn } from "@/lib/utils";
import type { HistoryEvent, HistoryField, HistoryUser } from "@/types/history";
import { useHistoryTranslation } from "../hooks/useHistoryTranslation";
import { buildFieldTrack } from "../utils/fieldTrack";
import { useHistoryFormat } from "../hooks/useHistoryFormat";
import { ChangeValue } from "./ChangeValue";
import { UserAvatar } from "./UserAvatar";

export interface FieldTrackViewProps {
  events: HistoryEvent[];
  /** 左の一覧に出す項目（詳細画面の並び順） */
  fields: HistoryField[];
  counts: Record<string, number>;
  users: Record<string, HistoryUser>;
  userIds: number[];
  selectedField: string | null;
  onSelectField: (field: string) => void;
}

/** 項目ごとの値の変遷（左: 項目一覧 / 右: 選択項目の値の移り変わり） */
export const FieldTrackView: React.FC<FieldTrackViewProps> = ({
  events,
  fields,
  counts,
  users,
  userIds,
  selectedField,
  onSelectField,
}) => {
  const t = useHistoryTranslation();
  const fmt = useHistoryFormat();
  const field = fields.find((f) => f.name === selectedField) ?? fields[0];
  const track = useMemo(
    () => (field ? buildFieldTrack(events, field.name, userIds) : []),
    [events, field, userIds],
  );

  if (!field) {
    return (
      <p className="m-0 px-5 py-16 text-center text-slate-500">
        {t("LBL_HISTORY_FIELD_TRACK_NONE")}
      </p>
    );
  }

  return (
    <div className="grid min-h-[360px] grid-cols-[220px_minmax(0,1fr)]">
      <nav
        className="grid content-start gap-1 border-0 border-r border-solid border-slate-200 p-3"
        aria-label={t("LBL_HISTORY_FIELDS")}
      >
        {fields.map((f) => {
          const pressed = f.name === field.name;
          return (
            <button
              key={f.name}
              type="button"
              aria-pressed={pressed}
              onClick={() => onSelectField(f.name)}
              className={cn(
                "grid shrink-0 cursor-pointer grid-cols-[minmax(0,1fr)_auto] gap-3 rounded border-0 px-4 py-2 text-left text-md",
                pressed
                  ? "bg-blue-50 font-bold text-blue-700"
                  : "bg-transparent hover:bg-slate-50",
              )}
            >
              <span className="truncate">{f.label}</span>
              <span className="font-mono text-xs font-normal text-slate-400">
                {counts[f.name] ?? 0}
              </span>
            </button>
          );
        })}
      </nav>

      <div className="grid min-w-0 content-start gap-5 px-8 py-6">
        <div className="flex flex-wrap items-baseline gap-3">
          <h4 className="m-0 text-[15px] font-bold text-slate-800">
            {t("LBL_HISTORY_FIELD_TRACK_TITLE", field.label)}
          </h4>
          <span className="text-sm text-slate-500">
            {t("LBL_HISTORY_FIELD_TRACK_META", counts[field.name] ?? 0)}
          </span>
        </div>

        {track.length === 0 ? (
          <p className="m-0 text-slate-500">
            {t("LBL_HISTORY_FIELD_TRACK_EMPTY")}
          </p>
        ) : (
          <ol className="m-0 grid list-none p-0">
            {track.map((entry) => {
              const isCurrent = entry.until === null;
              const user = users[String(entry.userId)];
              const userName = user?.name || t("LBL_HISTORY_UNKNOWN_USER");
              const days = fmt.heldDays(entry.changedOn, entry.until);
              return (
                <li
                  key={entry.eventId}
                  className="relative grid grid-cols-[14px_minmax(0,1fr)] gap-5 pb-6 before:absolute before:bottom-0 before:left-[6px] before:top-[18px] before:w-[2px] before:bg-slate-200 before:content-[''] last:before:hidden"
                  data-testid="field-track-entry"
                >
                  <span
                    aria-hidden="true"
                    className={cn(
                      "relative z-[1] mt-1 size-[14px] rounded-full border-2 border-solid",
                      isCurrent
                        ? "border-blue-700 bg-blue-700"
                        : "border-slate-300 bg-white",
                    )}
                  />
                  <div className="grid gap-1">
                    <div className="flex flex-wrap items-center gap-3 text-[15px]">
                      <ChangeValue
                        type={field.type}
                        value={entry.value}
                        role={isCurrent ? "after" : "plain"}
                        users={users}
                      />
                      {isCurrent && (
                        <span className="rounded-[3px] border border-solid border-blue-700 px-1.5 text-xs font-bold leading-[16px] text-blue-700">
                          {t("LBL_HISTORY_CURRENT")}
                        </span>
                      )}
                    </div>
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                      <UserAvatar user={user} size="sm" />
                      <span>
                        {entry.isInitial
                          ? t("LBL_HISTORY_SET_ON_CREATE", userName)
                          : t("LBL_HISTORY_CHANGED_BY", userName)}
                      </span>
                      <span aria-hidden="true">·</span>
                      <time dateTime={entry.changedOn}>
                        {fmt.fullDateTime(entry.changedOn)}
                      </time>
                      <span aria-hidden="true">·</span>
                      <span className="font-mono">
                        {isCurrent
                          ? t("LBL_HISTORY_HELD_ONGOING", days + 1)
                          : t("LBL_HISTORY_HELD_DAYS", days)}
                      </span>
                    </div>
                  </div>
                </li>
              );
            })}
          </ol>
        )}
      </div>
    </div>
  );
};
