import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify: any;


@Component({
  selector: 'app-poapproval',
  templateUrl: './poapproval.component.html',
  styleUrls: ['./poapproval.component.css'],
  providers: [DatePipe]
})
export class PoapprovalComponent implements OnInit {

  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getAllPendingPO();
  }

 

 
  po_type = 'Raw Material';
  results: any[] = [];
  loading = false;
  searchQuery = '';

  getAllPendingPO() {
    this.loading = true;
    const type = encodeURIComponent(this.po_type || 'All');
    this.service.get('purchase/po/raw.php?type=getAllPendingPOForPurchaseHeadApproval&po_type=' + type).subscribe(
      (response) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.results = [];
        this.loading = false;
      }
    );
  }



  isView = false;
  selectedPO = [];
  selectedBill = [];
  selectedShip = [];
  isLanding = false;
  view(data) {
    this.selectedPO = data;
    this.selectedBill = this.selectedPO['selectedBill'];
    this.selectedShip = this.selectedPO['selectedShip'];
    this.isView = true;
    this.isLanding = !this.isLanding;
  }

 

  updatePO(status) {
 
    let temp = {};
    temp['id'] = this.selectedPO['id'];
    temp['status'] = status;

    this.service.post('purchase/po/raw.php?type=approvePOFromPurchaseHead', JSON.stringify(temp) ).subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('PO Sent For '+status+ ' Approval.....');
          this.getAllPendingPO();
          this.isView = false;
        } else {
          alertify.error('Failed to Update PO, Please try again!');
        }
      });
  }

  get filteredMaterials(): any[] {
    const list = Array.isArray(this.results) ? this.results : [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return list;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return list.filter((material) => {
      if (!material || typeof material !== 'object') {
        return false;
      }
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            !isNaN(dateValue.getTime()) &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        }
        return value != null && value.toString().toLowerCase().includes(query);
      });
    });
  }




}
