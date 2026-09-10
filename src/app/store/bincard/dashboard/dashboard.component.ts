import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
import { ClrLoadingState } from '@clr/angular';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe],
})
export class DashboardComponent implements OnInit {
  isView = false;
  isView1 = false;
  isStockSummary = false;
  isNewIssue = false;
  loading = false;
  detailLoading = false;
  searchQuery = '';
  bincardLog: any[] = [];
  stock_summary: any[] = [];
  selectedResult: any = {};
  selectedGRN: any = {};
  issued: any[] = [];
  material_type = 'Raw Material';
  balance_qty: any;
  products: any[] = [];
  submitBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;
  validateBtnState: ClrLoadingState = ClrLoadingState.DEFAULT;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {}

  ngOnInit(): void {
    this.getMaterialOutDetails();
    this.getProducts();
  }

  getMaterialOutDetails() {
    this.loading = true;
    const type = encodeURIComponent(this.material_type || 'Raw Material');
    this.service.getJsonArray('store/bincard.php?type=getMaterials&material_type=' + type).subscribe(
      (response) => {
        this.bincardLog = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.bincardLog = [];
        this.loading = false;
      }
    );
  }

  get filteredMaterials(): any[] {
    const rows = Array.isArray(this.bincardLog) ? this.bincardLog : [];
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return rows;
    }
    return rows.filter((material) =>
      Object.entries(material || {}).some(([_, value]) => value && value.toString().toLowerCase().includes(q))
    );
  }

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe((response) => {
      this.products = Array.isArray(response) ? response : [];
    });
  }

  view(index: number) {
    const row = this.filteredMaterials[index] || {};
    this.selectedResult = { ...row, stock_data: [] };
    this.isView = true;
    this.isView1 = false;
    this.isStockSummary = false;
    this.loadMaterialStockData(row['material_code']);
  }

  loadMaterialStockData(materialCode: string) {
    if (!materialCode) {
      this.selectedResult = { ...this.selectedResult, stock_data: [] };
      return;
    }
    this.detailLoading = true;
    this.service
      .get(
        'store/bincard.php?type=getMaterialStockData&material_code=' +
          encodeURIComponent(materialCode)
      )
      .subscribe(
        (response) => {
          this.selectedResult = {
            ...this.selectedResult,
            stock_data: Array.isArray(response) ? response : [],
          };
          this.detailLoading = false;
        },
        () => {
          this.selectedResult = { ...this.selectedResult, stock_data: [] };
          this.detailLoading = false;
        }
      );
  }

  show_stock_summary(grn_data: any) {
    let balance_stock = 0;
    this.validateBtnState = ClrLoadingState.LOADING;
    this.service
      .get(
        'store/bincard.php?type=get_grn_stock_summary_by_material_code&material_code=' +
          encodeURIComponent(this.selectedResult['material_code'] || '') +
          '&grn_no=' +
          encodeURIComponent(grn_data['grn_no'] || '') +
          '&ar_no=' +
          encodeURIComponent(grn_data['ar_no'] || '')
      )
      .subscribe(
        (response) => {
          this.validateBtnState = ClrLoadingState.DEFAULT;
          this.stock_summary = Array.isArray(response) ? response : [];
          for (let x = 0; x < this.stock_summary.length; x++) {
            balance_stock =
              balance_stock +
              (Number(this.stock_summary[x]['received_qty'] || 0) - Number(this.stock_summary[x]['issued_Qty'] || 0));
            this.stock_summary[x]['balance_qty'] = balance_stock;
          }
          this.isStockSummary = true;
          this.isView = false;
          this.isView1 = false;
        },
        () => {
          this.validateBtnState = ClrLoadingState.DEFAULT;
          this.stock_summary = [];
        }
      );
  }

  viewDetails(index: number) {
    this.isView1 = true;
    this.isStockSummary = false;
    this.isView = false;
    this.selectedGRN = this.stock_summary[index] || {};
    this.issued = Array.isArray(this.selectedGRN['issued']) ? this.selectedGRN['issued'] : [];
    this.balance_qty = this.selectedGRN['balance_qty'];
  }

  download() {
    this.service.open('store/bincard.php?type=downloadMaterialLog&material_type=' + encodeURIComponent(this.material_type));
  }

  downloadgrn() {
    this.service.open('store/bincard.php?type=downloadGrn&id=' + this.selectedResult['id']);
  }

  downloadIssueRecords() {
    this.service.open('store/bincard.php?type=downloadMaterialBinCard&ar_no=' + this.selectedGRN['ar_no']);
  }

  saveIssuedEntry(data) {
    if (!data.valid) {
      alertify.error('Invalid Data!');
      return;
    }
    this.submitBtnState = ClrLoadingState.LOADING;
    const temp = data.value;
    temp['grn_no'] = this.selectedGRN['grn_no'];
    temp['ar_no'] = this.selectedGRN['ar_no'];
    temp['material_code'] = this.selectedGRN['material_code'];
    temp['unit'] = this.selectedGRN['unit'];
    this.service.post('store/bincard.php?type=issueMaterial', JSON.stringify(temp)).subscribe((response) => {
      this.submitBtnState = ClrLoadingState.DEFAULT;
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully!');
        this.isNewIssue = false;
        this.isView = false;
        this.isView1 = false;
        data.reset();
        this.getMaterialOutDetails();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
