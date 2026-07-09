import React, { useState, useEffect, useRef } from "react";
import { Pencil, Check, X, Loader2 } from "lucide-react";
import { Badge } from "../ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "../ui/select";
import { Button } from "../ui/button";
import { StatusOption } from "@/types/activity";
import { cn } from "@/lib/utils";

/**
 * Props for ActivityStatusEditor component
 */
export interface ActivityStatusEditorProps {
  /** Current status value */
  value: string;
  /** Status field name (e.g., 'taskstatus' or 'eventstatus') */
  fieldName: string;
  /** Available status options */
  options: StatusOption[];
  /** Whether the user can edit the status */
  canEdit: boolean;
  /** Callback when saving the new status (returns a Promise) */
  onSave: (newValue: string) => Promise<void>;
}

/**
 * Get badge variant based on status
 */
const getStatusVariant = (
  status: string,
): "default" | "success" | "warning" | "destructive" | "secondary" => {
  const lowerStatus = status.toLowerCase();

  if (
    lowerStatus.includes("completed") ||
    lowerStatus.includes("held") ||
    lowerStatus.includes("完了")
  ) {
    return "success";
  }
  if (lowerStatus.includes("progress") || lowerStatus.includes("進行中")) {
    return "warning";
  }
  if (lowerStatus.includes("cancel") || lowerStatus.includes("キャンセル")) {
    return "destructive";
  }
  if (lowerStatus.includes("planned") || lowerStatus.includes("計画")) {
    return "secondary";
  }

  return "default";
};

/**
 * ActivityStatusEditor - Inline status editor with edit/view mode
 *
 * Features:
 * - Display mode: Shows status as a Badge with optional edit icon
 * - Edit mode: Allows selecting a new status from a dropdown
 * - Save/Cancel: Immediate save on selection, with cancel option
 * - Loading state: Shows spinner while saving
 * - Keyboard support: Escape key to cancel
 *
 * @example
 * ```tsx
 * <ActivityStatusEditor
 *   value="In Progress"
 *   fieldName="taskstatus"
 *   options={[
 *     { value: 'Planned', label: 'Planned' },
 *     { value: 'In Progress', label: 'In Progress' },
 *     { value: 'Completed', label: 'Completed' }
 *   ]}
 *   canEdit={true}
 *   onSave={async (newValue) => {
 *     await updateActivityStatus(activityId, newValue);
 *   }}
 * />
 * ```
 */
export const ActivityStatusEditor: React.FC<ActivityStatusEditorProps> = ({
  value,
  fieldName,
  options,
  canEdit,
  onSave,
}) => {
  const [isEditing, setIsEditing] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [selectedValue, setSelectedValue] = useState(value);
  const editorRef = useRef<HTMLDivElement>(null);

  // Reset selected value when prop changes
  useEffect(() => {
    setSelectedValue(value);
  }, [value]);

  // Handle Escape key to cancel editing
  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape" && isEditing) {
        handleCancel();
      }
    };

    if (isEditing) {
      document.addEventListener("keydown", handleKeyDown);
    }

    return () => {
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [isEditing]);

  /**
   * Handle save action
   */
  const handleSave = async () => {
    // If value hasn't changed, just exit edit mode
    if (selectedValue === value) {
      setIsEditing(false);
      return;
    }

    setIsSaving(true);
    try {
      await onSave(selectedValue);
      setIsEditing(false);
    } catch (error) {
      console.error("Failed to update status:", error);
      // Reset to original value on error
      setSelectedValue(value);
    } finally {
      setIsSaving(false);
    }
  };

  /**
   * Handle cancel action
   */
  const handleCancel = () => {
    setSelectedValue(value);
    setIsEditing(false);
  };

  /**
   * Handle selection change
   */
  const handleValueChange = (newValue: string) => {
    setSelectedValue(newValue);
  };

  /**
   * Enter edit mode
   */
  const handleEditClick = () => {
    if (canEdit) {
      setIsEditing(true);
    }
  };

  // Get current status label
  const currentLabel =
    options.find((opt) => opt.value === value)?.label || value;
  const statusVariant = getStatusVariant(value);

  // Display mode - Badge自体がクリック可能、hover時に編集アイコン表示
  if (!isEditing) {
    return (
      <Badge
        variant={statusVariant}
        className={cn(
          "px-4 text-md",
          canEdit && [
            "cursor-pointer",
            "hover:ring-2 hover:ring-offset-1 hover:ring-blue-400",
            "transition-all",
          ],
        )}
        onClick={handleEditClick}
        role={canEdit ? "button" : undefined}
        aria-label={canEdit ? `${currentLabel} - クリックして編集` : undefined}
        tabIndex={canEdit ? 0 : undefined}
        onKeyDown={(e) => {
          if (canEdit && (e.key === "Enter" || e.key === " ")) {
            e.preventDefault();
            handleEditClick();
          }
        }}
      >
        {currentLabel}
        {canEdit && (
          <Pencil className="h-3 w-3 ml-1 opacity-50" aria-hidden="true" />
        )}
      </Badge>
    );
  }

  // Edit mode - Badgeの場所がSelectに置き換わる
  return (
    <div ref={editorRef} className="inline-flex items-center gap-1">
      <Select
        value={selectedValue}
        onValueChange={handleValueChange}
        disabled={isSaving}
      >
        <SelectTrigger
          className="h-6 min-w-[120px] text-md"
          aria-label={`Select ${fieldName}`}
        >
          <SelectValue placeholder="選択..." />
        </SelectTrigger>
        <SelectContent>
          {options.map((option) => (
            <SelectItem key={option.value} value={option.value}>
              {option.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      {/* Action buttons - コンパクトに */}
      <Button
        size="icon-sm"
        variant="ghost"
        className="h-6 w-6"
        onClick={handleSave}
        disabled={isSaving}
        aria-label="保存"
        title="保存"
      >
        {isSaving ? (
          <Loader2 className="h-3 w-3 animate-spin" />
        ) : (
          <Check className="h-3 w-3 text-green-600" />
        )}
      </Button>
      <Button
        size="icon-sm"
        variant="ghost"
        className="h-6 w-6"
        onClick={handleCancel}
        disabled={isSaving}
        aria-label="キャンセル"
        title="キャンセル"
      >
        <X className="h-3 w-3 text-red-600" />
      </Button>
    </div>
  );
};

export default ActivityStatusEditor;
