import React, { useState, useRef, useEffect, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { cn } from '@/lib/utils';

/**
 * Time option for dropdown selection
 */
export interface TimeOption {
  value: string;
  label: string;
}

/**
 * TimeComboBox props
 */
export interface TimeComboBoxProps {
  /** Current value (HH:MM format) */
  value: string;
  /** Callback when value changes */
  onChange: (value: string) => void;
  /** Whether the input is disabled */
  disabled?: boolean;
  /** Whether the input has error */
  error?: boolean;
  /** Time options for dropdown (10-minute intervals) */
  timeOptions: TimeOption[];
  /** Additional CSS class */
  className?: string;
  /** Placeholder text */
  placeholder?: string;
}

/**
 * TimeComboBox - Hybrid time input with manual entry and dropdown selection
 *
 * Features:
 * - Manual input (1-minute precision): 930 → 09:30, 9:30 → 09:30, 14 → 14:00, 1430 → 14:30, 09:23 → 09:23
 * - Dropdown selection (10-minute intervals)
 * - Auto-normalization on blur
 * - Keyboard navigation (↑↓ to navigate, Enter to select, Esc to close)
 * - Portal-based dropdown rendering
 * - Invalid input reverts to previous valid value
 *
 * @example
 * ```tsx
 * <TimeComboBox
 *   value="09:30"
 *   onChange={(value) => console.log(value)}
 *   timeOptions={[
 *     { value: "09:00", label: "09:00" },
 *     { value: "09:10", label: "09:10" },
 *     // ...
 *   ]}
 *   placeholder="--:--"
 * />
 * ```
 */
export function TimeComboBox({
  value,
  onChange,
  disabled = false,
  error = false,
  timeOptions,
  className,
  placeholder = '--:--'
}: TimeComboBoxProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [inputValue, setInputValue] = useState(value);
  const [highlightedIndex, setHighlightedIndex] = useState(-1);
  const [lastValidValue, setLastValidValue] = useState(value);

  const inputRef = useRef<HTMLInputElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);
  // Track if an option was just selected to skip blur normalization
  const optionSelectedRef = useRef(false);
  const [dropdownPosition, setDropdownPosition] = useState<{
    top: number;
    left: number;
    width: number;
  } | null>(null);

  // Update inputValue when value prop changes
  useEffect(() => {
    setInputValue(value);
    setLastValidValue(value);
  }, [value]);

  // Calculate dropdown position
  const updateDropdownPosition = useCallback(() => {
    if (!inputRef.current) return;

    const rect = inputRef.current.getBoundingClientRect();
    setDropdownPosition({
      top: rect.bottom + window.scrollY + 4,
      left: rect.left + window.scrollX,
      width: rect.width
    });
  }, []);

  // Handle input focus
  const handleFocus = useCallback(() => {
    if (disabled) return;
    setIsOpen(true);
    updateDropdownPosition();
  }, [disabled, updateDropdownPosition]);

  // Handle input blur
  const handleBlur = useCallback(() => {
    // Delay to allow click on dropdown
    setTimeout(() => {
      setIsOpen(false);

      // Skip normalization if an option was just selected
      // (handleOptionClick already handled the value correctly)
      if (optionSelectedRef.current) {
        optionSelectedRef.current = false;
        return;
      }

      // Normalize the input value
      const normalized = normalizeTimeInput(inputValue);
      if (normalized) {
        setInputValue(normalized);
        setLastValidValue(normalized);
        onChange(normalized);
      } else {
        // Invalid input: revert to last valid value
        setInputValue(lastValidValue);
      }
    }, 200);
  }, [inputValue, lastValidValue, onChange]);

  // Handle input change
  const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;
    setInputValue(newValue);
  }, []);

  // Handle option click
  const handleOptionClick = useCallback((option: TimeOption) => {
    // Mark that an option was selected to skip blur normalization
    optionSelectedRef.current = true;
    setInputValue(option.value);
    setLastValidValue(option.value);
    onChange(option.value);
    setIsOpen(false);
    inputRef.current?.focus();
  }, [onChange]);

  // Handle keyboard navigation
  const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLInputElement>) => {
    if (!isOpen) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        setIsOpen(true);
        updateDropdownPosition();
      }
      return;
    }

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setHighlightedIndex(prev =>
          prev < timeOptions.length - 1 ? prev + 1 : 0
        );
        break;
      case 'ArrowUp':
        e.preventDefault();
        setHighlightedIndex(prev =>
          prev > 0 ? prev - 1 : timeOptions.length - 1
        );
        break;
      case 'Enter':
        e.preventDefault();
        if (highlightedIndex >= 0 && highlightedIndex < timeOptions.length) {
          handleOptionClick(timeOptions[highlightedIndex]);
        }
        break;
      case 'Escape':
        e.preventDefault();
        setIsOpen(false);
        break;
    }
  }, [isOpen, highlightedIndex, timeOptions, handleOptionClick, updateDropdownPosition]);

  // Scroll highlighted option into view
  useEffect(() => {
    if (isOpen && highlightedIndex >= 0 && dropdownRef.current) {
      const highlightedElement = dropdownRef.current.querySelector(
        `[data-index="${highlightedIndex}"]`
      ) as HTMLElement;
      if (highlightedElement) {
        highlightedElement.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    }
  }, [isOpen, highlightedIndex]);

  // Auto-highlight closest option when dropdown opens
  useEffect(() => {
    if (isOpen && value) {
      const closestIndex = findClosestOptionIndex(value, timeOptions);
      setHighlightedIndex(closestIndex);
    }
  }, [isOpen, value, timeOptions]);

  // Close dropdown on outside click
  useEffect(() => {
    if (!isOpen) return;

    const handleClickOutside = (e: MouseEvent) => {
      if (
        inputRef.current && !inputRef.current.contains(e.target as Node) &&
        dropdownRef.current && !dropdownRef.current.contains(e.target as Node)
      ) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [isOpen]);

  // Update dropdown position on window resize
  useEffect(() => {
    if (!isOpen) return;

    const handleResize = () => updateDropdownPosition();
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, [isOpen, updateDropdownPosition]);

  return (
    <>
      <input
        ref={inputRef}
        type="text"
        value={inputValue}
        onChange={handleInputChange}
        onFocus={handleFocus}
        onBlur={handleBlur}
        onKeyDown={handleKeyDown}
        disabled={disabled}
        placeholder={placeholder}
        className={cn(
          'w-28 h-[30px] px-2 text-md border border-input rounded-sm shadow-xs transition-[color,box-shadow]',
          'focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring',
          'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
          error && 'border-red-500 bg-red-50',
          className
        )}
      />

      {isOpen && !disabled && dropdownPosition && createPortal(
        <div
          ref={dropdownRef}
          className="fixed z-[100003] bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-auto pointer-events-auto"
          style={{
            top: dropdownPosition.top,
            left: dropdownPosition.left,
            width: dropdownPosition.width
          }}
          onWheel={(e) => {
            e.stopPropagation();
            const target = e.currentTarget;
            target.scrollTop += e.deltaY * 0.3;
          }}
        >
          <div className="py-1">
            {timeOptions.map((option, index) => (
              <div
                key={option.value}
                data-index={index}
                onClick={() => handleOptionClick(option)}
                className={cn(
                  'px-3 py-1.5 text-md cursor-pointer',
                  highlightedIndex === index
                    ? 'bg-blue-100'
                    : 'hover:bg-blue-50',
                  value === option.value && 'font-semibold'
                )}
              >
                {option.label}
              </div>
            ))}
          </div>
        </div>,
        document.body
      )}
    </>
  );
}

/**
 * Normalize time input to HH:MM format
 *
 * Examples:
 * - "930" → "09:30"
 * - "9:30" → "09:30"
 * - "14" → "14:00"
 * - "1430" → "14:30"
 * - "09:23" → "09:23"
 * - "25:00" → null (invalid)
 * - "abc" → null (invalid)
 */
function normalizeTimeInput(input: string): string | null {
  if (!input) return null;

  // Remove whitespace
  const cleaned = input.trim();

  // Already in HH:MM format
  if (/^\d{1,2}:\d{2}$/.test(cleaned)) {
    const [hourStr, minuteStr] = cleaned.split(':');
    const hour = parseInt(hourStr, 10);
    const minute = parseInt(minuteStr, 10);

    if (isValidTime(hour, minute)) {
      return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
    }
    return null;
  }

  // Digits only (e.g., "930", "14", "1430")
  if (/^\d+$/.test(cleaned)) {
    const digits = cleaned;

    if (digits.length === 1 || digits.length === 2) {
      // "9" → "09:00", "14" → "14:00"
      const hour = parseInt(digits, 10);
      if (isValidTime(hour, 0)) {
        return `${String(hour).padStart(2, '0')}:00`;
      }
      return null;
    }

    if (digits.length === 3) {
      // "930" → "09:30"
      const hour = parseInt(digits.substring(0, 1), 10);
      const minute = parseInt(digits.substring(1, 3), 10);
      if (isValidTime(hour, minute)) {
        return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
      }
      return null;
    }

    if (digits.length === 4) {
      // "1430" → "14:30"
      const hour = parseInt(digits.substring(0, 2), 10);
      const minute = parseInt(digits.substring(2, 4), 10);
      if (isValidTime(hour, minute)) {
        return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
      }
      return null;
    }
  }

  return null;
}

/**
 * Check if hour and minute are valid
 */
function isValidTime(hour: number, minute: number): boolean {
  return hour >= 0 && hour <= 23 && minute >= 0 && minute <= 59;
}

/**
 * Find the closest option index to the current value
 */
function findClosestOptionIndex(value: string, options: TimeOption[]): number {
  if (!value || options.length === 0) return 0;

  const index = options.findIndex(opt => opt.value === value);
  if (index >= 0) return index;

  // Find closest time
  const [hourStr, minuteStr] = value.split(':');
  const currentMinutes = parseInt(hourStr, 10) * 60 + parseInt(minuteStr, 10);

  let closestIndex = 0;
  let minDiff = Infinity;

  options.forEach((opt, idx) => {
    const [optHour, optMinute] = opt.value.split(':');
    const optMinutes = parseInt(optHour, 10) * 60 + parseInt(optMinute, 10);
    const diff = Math.abs(currentMinutes - optMinutes);

    if (diff < minDiff) {
      minDiff = diff;
      closestIndex = idx;
    }
  });

  return closestIndex;
}
