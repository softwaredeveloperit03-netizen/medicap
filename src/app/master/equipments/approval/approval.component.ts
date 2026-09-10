import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) {
 
  }

  ngOnInit(): void {
    this.getEquipmentsLog();
  }
  
  results;
  selectedResult;
  isView = false;

  getEquipmentsLog() {
    this.service.get('master/equipment.php?type=getEquipmentsforapproval').subscribe(response => {
      this.results = response;
      });
  }

  view(data) {
    this.selectedResult = data
    this.isView = true;
  }

  change_status(){
    let temp ={};
    this.service.post('master/equipment.php?type=update_equipment_status&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isView = false;
         alertify.success('Approved Successfully');
         this.getEquipmentsLog();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }


  
  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter(material => {
      // Check if the equipment_name field contains the search query
      return material.equipment_name && material.equipment_name.toLowerCase().includes(query);
    });
  }


}
