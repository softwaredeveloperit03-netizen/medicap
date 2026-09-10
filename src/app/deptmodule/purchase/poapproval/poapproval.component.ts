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
  results;

  getAllPendingPO() {
    this.service.get('purchase/po/raw.php?type=getAllPendingPOForPlantHeadApproval&po_type=' + this.po_type).subscribe((response) => {
        this.results = response;
    });
  }



  isView = false;
  selectedPO = [];
  selectedBill = [];
  selectedShip = [];

  view(data) {
    this.selectedPO = data;
    this.selectedBill = this.selectedPO['selectedBill'];
    this.selectedShip = this.selectedPO['selectedShip'];
    this.isView = true;
  }

 

  updatePO(status) {
 
    let temp = {};
    temp['id'] = this.selectedPO['id'];
    temp['status'] = status;

    this.service.post('purchase/po/raw.php?type=approvePOByPlantHead', JSON.stringify(temp) ).subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('PO Sent For '+status+ ' Approval.....');
          this.getAllPendingPO();
          this.isView = false;
        } else {
          alertify.error('Failed to Update PO, Please try again!');
        }
      });
  }

   
  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }




}
