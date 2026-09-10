import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  DESTRUCTION_CRITERIA,
  DISPOSITION_OPTIONS,
  EFFECTIVE_DATE,
  FORM_A_NO,
  MANUAL_PRODUCT,
  NARCOTIC_OPTIONS,
  RETURN_STOCK_CRITERIA,
  REVISION_NO,
  SALVAGING_CRITERIA,
  SOP_REF,
  buildChecklistPayload,
  createChecklistDefaults,
  defaultFromDate,
  defaultToDate,
  getEmpDisplayName,
  parseChecklist,
  stampNow,
  statusClass,
} from '../rfp.utils';

declare let alertify: any;

@Component({
  selector: 'app-return-merchandise-report',
  templateUrl: './return-merchandise-report.component.html',
  styleUrls: ['../rfp.shared.css'],
  providers: [DatePipe],
})
export class ReturnMerchandiseReportComponent implements OnInit {
  formNo = FORM_A_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;
  manualProduct = MANUAL_PRODUCT;
  narcoticOptions = NARCOTIC_OPTIONS;
  dispositionOptions = DISPOSITION_OPTIONS;
  destructionCriteria = DESTRUCTION_CRITERIA;
  returnStockCriteria = RETURN_STOCK_CRITERIA;
  salvagingCriteria = SALVAGING_CRITERIA;

  isLog = false;
  isQaReview = false;
  isView = false;
  reviewId = 0;
  dept_head = 'No';
  results: any[] = [];
  pendingQa: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';

  products: any[] = [];
  productSelectMode = 'master';
  selectedProduct: any = null;
  returnMerchandiseNo = '';
  receivedDate = '';
  customerName = '';
  customerAddress = '';
  productDescription = '';
  productCode = '';
  productId = '';
  lotNumber = '';
  expiryDate = '';
  poNumber = '';
  originalPoDate = '';
  invoiceNumber = '';
  invoiceDate = '';
  creditNumber = '';
  creditDate = '';
  narcoticType = '';
  qtyReceived = '';
  inspectedBy = '';
  commentsReceiving = '';
  commentsQa = '';
  disposition = '';
  directorDesignate = '';
  directorDate = '';
  destructionChecks: boolean[] = createChecklistDefaults(DESTRUCTION_CRITERIA.length);
  returnStockChecks: boolean[] = createChecklistDefaults(RETURN_STOCK_CRITERIA.length);
  salvagingChecks: boolean[] = createChecklistDefaults(SALVAGING_CRITERIA.length);

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
    this.receivedDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
    this.inspectedBy = getEmpDisplayName();
  }

  ngOnInit(): void {
    const view = this.route.snapshot.data['view'];
    this.isLog = view === 'log';
    this.isQaReview = view === 'qa-review';
    this.loadRights();
    if (this.isQaReview) {
      this.reviewId = +this.route.snapshot.paramMap.get('id') || 0;
      this.loadForQaReview();
      return;
    }
    if (this.isLog) {
      this.getLog();
      this.loadPendingQa();
      return;
    }
    this.getProducts();
  }

  loadRights(): void {
    const empId = localStorage.getItem('emp_id');
    if (!empId) return;
    this.service.get('hr/employee.php?type=getrights&emp_id=' + encodeURIComponent(empId)).subscribe((response: any) => {
      const r = Array.isArray(response) && response[0] ? response[0] : {};
      this.dept_head = r.dept_head || 'No';
    });
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
      this.productDescription = '';
      this.productCode = '';
      this.productId = '';
      return;
    }
    if (!this.selectedProduct) {
      this.productDescription = '';
      this.productCode = '';
      this.productId = '';
      return;
    }
    this.productSelectMode = 'master';
    this.productDescription =
      this.selectedProduct.product_name || this.selectedProduct.material_name || '';
    this.productCode = this.selectedProduct.product_code || this.selectedProduct.material_code || '';
    this.productId = this.selectedProduct.id || this.selectedProduct.product_id || '';
  }

  stampInspectedBy(): void {
    this.inspectedBy = stampNow(this.datePipe);
  }

  stampDirector(): void {
    this.directorDesignate = stampNow(this.datePipe);
    this.directorDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
  }

  getLog(): void {
    this.service
      .get(
        'qa/returnedFinishedProducts.php?type=getReturnMerchandiseReportLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  loadPendingQa(): void {
    this.service.get('qa/returnedFinishedProducts.php?type=getPendingReturnMerchandiseQa').subscribe((response: any) => {
      this.pendingQa = Array.isArray(response) ? response : [];
    });
  }

  loadForQaReview(): void {
    if (!this.reviewId) {
      alertify.error('Invalid record');
      this.router.navigate(['/qa/returned-finished-products/report/log']);
      return;
    }
    this.service
      .get('qa/returnedFinishedProducts.php?type=getReturnMerchandiseById&id=' + this.reviewId)
      .subscribe((response: any) => {
        if (!response?.id) {
          alertify.error('Record not found');
          this.router.navigate(['/qa/returned-finished-products/report/log']);
          return;
        }
        if (response.status !== 'Pending QA') {
          alertify.error('This record is not pending QA review');
          this.router.navigate(['/qa/returned-finished-products/report/log']);
          return;
        }
        this.populateFromRecord(response);
        this.isQaReview = true;
      });
  }

  populateFromRecord(record: any): void {
    this.returnMerchandiseNo = record.return_merchandise_no || '';
    this.receivedDate = record.received_date || '';
    this.customerName = record.customer_name || '';
    this.customerAddress = record.customer_address || '';
    this.productDescription = record.product_description || '';
    this.productCode = record.product_code || '';
    this.productId = record.product_id || '';
    this.lotNumber = record.lot_number || '';
    this.expiryDate = record.expiry_date || '';
    this.poNumber = record.po_number || '';
    this.originalPoDate = record.original_po_date || '';
    this.invoiceNumber = record.invoice_number || '';
    this.invoiceDate = record.invoice_date || '';
    this.creditNumber = record.credit_number || '';
    this.creditDate = record.credit_date || '';
    this.narcoticType = record.narcotic_type || '';
    this.qtyReceived = record.qty_received || '';
    this.inspectedBy = record.inspected_by || '';
    this.commentsReceiving = record.comments_receiving || '';
    this.commentsQa = record.comments_qa || '';
    this.disposition = record.disposition || '';
    this.directorDesignate = record.director_designate || '';
    this.directorDate = record.director_date || '';
    const parsed = parseChecklist(record);
    this.destructionChecks = parsed.destruction;
    this.returnStockChecks = parsed.returnStock;
    this.salvagingChecks = parsed.salvaging;
  }

  view(record: any): void {
    this.service
      .get('qa/returnedFinishedProducts.php?type=getReturnMerchandiseById&id=' + record.id)
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

  getStatusClass(status: string): string {
    return statusClass(status);
  }

  saveReceiving(form: NgForm): void {
    if (form.invalid || !this.productDescription.trim() || !this.lotNumber.trim()) {
      alertify.error('Please fill required fields (Product and Lot Number)');
      return;
    }
    const payload = {
      form_no: this.formNo,
      sop_ref: this.sopRef,
      received_date: this.receivedDate,
      customer_name: this.customerName,
      customer_address: this.customerAddress,
      product_description: this.productDescription,
      product_code: this.productCode,
      product_id: this.productId,
      lot_number: this.lotNumber,
      expiry_date: this.expiryDate,
      po_number: this.poNumber,
      original_po_date: this.originalPoDate,
      invoice_number: this.invoiceNumber,
      invoice_date: this.invoiceDate,
      credit_number: this.creditNumber,
      credit_date: this.creditDate,
      narcotic_type: this.narcoticType,
      qty_received: this.qtyReceived,
      inspected_by: this.inspectedBy,
      comments_receiving: this.commentsReceiving,
    };
    this.service
      .post('qa/returnedFinishedProducts.php?type=saveReturnMerchandiseReport', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Report saved. Return # ' + (response.return_merchandise_no || '') + ' sent to QA for review.');
          this.router.navigate(['/qa/returned-finished-products/report/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      });
  }

  completeQaReview(): void {
    if (!this.disposition) {
      alertify.error('Please select disposition (Return to Stock or Rejected)');
      return;
    }
    const payload = {
      id: this.reviewId,
      comments_qa: this.commentsQa,
      assessment_checklist: buildChecklistPayload(
        this.destructionChecks,
        this.returnStockChecks,
        this.salvagingChecks
      ),
      disposition: this.disposition,
      director_designate: this.directorDesignate,
      director_date: this.directorDate,
    };
    this.service
      .post('qa/returnedFinishedProducts.php?type=completeReturnMerchandiseQaReview', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('QA review completed. Record added to Return of Merchandise Log.');
          this.router.navigate(['/qa/returned-finished-products/log']);
        } else {
          alertify.error(response?.status || 'Failed to complete QA review');
        }
      });
  }

  downloadForm(id: number): void {
    this.service.open('qa/returnedFinishedProducts.php?type=downloadReturnMerchandiseReportForm&id=' + id);
  }

  goQaReview(record: any): void {
    this.router.navigate(['/qa/returned-finished-products/report/qa-review', record.id]);
  }
}
