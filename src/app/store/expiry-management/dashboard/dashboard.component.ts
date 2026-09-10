import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  searchQuery = '';
  materials_data: any[] = [];
  expiredCount = 0;
  loading = false;
  matStatus = 'Expired';
  isExpired = false;
  selectedStock: any = {};
  expiredQty: any = 0;

  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getallmaterial();
    this.getexpiredCountAPI();
  }

  getallmaterial() {
    this.loading = true;
    this.service.getJsonArray('common.php?type=getexpiryManagementData').subscribe(
      (response) => {
        this.materials_data = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.materials_data = [];
        this.loading = false;
      }
    );
  }

  filterMaterials(months) {
    this.loading = true;
    this.service.getJsonArray('common.php?type=getMonthWIseData&months=' + months).subscribe(
      (response) => {
        this.materials_data = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.materials_data = [];
        this.loading = false;
      }
    );
  }

  getexpiredCountAPI() {
    this.service.get('common.php?type=expiredCountAPI').subscribe(
      (response) => {
        this.expiredCount = Number(response?.['total_expired_count'] || 0);
      },
      () => {
        this.expiredCount = 0;
      }
    );
  }

  get filteredMaterials(): any[] {
    const rows = Array.isArray(this.materials_data) ? this.materials_data : [];
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return rows;
    }
    return rows.filter((material) =>
      Object.entries(material || {}).some(([_, value]) => value && value.toString().toLowerCase().includes(q))
    );
  }

  MakeExpired(index) {
    this.selectedStock = this.filteredMaterials[index] || {};
    this.expiredQty = 0;
    this.isExpired = true;
  }

  isNumber(value) {
    if (isNaN(value)) {
      alertify.error('Please ENter Numeric Value!!!!!!');
      this.expiredQty = 0;
    }
  }

  distroyMaterial() {
    if (this.expiredQty == 0) {
      alertify.error('Please Enter Expired Qty!!!!!');
      return;
    }
    this.service
      .get(
        'common.php?type=updateStockStatus&status=Expired' +
          '&id=' +
          this.selectedStock['id'] +
          '&expiredQty=' +
          this.expiredQty
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record updated successfully');
          this.isExpired = false;
          this.expiredQty = 0;
          this.getallmaterial();
          this.getexpiredCountAPI();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }
}
