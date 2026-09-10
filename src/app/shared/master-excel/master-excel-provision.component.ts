import { Component, EventEmitter, Input, Output } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { getMasterExcelConfig } from './master-excel.config';
import { MasterExcelService } from './master-excel.service';
import { MasterExcelConfig, MasterExcelUploadResult } from './master-excel.types';

declare let alertify: any;

@Component({
  selector: 'app-master-excel-provision',
  templateUrl: './master-excel-provision.component.html',
  styleUrls: ['./master-excel-provision.component.css'],
})
export class MasterExcelProvisionComponent {
  @Input() configId = '';
  @Input() buttonLabel = 'Excel Provision';
  @Input() buttonClass = 'btn btn-sm btn-primary';
  @Output() uploaded = new EventEmitter<MasterExcelUploadResult>();

  open = false;
  downloading = false;
  uploading = false;
  selectedFile: File | null = null;
  clientErrors: string[] = [];
  result: MasterExcelUploadResult | null = null;

  constructor(
    private excel: MasterExcelService,
    private service: DataAccessService
  ) {}

  get config(): MasterExcelConfig {
    return getMasterExcelConfig(this.configId);
  }

  openModal(): void {
    this.open = true;
    this.selectedFile = null;
    this.clientErrors = [];
    this.result = null;
  }

  closeModal(): void {
    this.open = false;
  }

  async downloadFormat(): Promise<void> {
    this.downloading = true;
    try {
      await this.excel.downloadTemplate(this.config);
      alertify.success('Excel format downloaded');
    } catch (e) {
      console.error(e);
      alertify.error('Unable to download Excel format');
    } finally {
      this.downloading = false;
    }
  }

  onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.selectedFile = input.files && input.files.length ? input.files[0] : null;
    this.clientErrors = [];
    this.result = null;
  }

  async upload(): Promise<void> {
    if (!this.selectedFile) {
      alertify.error('Please choose an Excel file');
      return;
    }
    this.uploading = true;
    this.clientErrors = [];
    this.result = null;
    try {
      const rows = await this.excel.parseWorkbook(this.selectedFile, this.config.columns);
      const { ok, errors } = this.excel.validateRows(rows, this.config.columns);
      this.clientErrors = errors;
      if (!ok.length) {
        alertify.error(errors.length ? 'No valid rows to upload' : 'Excel file has no data rows');
        this.uploading = false;
        return;
      }
      this.service.post(this.config.uploadUrl, JSON.stringify(ok)).subscribe({
        next: (response: any) => {
          this.uploading = false;
          this.result = response || { status: 'error', message: 'Empty response' };
          if (String(this.result.status).toLowerCase() === 'success') {
            alertify.success(
              `Uploaded: ${this.result.inserted || 0} inserted` +
                (this.result.failed ? `, ${this.result.failed} failed` : '')
            );
            this.uploaded.emit(this.result);
          } else {
            alertify.error(this.result.message || this.result.status || 'Upload failed');
          }
        },
        error: (err) => {
          this.uploading = false;
          console.error(err);
          alertify.error('Upload request failed');
        },
      });
    } catch (e: any) {
      this.uploading = false;
      console.error(e);
      alertify.error(e?.message || 'Unable to read Excel file');
    }
  }
}
