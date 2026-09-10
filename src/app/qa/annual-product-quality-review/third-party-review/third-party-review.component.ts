import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  EFFECTIVE_DATE,
  FORM_THIRD_PARTY_NO,
  MANUAL_PRODUCT,
  QA_AGREEMENT_STATUS,
  REVISION_NO,
  SOP_REF,
  THIRD_PARTY_DISPOSITION,
  defaultFromDate,
  defaultToDate,
  getEmpDisplayName,
  statusClass,
} from '../apqr.utils';

declare let alertify: any;

@Component({
  selector: 'app-apqr-third-party-review',
  templateUrl: './third-party-review.component.html',
  styleUrls: ['../apqr.shared.css'],
  providers: [DatePipe],
})
export class ThirdPartyReviewComponent implements OnInit {
  formNo = FORM_THIRD_PARTY_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;
  manualProduct = MANUAL_PRODUCT;
  dispositionOptions = THIRD_PARTY_DISPOSITION;
  agreementStatusOptions = QA_AGREEMENT_STATUS;

  isLog = false;
  isView = false;
  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';

  products: any[] = [];
  productSelectMode = 'master';
  thirdPartyName = '';
  productCode = '';
  productName = '';
  productId = '';
  regulatoryRef = '';
  reportingFrom = '';
  reportingTo = '';
  reportReceivedDate = '';
  qualityAgreementReview = '';
  qualityAgreementStatus = '';
  completenessAssessment = '';
  complianceAssessment = '';
  additionalInfoRequired = 'No';
  additionalInfoDetails = '';
  finalDisposition = '';
  fileReference = '';
  remarks = '';
  qaReviewer = '';

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
    this.reportReceivedDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
    this.qaReviewer = getEmpDisplayName();
  }

  ngOnInit(): void {
    this.isLog = this.route.snapshot.data['view'] === 'log';
    if (this.isLog) {
      this.getLog();
    } else {
      this.getProducts();
    }
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
      return;
    }
    const p = this.products.find((x) => (x.product_code || x.code) === this.productCode);
    if (p) {
      this.productName = p.product_name || '';
      this.productId = String(p.id || p.product_id || '');
      this.regulatoryRef = p.regulatory_ref || p.nda_anda || p.approval_no || '';
    }
  }

  buildPayload(): any {
    return {
      third_party_name: this.thirdPartyName,
      product_code: this.productCode,
      product_name: this.productName,
      product_id: this.productId,
      regulatory_ref: this.regulatoryRef,
      reporting_from: this.reportingFrom,
      reporting_to: this.reportingTo,
      report_received_date: this.reportReceivedDate,
      quality_agreement_review: this.qualityAgreementReview,
      quality_agreement_status: this.qualityAgreementStatus,
      completeness_assessment: this.completenessAssessment,
      compliance_assessment: this.complianceAssessment,
      additional_info_required: this.additionalInfoRequired,
      additional_info_details: this.additionalInfoDetails,
      final_disposition: this.finalDisposition,
      file_reference: this.fileReference,
      remarks: this.remarks,
      qa_reviewer: this.qaReviewer,
    };
  }

  save(form: NgForm): void {
    if (form.invalid) {
      alertify.error('Complete required fields');
      return;
    }
    if (!this.finalDisposition) {
      alertify.error('Select final disposition');
      return;
    }
    this.service
      .post('qa/annualProductQualityReview.php?type=saveThirdPartyReview', JSON.stringify(this.buildPayload()))
      .subscribe(
        (response: any) => {
          if (response?.status === 'success') {
            alertify.success('Third party review saved');
            this.router.navigate(['/qa/annual-product-quality-review/third-party/log']);
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
    this.service
      .get('qa/annualProductQualityReview.php?type=getThirdPartyReviewLog&' + q)
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  viewRecord(record: any): void {
    this.selectedRecord = record;
    this.isView = true;
    this.thirdPartyName = record.third_party_name || '';
    this.productCode = record.product_code || '';
    this.productName = record.product_name || '';
    this.regulatoryRef = record.regulatory_ref || '';
    this.reportingFrom = record.reporting_from || '';
    this.reportingTo = record.reporting_to || '';
    this.reportReceivedDate = record.report_received_date || '';
    this.qualityAgreementReview = record.quality_agreement_review || '';
    this.qualityAgreementStatus = record.quality_agreement_status || '';
    this.completenessAssessment = record.completeness_assessment || '';
    this.complianceAssessment = record.compliance_assessment || '';
    this.additionalInfoRequired = record.additional_info_required || 'No';
    this.additionalInfoDetails = record.additional_info_details || '';
    this.finalDisposition = record.final_disposition || '';
    this.fileReference = record.file_reference || '';
    this.remarks = record.remarks || '';
    this.qaReviewer = record.qa_reviewer || '';
  }

  closeView(): void {
    this.isView = false;
    this.selectedRecord = null;
  }

  downloadPdf(record: any): void {
    this.service.open('qa/annualProductQualityReview.php?type=downloadThirdPartyReviewForm&id=' + record.id);
  }

  statusClass(status: string): string {
    return statusClass(status);
  }
}
