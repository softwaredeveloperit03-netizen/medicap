import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-material-issue-request',
  templateUrl: './material-issue-request.component.html',
  styleUrls: ['./material-issue-request.component.css']
})
export class MaterialIssueRequestComponent implements OnInit {

  
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getRndMaterialRequestToStore();
  }
 

 
  results;
  getRndMaterialRequestToStore() {
    this.service.get('npd/npd.php?type=getRndMaterialRequestToStore&requestTo=STORE').subscribe(response => {
      this.results = response;
    });
  }

  batches;
  getAvaliableStockByMaterialCode(material_code) {
    this.service.get('common.php?type=getAvaliableStockByMaterialCode&material_code='+material_code).subscribe(response => {
      this.batches = response;
    });
  }
 

  isView = false;


  selectedMaterial = [];
  view(data){
    this.batches = [];
    this.getAvaliableStockByMaterialCode(data.reqMaterialCode);
    this.selectedMaterial = data;
    this.isView = true;
    this.selectedBatch = {};
  } 


  selectedBatch = {};
  onBatchChange(batch_no){
      if (!batch_no) {
        // If batch_no is null, undefined, or empty
        this.selectedBatch = {};
      } else {
        this.selectedBatch = this.batches.find(batch => batch.batch_no === batch_no) || {};
      }
  }
 
  submitRequirementForDevelopementRequest() {

      let temp = {};
  
      this.service.post('rnd/predevelopment.php?type=submitRequirementForDevelopementRequest', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success('Product Requirement Saved Successfully!');
          this.isView =false;
          this.getRndMaterialRequestToStore();
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

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entryOn') {
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
