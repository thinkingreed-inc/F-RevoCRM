import React, { useRef, useState, useCallback } from "react";
import Image from "@tiptap/extension-image";
import { NodeViewWrapper, ReactNodeViewRenderer } from "@tiptap/react";
import type { NodeViewProps } from "@tiptap/react";

const ResizableImageComponent = ({
  node,
  updateAttributes,
  selected,
}: NodeViewProps) => {
  const imgRef = useRef<HTMLImageElement>(null);
  const [resizing, setResizing] = useState(false);

  const handleMouseDown = useCallback(
    (e: React.MouseEvent) => {
      e.preventDefault();
      e.stopPropagation();
      const startX = e.clientX;
      const startWidth = imgRef.current?.offsetWidth || 200;
      const startHeight =
        (node.attrs.height as number) ||
        imgRef.current?.naturalHeight ||
        imgRef.current?.offsetHeight ||
        0;
      const ratio = startWidth > 0 ? startHeight / startWidth : 0;
      setResizing(true);
      const maxWidth = window.innerWidth;
      const onMouseMove = (ev: MouseEvent) => {
        const newWidth = Math.min(maxWidth, Math.max(50, startWidth + ev.clientX - startX));
        const newHeight = ratio > 0 ? Math.round(newWidth * ratio) : null;
        updateAttributes({ width: newWidth, height: newHeight });
      };
      const onMouseUp = () => {
        setResizing(false);
        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);
      };
      document.addEventListener("mousemove", onMouseMove);
      document.addEventListener("mouseup", onMouseUp);
    },
    [updateAttributes, node]
  );

  const width = node.attrs.width as number | null;
  return (
    <NodeViewWrapper
      as="span"
      className="tiptap-resizable-image-wrapper"
      style={{ display: "inline-block" }}
    >
      <span
        className={`tiptap-resizable-image ${selected ? "selected" : ""} ${resizing ? "resizing" : ""}`}
        style={{
          display: "inline-block",
          position: "relative",
          width: width ? `${width}px` : undefined,
        }}
      >
        <img
          ref={imgRef}
          src={node.attrs.src as string}
          alt={(node.attrs.alt as string) || ""}
          title={(node.attrs.title as string) || undefined}
          style={{ width: "100%", height: "auto", display: "block" }}
          draggable={false}
        />
        {selected && (
          <span
            className="tiptap-resize-handle tiptap-resize-handle-br"
            onMouseDown={handleMouseDown}
          />
        )}
      </span>
    </NodeViewWrapper>
  );
};

export const ResizableImage = Image.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      width: {
        default: null,
        parseHTML: (element) => {
          const w = element.getAttribute("width") || element.style.width;
          const parsed = w ? parseInt(String(w), 10) || null : null;
          return parsed !== null ? Math.min(parsed, 2000) : null;
        },
        renderHTML: (attributes) => {
          if (!attributes.width) return {};
          const w = Math.min(parseInt(String(attributes.width), 10), 2000);
          if (!w || w <= 0) return {};
          return {
            width: w,
            style: `width: ${w}px`,
          };
        },
      },
      height: {
        default: null,
        parseHTML: (element) => {
          const rawW = element.getAttribute("width") || element.style.width;
          const origW = rawW ? parseInt(String(rawW), 10) || 0 : 0;
          const h = element.getAttribute("height") || element.style.height;
          const parsed = h ? parseInt(String(h), 10) || null : null;
          if (parsed !== null && origW > 2000) {
            return Math.round(parsed * 2000 / origW);
          }
          return parsed;
        },
        renderHTML: (attributes) => {
          if (!attributes.height) return {};
          const h = parseInt(String(attributes.height), 10);
          if (!h || h <= 0) return {};
          const rawW = attributes.width ? parseInt(String(attributes.width), 10) : 0;
          if (rawW > 2000) {
            return { height: Math.round(h * 2000 / rawW) };
          }
          return { height: h };
        },
      },
    };
  },
  addNodeView() {
    return ReactNodeViewRenderer(ResizableImageComponent);
  },
});
