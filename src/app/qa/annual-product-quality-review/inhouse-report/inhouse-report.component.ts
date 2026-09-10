import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  APQR_SECTIONS,
  ApqrReportData,
  EFFECTIVE_DATE,
  FORM_INHOUSE_NO,
  MANUAL_PRODUCT,
  OVERALL_RATING_OPTIONS,
  REVISION_NO,
  SOP_REF,
  createDefaultReportData,
  createEmptyBatchRow,
  createEmptyChangeControlRow,
  createEmptyInvestigationRow,
  createEmptyRecallRow,
  createEmptyReturnRow,
  defaultFromDate,
  defaultToDate,
  getEmpDisplayName,
  parseReportData,
  statusClass,
} from '../apqr.utils';

declare let alertify: any;

@Component({
  selector: 'app-apqr-inhouse-report',
  templateUrl: './inhouse-report.component.html',
  styleUrls: ['../apqr.shared.css'],
  providers: [DatePipe],
})
export class InhouseReportComponent implements OnInit {
  formNo = FORM_INHOUSE_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;
  manualProduct = MANUAL_PRODUCT;
  sections = APQR_SECTIONS;
  ratingOptions = OVERALL_RATING_OPTIONS;

  isLog = false;
  isEdit = false;
  isView = false;
  editId = 0;
  activeSection = 'executive_summary';
  loadingSource = false;

  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';

  products: any[] = [];
  productSelectMode = 'master';
  selectedProduct: any = null;
  productCode = '';
  productName = '';
  productId = '';
  grade = '';
  genericName = '';
  regulatoryRef = '';
  reportingFrom = '';
  reportingTo = '';
  reportTitle = 'Annual Product Quality Review';
  status = 'Draft';
  reportData: ApqrReportData = createDefaultReportData();

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
    this.reportingFrom = this.fromDate;
    this.reportingTo = this.toDate;
  }

  ngOnInit(): void {
    const view = this.route.snapshot.data['view'];
    this.isLog = view === 'log';
    this.isEdit = view === 'edit';
    this.isView = view === 'view';
    if (this.isEdit || this.isView) {
      this.editId = +this.route.snapshot.paramMap.get('id') || 0;
      this.loadRecord();
      return;
    }
    if (this.isLog) {
      this.getLog();
      return;
    }
    this.getProducts();
  }

  getProducts(): void {
    this.service.get('master/product.php?type=getBrandProductsLog').subscribe(
      (response: any) => {
        this.products = Array.isArray(response) ? response : [];
        if (!this.products.length) {
          this.service.get('common.php?type=getProducts').subscribe((res: any) => {
            this.products = Array.isArray(res) ? res : [];
          });
        }
      },
      () => {
        this.service.get('common.php?type=getProducts').subscribe((res: any) => {
          this.products = Array.isArray(res) ? res : [];
        });
      }
    );
  }

  onProductChange(): void {
    if (this.productSelectMode === 'manual') {
      this.selectedProduct = null;
      return;
    }
    const code = this.productCode;
    this.selectedProduct = this.products.find((p) => (p.product_code || p.code) === code) || null;
    if (this.selectedProduct) {
      this.productName = this.selectedProduct.product_name || '';
      this.productId = String(this.selectedProduct.id || this.selectedProduct.product_id || '');
      this.grade = this.selectedProduct.grade || '';
      this.genericName = this.selectedProduct.generic_name || '';
      this.regulatoryRef =
        this.selectedProduct.regulatory_ref ||
        this.selectedProduct.nda_anda ||
        this.selectedProduct.approval_no ||
        '';
    }
  }

  setSection(key: string): void {
    this.activeSection = key;
  }

  addBatchRow(): void {
    this.reportData.manufacturing_batches.push(createEmptyBatchRow());
  }

  removeBatchRow(index: number): void {
    if (this.reportData.manufacturing_batches.length > 1) {
      this.reportData.manufacturing_batches.splice(index, 1);
    }
  }

  addInvestigationRow(): void {
    this.reportData.investigations.push(createEmptyInvestigationRow());
  }

  removeInvestigationRow(index: number): void {
    if (this.reportData.investigations.length > 1) {
      this.reportData.investigations.splice(index, 1);
    }
  }

  addRecallRow(): void {
    this.reportData.recalls.push(createEmptyRecallRow());
  }

  removeRecallRow(index: number): void {
    if (this.reportData.recalls.length > 1) {
      this.reportData.recalls.splice(index, 1);
    }
  }

  addReturnRow(): void {
    this.reportData.returns.push(createEmptyReturnRow());
  }

  removeReturnRow(index: number): void {
    if (this.reportData.returns.length > 1) {
      this.reportData.returns.splice(index, 1);
    }
  }

  addChangeControlRow(): void {
    this.reportData.change_controls.push(createEmptyChangeControlRow());
  }

  removeChangeControlRow(index: number): void {
    if (this.reportData.change_controls.length > 1) {
      this.reportData.change_controls.splice(index, 1);
    }
  }

  loadSourceData(): void {
    if (this.productSelectMode === 'master' && this.productCode) {
      this.onProductChange();
    }
    if (!this.productCode && !this.productName) {
      alertify.error('Select or enter a product first');
      return;
    }
    if (!this.reportingFrom || !this.reportingTo) {
      alertify.error('Enter reporting period dates');
      return;
    }
    this.loadingSource = true;
    const q =
      'product_code=' +
      encodeURIComponent(this.productCode || '') +
      '&product_name=' +
      encodeURIComponent(this.productName || '') +
      '&from_date=' +
      encodeURIComponent(this.reportingFrom) +
      '&to_date=' +
      encodeURIComponent(this.reportingTo);
    this.service.get('qa/annualProductQualityReview.php?type=getApqrSourceData&' + q).subscribe(
      (response: any) => {
        this.loadingSource = false;
        if (!response || response.error || response.status === 'error') {
          alertify.error(response?.error || response?.message || 'Could not load source data');
          return;
        }
        if (Array.isArray(response.manufacturing_batches) && response.manufacturing_batches.length) {
          this.reportData.manufacturing_batches = response.manufacturing_batches;
        }
        if (Array.isArray(response.investigations) && response.investigations.length) {
          this.reportData.investigations = response.investigations;
        }
        if (Array.isArray(response.recalls) && response.recalls.length) {
          this.reportData.recalls = response.recalls;
        }
        if (Array.isArray(response.returns) && response.returns.length) {
          this.reportData.returns = response.returns;
        }
        if (Array.isArray(response.change_controls) && response.change_controls.length) {
          this.reportData.change_controls = response.change_controls;
        }
        if (response.quality_complaints_summary) {
          this.reportData.quality_complaints_summary = response.quality_complaints_summary;
        }
        if (response.manufacturing_history_notes) {
          this.reportData.manufacturing_history_notes = response.manufacturing_history_notes;
        }
        if (response.manufacturing_yield_notes) {
          this.reportData.manufacturing_yield_notes = response.manufacturing_yield_notes;
        }
        alertify.success('Source data loaded — review each section and complete remaining fields');
      },
      (err) => {
        this.loadingSource = false;
        alertify.error('Failed to load source data. Ensure annualProductQualityReview.php is deployed on the server.');
        console.error('APQR source data error', err);
      }
    );
  }

  buildPayload(submitStatus: string): any {
    return {
      id: this.editId || undefined,
      product_code: this.productCode,
      product_name: this.productName,
      product_id: this.productId,
      grade: this.grade,
      generic_name: this.genericName,
      regulatory_ref: this.regulatoryRef,
      reporting_from: this.reportingFrom,
      reporting_to: this.reportingTo,
      report_title: this.reportTitle,
      status: submitStatus,
      report_data: this.reportData,
      prepared_by: getEmpDisplayName(),
    };
  }

  saveDraft(form: NgForm): void {
    if (form.invalid) {
      alertify.error('Complete required cover page fields');
      return;
    }
    this.persist('Draft');
  }

  submitForReview(form: NgForm): void {
    if (form.invalid) {
      alertify.error('Complete required cover page fields');
      return;
    }
    if (!this.reportData.overall_rating) {
      alertify.error(
        'Select Overall Review Rating on the cover page (SOP §3.1.15: Acceptable or Acceptable with conditions)'
      );
      return;
    }
    this.persist('Pending Review');
  }

  approveRecord(): void {
    this.service
      .post(
        'qa/annualProductQualityReview.php?type=approveInhouseApqr',
        JSON.stringify({ id: this.selectedRecord.id })
      )
      .subscribe(
        (response: any) => {
          if (response?.status === 'success') {
            alertify.success('APQR approved');
            this.isView = false;
            this.selectedRecord = null;
            this.getLog();
          } else {
            alertify.error(response?.message || 'Approval failed');
          }
        },
        () => alertify.error('Approval failed')
      );
  }

  persist(status: string): void {
    const payload = this.buildPayload(status);
    this.service
      .post('qa/annualProductQualityReview.php?type=saveInhouseApqr', JSON.stringify(payload))
      .subscribe(
        (response: any) => {
          if (response?.status === 'success') {
            alertify.success(status === 'Draft' ? 'Draft saved' : 'Submitted for review');
            this.router.navigate(['/qa/annual-product-quality-review/inhouse/log']);
          } else {
            alertify.error(response?.message || 'Save failed');
          }
        },
        () => alertify.error('Save failed')
      );
  }

  getLog(): void {
    const q =
      'from_date=' +
      encodeURIComponent(this.fromDate) +
      '&to_date=' +
      encodeURIComponent(this.toDate);
    this.service.get('qa/annualProductQualityReview.php?type=getInhouseApqrLog&' + q).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  loadRecord(): void {
    this.service
      .get('qa/annualProductQualityReview.php?type=getInhouseApqrById&id=' + this.editId)
      .subscribe((response: any) => {
        if (!response || response.error) {
          alertify.error('Record not found');
          this.router.navigate(['/qa/annual-product-quality-review/inhouse/log']);
          return;
        }
        this.applyRecord(response);
      });
  }

  applyRecord(record: any): void {
    this.productCode = record.product_code || '';
    this.productName = record.product_name || '';
    this.productId = record.product_id || '';
    this.grade = record.grade || '';
    this.genericName = record.generic_name || '';
    this.regulatoryRef = record.regulatory_ref || '';
    this.reportingFrom = record.reporting_from || '';
    this.reportingTo = record.reporting_to || '';
    this.reportTitle = record.report_title || 'Annual Product Quality Review';
    this.status = record.status || 'Draft';
    this.reportData = parseReportData(record.report_data);
    if (!this.isView) {
      this.getProducts();
    }
  }

  viewRecord(record: any): void {
    this.selectedRecord = record;
    this.isView = true;
    this.applyRecord(record);
  }

  closeView(): void {
    this.isView = false;
    this.selectedRecord = null;
    this.reportData = createDefaultReportData();
  }

  editRecord(record: any): void {
    this.router.navigate(['/qa/annual-product-quality-review/inhouse/edit', record.id]);
  }

  downloadPdf(record: any): void {
    this.service.open('qa/annualProductQualityReview.php?type=downloadInhouseApqrForm&id=' + record.id);
  }

  statusClass(status: string): string {
    return statusClass(status);
  }
}
