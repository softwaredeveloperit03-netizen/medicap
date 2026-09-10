export interface MasterExcelColumn {
  key: string;
  header: string;
  required?: boolean;
  width?: number;
  /** Optional example value shown in the first sample data row */
  sample?: string;
  /** Optional hint shown on the Instructions sheet */
  note?: string;
}

export interface MasterExcelConfig {
  id: string;
  title: string;
  sheetName: string;
  filePrefix: string;
  uploadUrl: string;
  columns: MasterExcelColumn[];
}

export interface MasterExcelUploadResult {
  status: string;
  inserted?: number;
  failed?: number;
  errors?: Array<{ row?: number; message?: string } | string>;
  message?: string;
}
