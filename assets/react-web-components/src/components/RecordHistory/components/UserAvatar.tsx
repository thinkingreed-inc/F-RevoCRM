import React from "react";
import { cn } from "@/lib/utils";
import type { HistoryUser } from "@/types/history";

const SIZE_CLASS = {
  sm: "size-[18px] text-[9px]",
  md: "size-[24px] text-[11px]",
  lg: "size-[32px] text-[13px]",
} as const;

export interface UserAvatarProps {
  user: HistoryUser | undefined;
  size?: keyof typeof SIZE_CLASS;
  className?: string;
}

/**
 * ユーザーのアイコン。プロフィール画像があれば画像、無ければ名前の先頭文字。
 * CRM にユーザー固有の色が無いため、色分けはせずニュートラルな配色にする。
 */
export const UserAvatar: React.FC<UserAvatarProps> = ({
  user,
  size = "md",
  className,
}) => {
  const base = cn(
    "inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full",
    SIZE_CLASS[size],
    className,
  );
  if (user?.imageUrl) {
    return (
      <img
        src={user.imageUrl}
        alt=""
        aria-hidden="true"
        className={cn(base, "object-cover")}
      />
    );
  }
  const initial = user?.name ? Array.from(user.name.trim())[0] : "?";
  return (
    <span
      aria-hidden="true"
      className={cn(base, "bg-slate-200 font-bold text-slate-600")}
    >
      {initial}
    </span>
  );
};
