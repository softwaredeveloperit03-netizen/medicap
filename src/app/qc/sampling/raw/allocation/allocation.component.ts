import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css'],
  providers:[DatePipe]
})
export class AllocationComponent implements OnInit {

   isView = false;
   results;
   selectedSampling = [];
   constructor(private service: DataAccessService) { }
 
   ngOnInit() {
     this.getPendingAllocation();
     this.getQcPersons();
    }
   
   material_type = 'Raw Material';
   getPendingAllocation() {
     this.service.get('qc/sampling/raw.php?type=getPendingAllocation&material_type=' + this.material_type).subscribe(response => {
       this.results = response;
     });
   }
 


  qcEmployee;
  getQcPersons() {
    this.service.get('hr/employee.php?type=getEmployeesbydept&department_name=Quality Control').subscribe(response => {
      this.qcEmployee = response;
    });
  }

  isDIGI: boolean=false
  temp: any;
  
  openDigiSign(data){

    const selectedItems = this.filteredMaterials.filter(
      (term) => term.check
    );

    if (selectedItems.length === 0) {
      alertify.error('Pleae Select Atlest One Material to Allocate!');
      return;
    }

    this.isDIGI = true;
    this.temp = data
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alertify.error('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + localStorage.getItem('emp_id')).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.loginPassward ='';
        this.allocate()
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }

 
  allocate() {

    const selectedItems = this.filteredMaterials.filter(
      (term) => term.check
    );
 
    let temp = this.temp.value;
    temp['materials'] = selectedItems;

    if (selectedItems.length === 0) {
      alertify.error('Pleae Select Atlest One Material to Allocate!');
      return;
    }

    this.service.post('qc/sampling.php?type=allocatePerson', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Sampling Person Allocated Successfully!');
        this.getPendingAllocation();
        this.temp.reset();
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
 
  
 