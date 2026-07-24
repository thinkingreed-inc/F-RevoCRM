import { useMemo, useState } from "react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { PresetSelector } from "./PresetSelector";
import { JsonTemplateEditor } from "./JsonTemplateEditor";
import {
  TestSendPanel,
  TestSendPayload,
  TestSendResult,
} from "./TestSendPanel";
import { FieldOption } from "./types";
import {
  ADAPTIVE_CARD_DESIGNER_URL,
  CurlLabels,
  mergeLabels,
  presetLabel,
} from "./labels";

interface Props {
  url?: string;
  method?: string;
  headers?: string | object;
  body?: string | object;
  timeout?: string;
  fieldsJson?: FieldOption[] | string;
  labelsJson?: Partial<CurlLabels> | string;
}

const HTTP_METHODS = ["GET", "POST", "PUT", "DELETE", "PATCH"];

/** createWebComponentが属性値をJSON.parseするため、objectで来た場合は文字列へ戻す */
function toText(v: string | object | undefined): string {
  if (v == null) return "";
  if (typeof v === "string") return v;
  return JSON.stringify(v, null, 2);
}

function toFields(v: FieldOption[] | string | undefined): FieldOption[] {
  if (!v) return [];
  if (Array.isArray(v)) return v;
  try {
    const parsed = JSON.parse(v);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function toLabels(
  v: Partial<CurlLabels> | string | undefined,
): Partial<CurlLabels> | undefined {
  if (!v) return undefined;
  if (typeof v !== "string") return v;
  try {
    const parsed = JSON.parse(v);
    return typeof parsed === "object" && parsed !== null ? parsed : undefined;
  } catch {
    return undefined;
  }
}

/** app.request 等が返す様々な形のエラーを、人が読める文字列にする */
function stringifyError(err: unknown): string {
  if (err == null) return "不明なエラー";
  if (typeof err === "string") return err;
  if (typeof err === "object") {
    const o = err as Record<string, unknown>;
    if (typeof o.responseText === "string" && o.responseText) {
      return o.responseText;
    }
    if (typeof o.message === "string" && o.message) return o.message;
    try {
      return JSON.stringify(err);
    } catch {
      return String(err);
    }
  }
  return String(err);
}

/**
 * テスト送信はTestCurlAjaxアクションを叩く。
 * テスト送信では対象レコードが無いためフィールド変数は置換されず、入力そのままを送る。
 */
function defaultSendTest() {
  return (p: TestSendPayload): Promise<TestSendResult> => {
    const app = (window as unknown as { app?: any }).app;
    if (!app?.request?.post) {
      return Promise.resolve({
        success: false,
        error: "app.request is not available",
      });
    }
    return new Promise<TestSendResult>((resolve) => {
      app.request
        .post({
          data: {
            module: "Workflows",
            parent: "Settings",
            action: "TestCurlAjax",
            url: p.url,
            method: p.method,
            headers: p.headers,
            body: p.body,
            timeout: p.timeout,
          },
        })
        .then(
          (err: unknown, data: TestSendResult) => {
            if (err) resolve({ success: false, error: stringifyError(err) });
            else if (data == null)
              resolve({ success: false, error: "サーバから応答がありません" });
            else resolve(data);
          },
          // Deferredがrejectされた場合(通信失敗・非JSON応答など)
          (rejectErr: unknown) =>
            resolve({ success: false, error: stringifyError(rejectErr) }),
        );
    });
  };
}

export function CurlTaskForm(props: Props) {
  const [url, setUrl] = useState(toText(props.url));
  const [method, setMethod] = useState(props.method || "POST");
  const [headers, setHeaders] = useState(toText(props.headers));
  const [body, setBody] = useState(toText(props.body));
  const [timeout, setTimeoutValue] = useState(props.timeout || "30");

  const fields = useMemo(() => toFields(props.fieldsJson), [props.fieldsJson]);
  const labels = useMemo(
    () => mergeLabels(toLabels(props.labelsJson)),
    [props.labelsJson],
  );
  const hasExistingContent = body.trim() !== "" || headers.trim() !== "";

  const applyPresetResult = (r: {
    method: string;
    headers: string;
    body: string;
  }) => {
    setMethod(r.method);
    setHeaders(r.headers);
    setBody(r.body);
  };

  const sendTest = defaultSendTest();
  const getPayload = (): TestSendPayload => ({
    url,
    method,
    headers,
    body,
    timeout,
  });

  return (
    <div className="space-y-6 p-1">
      {/* 既存の保存パス(serializeFormData)が拾う入力。
          method/headers/bodyはUI(Select/エディタ)が直接name属性を持たないため隠しinputで同期する。 */}
      <input type="hidden" name="method" value={method} readOnly />
      <input type="hidden" name="headers" value={headers} readOnly />
      <input type="hidden" name="body" value={body} readOnly />

      <div className="space-y-1.5">
        <PresetSelector
          hasExistingContent={hasExistingContent}
          onApply={applyPresetResult}
          labelFor={(k) => presetLabel(labels, k)}
          presetLabel={labels.preset + ":"}
          confirmMessage={labels.presetOverwriteConfirm}
          okLabel={labels.ok}
          cancelLabel={labels.cancel}
        />
        <a
          href={ADAPTIVE_CARD_DESIGNER_URL}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-block text-xs text-blue-600 underline hover:text-blue-800"
        >
          {labels.adaptiveCardDesigner} ↗
        </a>
      </div>

      {/* URL */}
      <div className="space-y-1.5">
        <Label>
          {labels.url}
          <span className="text-red-600">*</span>
        </Label>
        <Input
          name="url"
          value={url}
          onChange={(e) => setUrl(e.target.value)}
        />
      </div>

      {/* Method */}
      <div className="space-y-1.5">
        <Label>{labels.method}</Label>
        <Select value={method} onValueChange={setMethod}>
          <SelectTrigger className="w-[200px]" aria-label={labels.method}>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {HTTP_METHODS.map((m) => (
              <SelectItem key={m} value={m}>
                {m}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Headers */}
      <div className="space-y-1.5">
        <Label>{labels.headers}</Label>
        <JsonTemplateEditor
          value={headers}
          onChange={setHeaders}
          fields={fields}
          rows={4}
          formatLabel={labels.format}
          insertLabel={labels.insertField}
        />
      </div>

      {/* Body */}
      <div className="space-y-1.5">
        <Label>{labels.body}</Label>
        <JsonTemplateEditor
          value={body}
          onChange={setBody}
          fields={fields}
          rows={10}
          validate
          formatLabel={labels.format}
          insertLabel={labels.insertField}
          validLabel={labels.jsonValid}
          invalidLabel={labels.jsonInvalid}
        />
      </div>

      {/* Timeout */}
      <div className="space-y-1.5">
        <Label>{labels.timeout}</Label>
        <Input
          name="timeout"
          type="number"
          min={1}
          max={60}
          className="w-[120px]"
          value={timeout}
          onChange={(e) => setTimeoutValue(e.target.value)}
        />
        <p className="text-xs text-muted-foreground">{labels.timeoutHelp}</p>
      </div>

      <TestSendPanel
        getPayload={getPayload}
        sendTest={sendTest}
        buttonLabel={labels.testSend}
        sendingLabel={labels.testSending}
        note={labels.testSendNote}
      />
    </div>
  );
}
