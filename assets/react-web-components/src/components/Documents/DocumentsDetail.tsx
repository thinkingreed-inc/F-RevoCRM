import React from "react";
import { useDocumentDetail } from "./hooks/useDocumentDetail";
import { DocumentsPreviewPanel } from "./DocumentsPreviewPanel";
import { TranslationProvider } from "../../contexts/TranslationContext";

interface DocumentsDetailProps {
  recordId: number;
}

export const DocumentsDetail: React.FC<DocumentsDetailProps> = ({ recordId }) => {
  const { document: doc, isLoading } = useDocumentDetail(recordId);

  const handleBack = () => {
    window.location.href = "index.php?module=Documents&view=List";
  };

  return (
    <TranslationProvider module="Documents">
    <div style={{ height: "100%", display: "flex", flexDirection: "column" }}>
      <DocumentsPreviewPanel document={doc} isLoading={isLoading} onBack={handleBack} />
    </div>
    </TranslationProvider>
  );
};
