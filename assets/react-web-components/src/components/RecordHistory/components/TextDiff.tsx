import React, { useMemo } from "react";
import { diffText } from "../utils/textDiff";

export interface TextDiffProps {
  before: string;
  after: string;
}

/** 長文の差分を 1 つの本文にまとめて表示する（削除は赤の打ち消し、追加は緑） */
export const TextDiff: React.FC<TextDiffProps> = ({ before, after }) => {
  const segments = useMemo(() => diffText(before, after), [before, after]);
  return (
    <div
      className="whitespace-pre-wrap break-words rounded bg-slate-50 px-4 py-2 text-md leading-relaxed"
      data-testid="text-diff"
    >
      {segments.map((segment, index) => {
        if (segment.op === "insert")
          return (
            <ins
              key={index}
              className="rounded-sm bg-emerald-100 text-emerald-800 no-underline"
            >
              {segment.text}
            </ins>
          );
        if (segment.op === "delete")
          return (
            <del key={index} className="rounded-sm bg-red-100 text-red-700">
              {segment.text}
            </del>
          );
        return <span key={index}>{segment.text}</span>;
      })}
    </div>
  );
};
