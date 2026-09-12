import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { ecvHomePath, ecvHubPath } from '../ecv.utils';

declare let alertify: any;

@Component({
  selector: 'app-ecv-approval-new',
  templateUrl: './approval-new.component.html',
  styleUrls: ['../new/new.component.css'],
  providers: [DatePipe],
})
export class ApprovalNewComponent implements OnInit {
  formNo = 'WI-QA-002-02';
  revisionNo = '00';
  effectiveDate = '2025-04-01';
  hubPath = ecvHubPath();
  homePath = ecvHomePath();
  stamp = '';

  pendingSamples: any[] = [];
  selectedSample: any = null;

  equipmentName = '';
  equipmentId = '';
  previousProductName = '';
  productCode = '';
  qcLabSampleNo = '';
  lotNo = '';
  cleaningDate = '';
  sampleType = '';

  visualInspection = '';
  productionByDate = '';
  phResult = '';
  tocResult = '';
  residueResult = '';
  microbialResult = '';
  qcMeetsSpec = '';
  qcByDate = '';
  qaMeetsCriteria = '';
  approvedForProduction = '';
  qaByDate = '';
  comments = '';
  saving = false;
  submitted = false;
  fieldErrors: { [key: string]: string } = {};

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {}

  ngOnInit(): void {
    this.loadStamp();
    this.loadPendingSamples();
  }

  loadStamp(): void {
    this.service.get('qa/equipmentCleaningVerification.php?type=getEquipmentCleaningStamp').subscribe(
      (response: any) => {
        this.stamp = response?.stamp || this.fallbackStamp();
        this.productionByDate = this.stamp;
      },
      () => {
        this.stamp = this.fallbackStamp();
        this.productionByDate = this.stamp;
      }
    );
  }

  fallbackStamp(): string {
    const name =
      localStorage.getItem('emp_name') ||
      localStorage.getItem('username') ||
      localStorage.getItem('emp_id') ||
      '';
    const empId = localStorage.getItem('emp_id') || '';
    const when = this.datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm') || '';
    return name ? name + (empId ? ' (' + empId + ')' : '') + ' - ' + when : when;
  }

  loadPendingSamples(): void {
    this.service.get('qa/equipmentCleaningVerification.php?type=getPendingVerificationForApproval').subscribe((response: any) => {
      const rows = Array.isArray(response) ? response : [];
      this.pendingSamples = rows.map((row) => ({
        ...row,
        display_label: this.sampleLabel(row),
      }));
    });
  }

  onSampleChange(): void {
    if (!this.selectedSample) {
      this.equipmentName = '';
      this.equipmentId = '';
      this.previousProductName = '';
      this.productCode = '';
      this.qcLabSampleNo = '';
      this.lotNo = '';
      this.cleaningDate = '';
      this.sampleType = '';
    } else {
      this.equipmentName = this.selectedSample.equipment_name || '';
      this.equipmentId = this.selectedSample.equipment_id || '';
      this.previousProductName = this.selectedSample.previous_product_name || '';
      this.productCode = this.selectedSample.product_code || '';
      this.qcLabSampleNo = this.selectedSample.qc_lab_sample_no || '';
      this.lotNo = this.selectedSample.lot_no || '';
      this.cleaningDate = this.selectedSample.cleaning_date || '';
      this.sampleType = this.selectedSample.sample_type || '';
    }
    if (this.submitted) {
      this.validateForm();
    }
  }

  onQcChange(): void {
    this.qcByDate = this.hasQcData() ? this.stamp : '';
    if (this.submitted) {
      this.validateForm();
    }
  }

  onQaChange(): void {
    this.qaByDate = this.approvedForProduction || this.qaMeetsCriteria ? this.stamp : '';
    if (this.submitted) {
      this.validateForm();
    }
  }

  hasQcData(): boolean {
    return !!(this.phResult || this.tocResult || this.residueResult || this.microbialResult || this.qcMeetsSpec);
  }

  sampleLabel(row: any): string {
    return (
      (row.equipment_name || 'Equipment') +
      ' / ' +
      (row.equipment_id || '-') +
      ' / Lot ' +
      (row.lot_no || '-') +
      ' / ' +
      (row.sample_type || '')
    );
  }

  validateForm(): boolean {
    const errors: { [key: string]: string } = {};

    if (!this.selectedSample) {
      errors.sample = 'Select a Sample Collection record';
    }
    if (!this.visualInspection) {
      errors.visual = 'Visual inspection is required';
    }
    if (this.hasQcData() && !this.qcMeetsSpec) {
      errors.qcMeetsSpec = 'Select whether QC results meet specifications';
    }
    if (this.approvedForProduction === 'Yes') {
      if (this.visualInspection !== 'Yes') {
        errors.visual = 'Visual inspection must be Yes to approve production';
      }
      if (this.qcMeetsSpec !== 'Yes') {
        errors.qcMeetsSpec = 'QC results must meet specifications to approve production';
      }
      if (!this.qaMeetsCriteria) {
        errors.qaMeetsCriteria = 'Select QA acceptance criteria';
      }
    }
    if ((this.qaMeetsCriteria || this.approvedForProduction) && !this.approvedForProduction) {
      errors.approved = 'Select Approved to start production';
    }

    this.fieldErrors = errors;
    return Object.keys(errors).length === 0;
  }

  save(form: NgForm): void {
    this.submitted = true;
    if (!this.validateForm() || form.invalid) {
      alertify.error(Object.values(this.fieldErrors)[0] || 'Please fill all required fields');
      return;
    }
    if (this.approvedForProduction === 'Yes' && (this.visualInspection !== 'Yes' || this.qcMeetsSpec !== 'Yes')) {
      alertify.error('Cannot approve production until visual inspection and QC results meet specifications');
      return;
    }

    this.saving = true;
    const payload = {
      form_no: this.formNo,
      verification_id: this.selectedSample.id,
      equipment_name: this.equipmentName,
      equipment_id: this.equipmentId,
      previous_product_name: this.previousProductName,
      product_code: this.productCode,
      qc_lab_sample_no: this.qcLabSampleNo,
      lot_no: this.lotNo,
      cleaning_date: this.cleaningDate,
      sample_type: this.sampleType,
      visual_inspection: this.visualInspection,
      ph_result: this.phResult,
      toc_result: this.tocResult,
      residue_result: this.residueResult,
      microbial_result: this.microbialResult,
      qc_meets_spec: this.qcMeetsSpec,
      qa_meets_criteria: this.qaMeetsCriteria,
      approved_for_production: this.approvedForProduction,
      comments: this.comments,
    };

    this.service
      .post('qa/equipmentCleaningVerification.php?type=saveEquipmentCleaningApproval', JSON.stringify(payload))
      .subscribe(
        (response: any) => {
          this.saving = false;
          if (response?.status === 'success') {
            alertify.success('Approval saved');
            this.router.navigate([this.hubPath + '/approval/log']);
          } else {
            alertify.error(response?.status || 'Failed to save approval');
          }
        },
        () => {
          this.saving = false;
          alertify.error('Failed to save approval');
        }
      );
  }
}
