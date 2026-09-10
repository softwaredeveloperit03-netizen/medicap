 import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-mat-approval',
  templateUrl: './mat-approval.component.html',
  styleUrls: ['./mat-approval.component.css']
})
export class MatApprovalComponent implements OnInit {

  loggedInDept = localStorage.getItem('department');
  plant_id = localStorage.getItem('plant_id');
  plant_type = this.service.getPlantConfigFields('plant_type');

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
    this.plant_id = localStorage.getItem('plant_id');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
  }

  ngOnInit(): void {
    this.getMaterialFOrQaApproval();
  }


  results;
  material_type = 'Raw Material';
  DBTable = 'material';
  getMaterialFOrQaApproval() {
    if(this.material_type == 'RND Raw Material' || this.material_type == 'RND Packing Material'){
      this.DBTable = 'rnd_material';
    }else{
      this.DBTable = 'material';
    }
    this.service.get('master/material.php?type=getMaterialFOrQaApproval&material_type='+this.material_type).subscribe((response) => {
        this.results = response;
      });
  }

  selectedResult = [];
  isView = false;
   
  view(data) {
    this.selectedResult = data
    this.isView = true;
  }

  viewMsds(url) {
    url = this.service.url + '../../upload/material/' + url;
    window.open(url, '_blank');
  }


 


  ApproveMaterial(){
    let temp ={};
  
    this.service.post('master/material.php?type=approveMaterialFromQa&id=' + this.selectedResult['id'] + '&DBTable=' + this.DBTable, JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        alertify.success('Material Approved Successfully');
        this.isView = false;
        this.getMaterialFOrQaApproval();
      } else {
        alertify.error(response['status']);
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
