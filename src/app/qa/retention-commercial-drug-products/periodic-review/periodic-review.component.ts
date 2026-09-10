import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  ADD_NEW_UNIT,
  defaultFromDate,
  defaultToDate,
  hasStamp,
  loadUnits,
  saveUnitMaster,
} from '../retention.utils';

declare let alertify: any;

@Component({
  selector: 'app-periodic-review',
  templateUrl: './periodic-review.component.html',
  styleUrls: ['../retention.shared.css'],
  providers: [DatePipe],
})
export class PeriodicReviewComponent implements OnInit {
  formNo = 'FQA-008-B';
  revisionNo = '00';
  effectiveDate = '2025-04-16';
  isLog = false;
  isView = false;
  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';
  stamping = false;

  products: any[] = [];
  units: any[] = [];
  selectedProduct: any = null;
  isAddUnit = false;
  newUnitName = '';
  savingUnit = false;
  reviewDate = '';
  productName = '';
  productCode = '';
  batchNo = '';
  storageLocation = '';
  rackShelfNo = '';
  quantityOnHand = '';
  unit = '';
  packCondition = '';
  labelCondition = '';
  sealIntact = '';
  storageConditionMaintained = '';
  actionTaken = '';
  comments = '';

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
    this.reviewDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
  }

  ngOnInit(): void {
    this.isLog = this.route.snapshot.data['view'] === 'log';
    if (this.isLog) {
      this.getLog();
    } else {
      this.getProducts();
      this.getUnits();
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

  getUnits(): void {
    loadUnits(this.service).subscribe(
      (response: any) => {
        this.units = Array.isArray(response) ? response : [];
      },
      () => {
        this.service.get('common.php?type=getUnits').subscribe((res: any) => {
          this.units = Array.isArray(res) ? res : [];
        });
      }
    );
  }

  onUnitChange(value: string): void {
    if (value === ADD_NEW_UNIT) {
      this.unit = '';
      this.newUnitName = '';
      this.isAddUnit = true;
    }
  }

  closeAddUnit(): void {
    this.isAddUnit = false;
    this.newUnitName = '';
  }

  saveNewUnit(): void {
    const unitName = (this.newUnitName || '').trim().toUpperCase();
    if (!unitName) {
      alertify.error('Please enter unit name');
      return;
    }
    if (this.savingUnit) {
      return;
    }
    this.savingUnit = true;
    saveUnitMaster(this.service, unitName).subscribe(
      (response: any) => {
        this.savingUnit = false;
        if (response?.status === 'success') {
          alertify.success('Unit saved successfully');
          this.isAddUnit = false;
          this.newUnitName = '';
          this.getUnits();
          this.unit = unitName;
        } else {
          alertify.error(response?.status || 'Failed to save unit');
        }
      },
      () => {
        this.savingUnit = false;
        alertify.error('Failed to save unit');
      }
    );
  }

  onProductChange(): void {
    if (!this.selectedProduct) {
      this.productName = '';
      this.productCode = '';
      return;
    }
    this.productName = this.selectedProduct.product_name || this.selectedProduct.material_name || '';
    this.productCode = this.selectedProduct.product_code || this.selectedProduct.material_code || '';
  }

  getLog(): void {
    this.service
      .get(
        'qa/commercialDrugRetention.php?type=getRetentionReviewLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.selectedRecord = record;
    this.isView = true;
  }

  save(form: NgForm): void {
    if (form.invalid || !this.productName.trim() || !this.batchNo.trim()) {
      alertify.error('Please fill required fields');
      return;
    }
    const payload = {
      review_date: this.reviewDate,
      product_name: this.productName,
      product_code: this.productCode,
      batch_no: this.batchNo,
      storage_location: this.storageLocation,
      rack_shelf_no: this.rackShelfNo,
      quantity_on_hand: this.quantityOnHand,
      unit: this.unit,
      pack_condition: this.packCondition,
      label_condition: this.labelCondition,
      seal_intact: this.sealIntact,
      storage_condition_maintained: this.storageConditionMaintained,
      action_taken: this.actionTaken,
      comments: this.comments,
    };
    this.service
      .post('qa/commercialDrugRetention.php?type=saveRetentionReview', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Record saved successfully');
          this.router.navigate(['/qa/retention-commercial-drug-products/review/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      });
  }

  stampReviewedBy(record: any): void {
    if (!record?.id || hasStamp(record.reviewed_by) || this.stamping) return;
    this.stamping = true;
    this.service
      .post('qa/commercialDrugRetention.php?type=updateRetentionReviewStamp', JSON.stringify({ id: record.id }))
      .subscribe(
        (response: any) => {
          this.stamping = false;
          if (response?.status === 'success') {
            record.reviewed_by = response.reviewed_by;
            if (this.selectedRecord?.id === record.id) this.selectedRecord.reviewed_by = response.reviewed_by;
            alertify.success('Reviewed By saved.');
          } else {
            alertify.error(response?.status || 'Failed to stamp');
          }
        },
        () => {
          this.stamping = false;
          alertify.error('Failed to stamp');
        }
      );
  }

  downloadForm(id: number): void {
    this.service.open('qa/commercialDrugRetention.php?type=downloadRetentionReviewForm&id=' + id);
  }
}
