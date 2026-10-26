import React, { useEffect, useState } from "react";
import { Dialog, DialogContent, DialogTitle } from "@/components/ui/dialog";
import { DocumentDetailModal } from "@/components/Documents/DocumentDetailModal";
import { DocumentCreateEditModal } from "@/components/Documents/DocumentCreateEditModal";
import { useDocumentDetail } from "@/components/Documents/hooks/useDocumentDetail";
import { useFolderTree } from "@/components/Documents/hooks/useFolderTree";
import type { DocumentDetail } from "@/components/Documents/types/documents";

interface FileFieldDocumentDialogProps {
  /** 項目に入っているドキュメントのID */
  documentId: number;
  onClose: () => void;
  /** ドキュメントを編集した（ファイルを差し替えると版が増える）とき */
  onChanged: () => void;
}

/**
 * 項目に入っているドキュメントの詳細（ドキュメント画面と同じモーダル）
 *
 * プレビュー・版の履歴・ダウンロード・編集ができる。削除は出さない
 * （削除すると項目が消えたドキュメントを指したままになるため）。
 */
export const FileFieldDocumentDialog: React.FC<
  FileFieldDocumentDialogProps
> = ({ documentId, onClose, onChanged }) => {
  const { document, isLoading, reload } = useDocumentDetail(documentId);
  const [editTarget, setEditTarget] = useState<DocumentDetail | null>(null);

  return (
    <>
      <DocumentDetailModal
        isOpen={editTarget === null}
        document={document}
        isLoading={isLoading}
        onClose={onClose}
        onEdit={(doc) => setEditTarget(doc)}
        onRelationChanged={() => reload()}
      />
      {editTarget && (
        <FileFieldDocumentEditDialog
          document={editTarget}
          onSave={() => {
            setEditTarget(null);
            reload();
            onChanged();
          }}
          onClose={() => setEditTarget(null)}
        />
      )}
    </>
  );
};

/** ドキュメントの編集（フォルダの一覧は編集するときだけ取得する） */
const FileFieldDocumentEditDialog: React.FC<{
  document: DocumentDetail;
  onSave: () => void;
  onClose: () => void;
}> = ({ document, onSave, onClose }) => {
  const { folders } = useFolderTree();
  return (
    <DocumentCreateEditModal
      isOpen
      mode="edit"
      document={document}
      folders={folders}
      defaultFolderId={document.folderid}
      onSave={onSave}
      onClose={onClose}
    />
  );
};

interface FileFieldImageDialogProps {
  /** 表示する画像（無ければ load で取得する） */
  src?: string;
  /** 大きな画像を取得する */
  load?: () => Promise<string | null>;
  alt: string;
  onClose: () => void;
}

/**
 * 画像の拡大表示
 *
 * ドキュメント自体を参照できない人（レコードからは見られる）や、保存前の画像に使う。
 */
export const FileFieldImageDialog: React.FC<FileFieldImageDialogProps> = ({
  src: initialSrc,
  load,
  alt,
  onClose,
}) => {
  const [src, setSrc] = useState<string | null>(initialSrc ?? null);

  useEffect(() => {
    if (initialSrc || !load) return;
    let cancelled = false;
    load()
      .then((loaded) => {
        if (!cancelled) setSrc(loaded);
      })
      .catch(() => {
        // 取得できなければ何も出さない（サムネイルは出ている）
      });
    return () => {
      cancelled = true;
    };
  }, [initialSrc, load]);

  return (
    <Dialog open onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="w-auto max-w-[calc(100%-2rem)] sm:max-w-[min(90vw,1200px)]">
        <DialogTitle className="break-all pr-6 text-sm font-medium">
          {alt}
        </DialogTitle>
        {src && (
          <img
            src={src}
            alt={alt}
            data-testid="file-field-preview-image"
            className="mx-auto max-h-[80vh] max-w-full rounded border border-gray-200 bg-white object-contain"
          />
        )}
      </DialogContent>
    </Dialog>
  );
};
