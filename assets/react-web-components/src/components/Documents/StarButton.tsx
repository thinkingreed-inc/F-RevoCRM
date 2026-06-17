import React, { useState, useCallback } from "react";
import { useOptionalTranslation } from "../../hooks/useTranslation";

interface StarButtonProps {
  recordId: number;
  starred: boolean;
  onChange?: (starred: boolean) => void;
}

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as any).csrfMagicName;
  const csrfToken = (window as any).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
}

export const StarButton: React.FC<StarButtonProps> = ({
  recordId,
  starred: initialStarred,
  onChange,
}) => {
  const { t } = useOptionalTranslation();
  const [starred, setStarred] = useState(initialStarred);
  const [isUpdating, setIsUpdating] = useState(false);

  const toggle = useCallback(
    async (e: React.MouseEvent) => {
      e.stopPropagation();
      e.preventDefault();
      if (isUpdating) return;

      const newValue = !starred;
      setStarred(newValue); // 楽観的更新
      setIsUpdating(true);

      try {
        const csrf = getCsrfToken();
        const bodyParams = new URLSearchParams();
        if (csrf) {
          bodyParams.append(csrf.name, csrf.value);
        }
        bodyParams.append("module", "Documents");
        bodyParams.append("api", "StarAPI");
        bodyParams.append("record", String(recordId));
        bodyParams.append("starred", newValue ? "1" : "0");

        const response = await fetch("index.php", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: bodyParams.toString(),
        });

        const data = await response.json();
        if (data.success === false || data.error) {
          setStarred(!newValue); // ロールバック
        } else {
          onChange?.(newValue);
        }
      } catch {
        setStarred(!newValue); // ロールバック
      } finally {
        setIsUpdating(false);
      }
    },
    [recordId, starred, isUpdating, onChange]
  );

  return (
    <button
      onClick={toggle}
      disabled={isUpdating}
      style={{
        background: "none",
        border: "none",
        cursor: isUpdating ? "wait" : "pointer",
        padding: 2,
        fontSize: 16,
        color: starred ? "#ED8936" : "#CBD5E0",
        transition: "color 0.15s",
        lineHeight: 1,
      }}
      title={starred ? t('LBL_STAR_REMOVE') : t('LBL_STAR_ADD')}
      aria-label={starred ? t('LBL_STAR_REMOVE') : t('LBL_STAR_ADD')}
    >
      {starred ? "★" : "☆"}
    </button>
  );
};
