import React, { useState, useCallback, useRef } from "react";
import { useFileUpload } from "./hooks/useFileUpload";

interface DocumentsUploadZoneProps {
  folderId: number;
  onUploadComplete: () => void;
}

export const DocumentsUploadZone: React.FC<DocumentsUploadZoneProps> = ({
  folderId,
  onUploadComplete,
}) => {
  const [isDragging, setIsDragging] = useState(false);
  const { isUploading, progress, error, upload } = useFileUpload(onUploadComplete);
  const dragCountRef = useRef(0);

  const handleDragEnter = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    dragCountRef.current++;
    if (dragCountRef.current === 1) setIsDragging(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    dragCountRef.current--;
    if (dragCountRef.current === 0) setIsDragging(false);
  }, []);

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
  }, []);

  const handleDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault();
      e.stopPropagation();
      dragCountRef.current = 0;
      setIsDragging(false);

      const files = e.dataTransfer.files;
      if (files.length > 0) {
        upload(files, folderId);
      }
    },
    [upload, folderId]
  );

  return (
    <div
      onDragEnter={handleDragEnter}
      onDragLeave={handleDragLeave}
      onDragOver={handleDragOver}
      onDrop={handleDrop}
      style={{
        position: "relative",
      }}
    >
      {/* ドラッグ中オーバーレイ */}
      {isDragging && (
        <div
          style={{
            position: "absolute",
            inset: 0,
            backgroundColor: "rgba(49, 130, 206, 0.08)",
            border: "2px dashed #3182CE",
            borderRadius: 8,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 10,
            pointerEvents: "none",
          }}
        >
          <div style={{ fontSize: 14, color: "#3182CE", fontWeight: 500 }}>
            ファイルをここにドロップしてアップロード
          </div>
        </div>
      )}

      {/* アップロード中プログレス */}
      {isUploading && (
        <div
          style={{
            position: "absolute",
            bottom: 0,
            left: 0,
            right: 0,
            padding: "8px 16px",
            backgroundColor: "#EBF8FF",
            borderTop: "1px solid #BEE3F8",
            zIndex: 11,
          }}
        >
          <div style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 13 }}>
            <span>アップロード中... {progress}%</span>
            <div style={{ flex: 1, height: 4, backgroundColor: "#BEE3F8", borderRadius: 2 }}>
              <div
                style={{
                  width: `${progress}%`,
                  height: "100%",
                  backgroundColor: "#3182CE",
                  borderRadius: 2,
                  transition: "width 0.2s",
                }}
              />
            </div>
          </div>
        </div>
      )}

      {/* エラー表示 */}
      {error && (
        <div
          style={{
            position: "absolute",
            bottom: 0,
            left: 0,
            right: 0,
            padding: "8px 16px",
            backgroundColor: "#FED7D7",
            borderTop: "1px solid #FEB2B2",
            color: "#C53030",
            fontSize: 13,
            zIndex: 11,
          }}
        >
          {error}
        </div>
      )}
    </div>
  );
};
