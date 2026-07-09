import { useMemo, useState } from "react";
import { cn } from "@/lib/utils";
import { PresetSelector } from "./PresetSelector";
import { JsonTemplateEditor } from "./JsonTemplateEditor";
import { FieldInserter } from "./FieldInserter";
import {
  TestSendPanel,
  TestSendPayload,
  TestSendResult,
} from "./TestSendPanel";
import { insertAtCursor } from "./jsonEditorUtils";
import { FieldOption } from "./types";
import { CurlLabels, mergeLabels, presetLabel } from "./labels";

interface Props {
  url?: string;
  method?: string;
  headers?: string | object;
  body?: string | object;
  timeout?: string;
  fieldsJson?: FieldOption[] | string;
  labelsJson?: Partial<CurlLabels> | string;
  recordId?: string;
  sourceModule?: string;
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

/** 本番の保存はapp.request経由。テスト送信はTestCurlAjaxアクションを叩く */
function defaultSendTest(
  recordId: string | undefined,
  sourceModule: string | undefined,
) {
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
            recordId: recordId || "",
            sourceModule: sourceModule || "",
          },
        })
        .then((err: unknown, data: TestSendResult) => {
          if (err) resolve({ success: false, error: String(err) });
          else resolve(data);
        });
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

  const insertIntoUrl = (variable: string) => {
    setUrl((cur) => insertAtCursor(cur, cur.length, cur.length, variable).text);
  };

  const sendTest = defaultSendTest(props.recordId, props.sourceModule);
  const getPayload = (): TestSendPayload => ({
    url,
    method,
    headers,
    body,
    timeout,
  });

  const inputCls =
    "border-input flex w-full rounded-sm border bg-transparent px-2 py-1 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50";

  return (
    <div className="space-y-4">
      {/* 既存の保存パス(serializeFormData)が拾う入力 */}
      <input type="hidden" name="headers" value={headers} readOnly />
      <input type="hidden" name="body" value={body} readOnly />

      <PresetSelector
        hasExistingContent={hasExistingContent}
        onApply={applyPresetResult}
        labelFor={(k) => presetLabel(labels, k)}
        presetLabel={labels.preset + ":"}
        confirmMessage={labels.presetOverwriteConfirm}
        okLabel={labels.ok}
        cancelLabel={labels.cancel}
      />

      {/* URL */}
      <div className="space-y-1">
        <label className="text-sm font-medium">
          {labels.url}
          <span className="text-red-600">*</span>
        </label>
        <div className="flex items-center gap-2">
          <input
            name="url"
            className={inputCls}
            value={url}
            onChange={(e) => setUrl(e.target.value)}
          />
          <FieldInserter
            fields={fields}
            onInsert={insertIntoUrl}
            placeholder={labels.insertField}
          />
        </div>
      </div>

      {/* Method */}
      <div className="space-y-1">
        <label className="text-sm font-medium">{labels.method}</label>
        <select
          name="method"
          className={cn(inputCls, "max-w-[200px]")}
          value={method}
          onChange={(e) => setMethod(e.target.value)}
        >
          {HTTP_METHODS.map((m) => (
            <option key={m} value={m}>
              {m}
            </option>
          ))}
        </select>
      </div>

      {/* Headers */}
      <div className="space-y-1">
        <label className="text-sm font-medium">{labels.headers}</label>
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
      <div className="space-y-1">
        <label className="text-sm font-medium">{labels.body}</label>
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
      <div className="space-y-1">
        <label className="text-sm font-medium">{labels.timeout}</label>
        <input
          name="timeout"
          type="number"
          min={1}
          max={60}
          className={cn(inputCls, "max-w-[120px]")}
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
      />
    </div>
  );
}
