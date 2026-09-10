import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-purchase-requisition-hub',
  templateUrl: './hub.component.html',
  styleUrls: ['./hub.component.css']
})
export class PurchaseRequisitionHubComponent implements OnInit {

  results: any[] = [];
  searchQuery = '';
  materialType = 'RM/PM Material';
  loading = false;

  isView = false;
  materials: any[] = [];
  selectedResult: any = {};

  currentPage = 1;
  pageSize = 10;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getMaterialRequisitions(this.materialType);
  }

  getMaterialRequisitions(value: string) {
    this.materialType = value;
    this.isView = false;
    this.loading = true;
    const endpoint = value === 'RM/PM Material'
      ? 'store/material_requisition.php?type=getRmpmRequisitions'
      : 'store/material_requisition.php?type=getGeneralRequisitions&department_name=' +
          encodeURIComponent(localStorage.getItem('department') || '');

    this.service.get(endpoint).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => {
      this.results = [];
      this.loading = false;
    });
  }

  get filteredResults(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((row) =>
      Object.values(row).some(value =>
        value != null && typeof value !== 'object' && value.toString().toLowerCase().includes(query)
      )
    );
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onPageChange(page: number) {
    this.currentPage = page;
  }

  view(indend: any) {
    this.selectedResult = indend || {};
    this.materials = (this.selectedResult && this.selectedResult['materials']) || [];
    this.isView = true;
  }

  viewf() {
    this.isView = false;
    this.currentPage = 1;
    this.pageSize = 10;
  }

  check(item: any) {
    if (!item || !item.request_no) {
      return;
    }
    this.service.get('store/material_requisition.php?type=checkRequisition&request_no=' +
      encodeURIComponent(item.request_no)).subscribe((response: any) => {
      if (response && response['status'] === 'success') {
        alertify.success('Requisition ' + item.request_no + ' checked successfully');
        this.getMaterialRequisitions(this.materialType);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
