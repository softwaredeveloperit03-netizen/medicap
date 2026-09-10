import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  EFFECTIVE_DATE,
  FORM_A_NO,
  MANUAL_PRODUCT,
  REVISION_NO,
  SOP_REF,
  defaultFromDate,
  defaultToDate,
  getEmpDisplayName,
  stampNow,
  statusClass,
} from '../cr.utils';

declare let alertify: any;

@Component({
  selector: 'app-conditional-release-request',
  templateUrl: './request-form.component.html',
  styleUrls: ['../cr.shared.css'],
  providers: [DatePipe],
})
export class RequestFormComponent implements OnInit {
  formNo = FORM_A_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;
  manualProduct = MANUAL_PRODUCT;

  isLog = false;
  isEdit = false;
  isView = false;
  editId = 0;
  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';
  showMineOnly = false;

  products: any[] = [];
  productSelectMode = '';
  selectedProduct: any = null;
  productName = '';
  productCode = '';
  productId = '';
  lotNo = '';
  sampleQuantity = '';
  dateRequired = '';
  description = '';
  justification = '';
  requestedBy = '';
  requestedByDate = '';
  deptApprovalBy = '';
  deptApprovalDate = '';
  rejectReason = '';

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
    this.requestedByDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    const view = this.route.snapshot.data['view'];
    this.isLog = view === 'log';
    this.isEdit = view === 'edit';
    if (this.isEdit) {
      this.editId = +this.route.snapshot.paramMap.get('id') || 0;
      this.loadForEdit();
      return;
    }
    if (this.isLog) {
      this.getLog();
      return;
    }
    this.getProducts();
    this.requestedBy = getEmpDisplayName();
    this.productSelectMode = 'master';
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
    if (this.selectedProduct === this.manualProduct) {
      this.productSelectMode = this.manualProduct;
      this.selectedProduct = null;
      this.productName = '';
      this.productCode = '';
      this.productId = '';
      return;
    }
    if (!this.selectedProduct) {
      this.productName = '';
      this.productCode = '';
      this.productId = '';
      return;
    }
    this.productSelectMode = 'master';
    this.productName =
      this.selectedProduct.product_name || this.selectedProduct.material_name || '';
    this.productCode =
      this.selectedProduct.product_code || this.selectedProduct.material_code || '';
    this.productId =
      this.selectedProduct.id || this.selectedProduct.product_id || this.selectedProduct.material_id || '';
  }

  stampRequestedBy(): void {
    this.requestedBy = stampNow(this.datePipe);
    this.requestedByDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
  }

  stampDeptApproval(): void {
    this.deptApprovalBy = stampNow(this.datePipe);
    this.deptApprovalDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
  }

  loadForEdit(): void {
    if (!this.editId) {
      alertify.error('Invalid request');
      this.router.navigate(['/qa/conditional-release/request/log']);
      return;
    }
    this.getProducts();
    this.service
      .get('qa/conditionalRelease.php?type=getConditionalReleaseById&id=' + this.editId)
      .subscribe((response: any) => {
        if (!response?.id) {
          alertify.error('Record not found');
          this.router.navigate(['/qa/conditional-release/request/log']);
          return;
        }
        if (response.status !== 'Rejected') {
          alertify.error('Only rejected requests can be edited');
          this.router.navigate(['/qa/conditional-release/request/log']);
          return;
        }
        this.rejectReason = response.reject_reason || '';
        this.productName = response.product_name || '';
        this.productCode = response.product_code || '';
        this.productId = response.product_id || '';
        this.lotNo = response.lot_no || '';
        this.sampleQuantity = response.sample_quantity || '';
        this.dateRequired = response.date_required || '';
        this.description = response.description || '';
        this.justification = response.justification || '';
        this.requestedBy = response.requested_by || getEmpDisplayName();
        this.requestedByDate = response.requested_by_date || this.requestedByDate;
        this.deptApprovalBy = response.dept_approval_by || '';
        this.deptApprovalDate = response.dept_approval_date || '';
        this.productSelectMode = this.manualProduct;
      });
  }

  getLog(): void {
    const mine = this.showMineOnly ? 'Yes' : '';
    this.service
      .get(
        'qa/conditionalRelease.php?type=getConditionalReleaseRequestLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate +
          '&mine_only=' +
          mine
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.service
      .get('qa/conditionalRelease.php?type=getConditionalReleaseById&id=' + record.id)
      .subscribe((response: any) => {
        if (response?.id) {
          this.selectedRecord = response;
          this.isView = true;
        }
      });
  }

  closeView(): void {
    this.isView = false;
    this.selectedRecord = null;
  }

  editRejected(record: any): void {
    this.router.navigate(['/qa/conditional-release/request/edit', record.id]);
  }

  canEdit(record: any): boolean {
    const empId = localStorage.getItem('emp_id') || '';
    return record?.status === 'Rejected' && record?.entry_by === empId;
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }

  save(form: NgForm): void {
    if (form.invalid || !this.productName.trim() || !this.lotNo.trim()) {
      alertify.error('Please fill required fields (Product and Lot #)');
      return;
    }
    if (!this.justification.trim()) {
      alertify.error('Justification is required');
      return;
    }

    const payload: any = {
      form_no: this.formNo,
      sop_ref: this.sopRef,
      product_name: this.productName.trim(),
      product_code: this.productCode.trim(),
      product_id: this.productId,
      lot_no: this.lotNo.trim(),
      sample_quantity: this.sampleQuantity,
      date_required: this.dateRequired,
      description: this.description,
      justification: this.justification,
      requested_by: this.requestedBy,
      requested_by_date: this.requestedByDate,
      dept_approval_by: this.deptApprovalBy,
      dept_approval_date: this.deptApprovalDate,
    };
    if (this.isEdit && this.editId) {
      payload.id = this.editId;
    }

    this.service
      .post('qa/conditionalRelease.php?type=saveConditionalReleaseRequest', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success(
            this.isEdit ? 'Request resubmitted to QA Head' : 'Request saved and sent to QA Head for approval'
          );
          this.router.navigate(['/qa/conditional-release/request/log']);
        } else {
          alertify.error(response?.status || response?.message || 'Failed to save');
        }
      });
  }

  downloadForm(id: number): void {
    this.service.open('qa/conditionalRelease.php?type=downloadConditionalReleaseRequestForm&id=' + id);
  }
}
