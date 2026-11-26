import React from "react";
import { cn } from "@/lib/utils";
import type {
  HistoryFieldType,
  HistoryUser,
  HistoryValue,
} from "@/types/history";
import { useHistoryTranslation } from "../hooks/useHistoryTranslation";
import { UserAvatar } from "./UserAvatar";

/** 値の役割: 旧値は打ち消し、新値は強調、それ以外は通常表示 */
export type ChangeValueRole = "before" | "after" | "plain";

const MONO_TYPES: HistoryFieldType[] = [
  "phone",
  "email",
  "url",
  "currency",
  "integer",
  "double",
  "percentage",
];

export interface ChangeValueProps {
  type: HistoryFieldType;
  value: HistoryValue | null;
  role?: ChangeValueRole;
  users: Record<string, HistoryUser>;
}

/** 項目の型に応じて 1 つの値を表示する */
export const ChangeValue: React.FC<ChangeValueProps> = ({
  type,
  value,
  role = "plain",
  users,
}) => {
  const t = useHistoryTranslation();

  if (value === null) {
    return (
      <span className="text-xs text-slate-400">
        {t("LBL_HISTORY_EMPTY_VALUE")}
      </span>
    );
  }

  const roleClass = cn(
    "break-words [overflow-wrap:anywhere]",
    role === "before" && "text-slate-500 line-through decoration-red-400",
  );

  if (type === "picklist" || type === "multipicklist") {
    const items =
      type === "multipicklist"
        ? value.display.split(/\s*,\s*/)
        : [value.display];
    return (
      <span className={cn("inline-flex flex-wrap gap-1", roleClass)}>
        {items.map((item, index) => (
          <span
            key={index}
            className={cn(
              "rounded-full px-3 text-sm leading-[20px]",
              role === "after"
                ? "bg-blue-50 font-medium text-blue-700"
                : "bg-slate-100",
            )}
          >
            {item}
          </span>
        ))}
      </span>
    );
  }

  if (type === "owner") {
    // raw はユーザー ID（グループの場合は履歴に登場しないため名前のみ）
    const user = users[value.raw];
    return (
      <span className={cn("inline-flex items-center gap-1.5", roleClass)}>
        {user && <UserAvatar user={user} size="sm" />}
        {value.display}
      </span>
    );
  }

  if (type === "text") {
    return (
      <span className={cn("whitespace-pre-wrap", roleClass)}>
        {value.display}
      </span>
    );
  }

  return (
    <span
      className={cn(
        roleClass,
        MONO_TYPES.includes(type) && "font-mono tabular-nums",
      )}
    >
      {value.display}
    </span>
  );
};
