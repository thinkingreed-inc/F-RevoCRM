import React, { useCallback, useEffect, useRef, useState } from "react";
import { Button } from "@/components/ui/button";

/** 書いた内容を保存するときの背景色（透過だとサムネイルで線が見えにくいため白で塗る） */
const BACKGROUND_COLOR = "#ffffff";
const STROKE_COLOR = "#111827";
const STROKE_WIDTH = 2.5;

/** 書き終わってから自動で保存するまでの待ち時間（ミリ秒） */
export const HANDWRITING_AUTO_SAVE_DELAY_MS = 800;

export interface HandwritingPadLabels {
  clear: string;
  cancel: string;
  placeholder: string;
}

interface HandwritingPadProps {
  /** 書いた内容（PNG）を受け取る。書き終わって少し待つと自動で呼ぶ */
  onSave: (image: Blob) => void;
  /** 書いた内容を消したとき */
  onClear?: () => void;
  /** やめる（既存のファイルがあるときだけ出す） */
  onCancel?: () => void;
  disabled?: boolean;
  labels: HandwritingPadLabels;
  /** 表示上の高さ（px） */
  height?: number;
}

/**
 * 手書き入力欄
 *
 * マウス・タッチ・ペンを Pointer Events でまとめて扱う。
 * 高解像度の画面でも線がぼやけないよう、表示の大きさ × devicePixelRatio で描く。
 *
 * 確定ボタンは置かず、書き終わって少し待つと自動で onSave を呼ぶ（続けて書けば待ち直す）。
 * マウスが書く欄から外れたとき（保存ボタンへ移るときなど）は待たずに呼ぶ。
 * タッチ・ペンは指を離すたびに欄から外れた扱いになるため、待ち時間だけで判断する。
 */
export const HandwritingPad: React.FC<HandwritingPadProps> = ({
  onSave,
  onClear,
  onCancel,
  disabled = false,
  labels,
  height = 200,
}) => {
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const drawingRef = useRef(false);
  const lastPointRef = useRef<{ x: number; y: number } | null>(null);
  const [hasStroke, setHasStroke] = useState(false);
  /** 自動保存の待ち（書いたがまだ保存していない間だけ持つ） */
  const autoSaveTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const onSaveRef = useRef(onSave);
  onSaveRef.current = onSave;

  const getContext = useCallback((): CanvasRenderingContext2D | null => {
    return canvasRef.current?.getContext("2d") ?? null;
  }, []);

  const fillBackground = useCallback(() => {
    const canvas = canvasRef.current;
    const context = getContext();
    if (!canvas || !context) return;
    context.save();
    context.setTransform(1, 0, 0, 1, 0, 0);
    context.fillStyle = BACKGROUND_COLOR;
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.restore();
  }, [getContext]);

  // 表示の大きさに合わせて描画領域を用意する
  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ratio = window.devicePixelRatio || 1;
    const width = canvas.clientWidth || 480;
    canvas.width = Math.round(width * ratio);
    canvas.height = Math.round(height * ratio);
    const context = getContext();
    if (context) {
      context.scale(ratio, ratio);
      context.lineCap = "round";
      context.lineJoin = "round";
      context.strokeStyle = STROKE_COLOR;
      context.lineWidth = STROKE_WIDTH;
    }
    fillBackground();
  }, [height, getContext, fillBackground]);

  const cancelAutoSave = useCallback(() => {
    if (autoSaveTimerRef.current !== null) {
      clearTimeout(autoSaveTimerRef.current);
      autoSaveTimerRef.current = null;
    }
  }, []);

  const save = useCallback(() => {
    cancelAutoSave();
    const canvas = canvasRef.current;
    if (!canvas) return;
    canvas.toBlob((blob) => {
      if (blob) onSaveRef.current(blob);
    }, "image/png");
  }, [cancelAutoSave]);

  const scheduleAutoSave = useCallback(() => {
    cancelAutoSave();
    autoSaveTimerRef.current = setTimeout(save, HANDWRITING_AUTO_SAVE_DELAY_MS);
  }, [cancelAutoSave, save]);

  // 閉じたら保存しない（やめたときなど）
  useEffect(() => cancelAutoSave, [cancelAutoSave]);

  const toPoint = (event: React.PointerEvent<HTMLCanvasElement>) => {
    const rect = event.currentTarget.getBoundingClientRect();
    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
  };

  const handlePointerDown = (event: React.PointerEvent<HTMLCanvasElement>) => {
    if (disabled) return;
    event.preventDefault();
    event.currentTarget.setPointerCapture?.(event.pointerId);
    cancelAutoSave();
    drawingRef.current = true;
    const point = toPoint(event);
    lastPointRef.current = point;
    const context = getContext();
    if (context) {
      // 点を打っただけでも跡が残るようにする
      context.beginPath();
      context.arc(point.x, point.y, STROKE_WIDTH / 2, 0, Math.PI * 2);
      context.fillStyle = STROKE_COLOR;
      context.fill();
    }
    setHasStroke(true);
  };

  const handlePointerMove = (event: React.PointerEvent<HTMLCanvasElement>) => {
    if (!drawingRef.current || disabled) return;
    event.preventDefault();
    const point = toPoint(event);
    const last = lastPointRef.current ?? point;
    const context = getContext();
    if (context) {
      context.beginPath();
      context.moveTo(last.x, last.y);
      context.lineTo(point.x, point.y);
      context.stroke();
    }
    lastPointRef.current = point;
  };

  const endStroke = () => {
    if (!drawingRef.current) return false;
    drawingRef.current = false;
    lastPointRef.current = null;
    return true;
  };

  const handlePointerUp = () => {
    if (endStroke()) scheduleAutoSave();
  };

  const handlePointerLeave = (event: React.PointerEvent<HTMLCanvasElement>) => {
    const ended = endStroke();
    if (event.pointerType === "mouse") {
      // 保存ボタンを押す前に値が入るよう、待たずに保存する
      if (ended || autoSaveTimerRef.current !== null) save();
    } else if (ended) {
      scheduleAutoSave();
    }
  };

  const handleClear = () => {
    cancelAutoSave();
    fillBackground();
    setHasStroke(false);
    onClear?.();
  };

  return (
    <div className="flex flex-col gap-2" data-testid="handwriting-pad">
      <div className="relative">
        <canvas
          ref={canvasRef}
          data-testid="handwriting-canvas"
          className="block w-full max-w-[560px] rounded-md border border-gray-300 bg-white touch-none cursor-crosshair"
          style={{ height }}
          onPointerDown={handlePointerDown}
          onPointerMove={handlePointerMove}
          onPointerUp={handlePointerUp}
          onPointerLeave={handlePointerLeave}
          onPointerCancel={handlePointerUp}
          aria-label={labels.placeholder}
          role="img"
        />
        {!hasStroke && (
          <span className="pointer-events-none absolute left-3 top-2 text-sm text-gray-400">
            {labels.placeholder}
          </span>
        )}
      </div>
      <div className="flex gap-2">
        <Button
          type="button"
          size="sm"
          variant="outline"
          onClick={handleClear}
          disabled={disabled || !hasStroke}
          data-testid="handwriting-clear"
        >
          {labels.clear}
        </Button>
        {onCancel && (
          <Button
            type="button"
            size="sm"
            variant="ghost"
            onClick={onCancel}
            disabled={disabled}
            data-testid="handwriting-cancel"
          >
            {labels.cancel}
          </Button>
        )}
      </div>
    </div>
  );
};
