import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-allocation-log',
  templateUrl: './allocation-log.component.html',
  styleUrls: ['./allocation-log.component.css']
})
export class AllocationLogComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getTestingAllocationLog();
    this.getQcChecmist();
    this.getMicrobiologist();
    this.getLabsLog();
  }
   
  isEdit = false;
  results;
  material_type = 'Raw Material';
  getTestingAllocationLog() {
    this.service.get('qc/testing/raw.php?type=getTestingAllocationLog&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }

  downloadTestingAllocationLog() {
    this.service.open('qc/testing/raw.php?type=downloadTestingAllocationLog&material_type=' + this.material_type);
  }


  employees;
  getQcChecmist() {
    this.service.get('qc/testing/raw.php?type=getQcChecmist').subscribe(response => {
      this.employees = response;
    });
  }

  microEmployees;
  getMicrobiologist() {
    this.service.get('qc/testing/raw.php?type=getMicrobiologist').subscribe(response => {
      this.microEmployees = response;
    });
  }

  labs;
  getLabsLog() {
    this.service.get('qc/lab.php?type=getLabsLog').subscribe(response => {
      this.labs = response;
    });
  }


  selectedTesting = {};
  isView = false;

  view(data) {
    this.selectedTesting = data;
    this.isView = true;
  }

 

  updateAllocatedPerson(data) {
    let temp = data;
    temp['id'] = this.selectedTesting['id'];
  
    this.service.post('qc/testing/raw.php?type=updateAllocatedPerson', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
      }else{
        alertify.error("some error Ocuured");
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
