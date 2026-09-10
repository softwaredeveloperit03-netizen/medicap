import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-inspection',
  templateUrl: './inspection.component.html',
  styleUrls: ['./inspection.component.css']
})
export class InspectionComponent implements OnInit {

  isIntimation = false;
  equipmentslog;

 selectedResult = [];
 department;

   
 constructor(private service:DataAccessService) { }

  ngOnInit() {
     this.getIntimation();
     this.department = localStorage.getItem('department');
  }

  
  getIntimation() {
    
   this.service.get('engineering/preventive.php?type=getIntimations&intimation_status=Inprocess&due_type=Inspection&Inti_department='+localStorage.getItem('department')).subscribe(response => {
     this.equipmentslog = response;
    });
 }

 intimation_to = 'Engineering';
 
 view1(index){
 
   this.selectedResult=this.filteredMaterials[index];
    this.isIntimation = true;
 }

 searchQuery;

 get filteredMaterials(): any[] {
   if (!this.searchQuery || this.searchQuery.trim() === '') {
     return this.equipmentslog; // If search query is empty or whitespace, return all materials
   }
   
   const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
   return this.equipmentslog.filter(material => {
     // Check if any field of the material contains the search query
     return Object.entries(material).some(([key, value]) => {
          return value && value.toString().toLowerCase().includes(query);
       
     });
   });
 }

 

 sendIntimation(formData) {

  // if (!formData.valid) {
  //   alertify.error('All fields are required!');
  //   return;
  // }

  let temp = formData.value;

  this.selectedResult['intimation_data'].push(temp);

  this.service.post('engineering/preventive.php?type=saveIntimation&intimation_status=Inprocess&id=' + this.selectedResult['id'], JSON.stringify(  this.selectedResult['intimation_data'])).subscribe(response => {
    const result = JSON.parse(JSON.stringify(response));
    if (result.status === 'success') {
      this.isIntimation = false;
      this.getIntimation();
       alertify.success('Send Intimation Successfully');
      formData.reset();
      this.intimation_to = 'Engineering';
    } else {
      alertify.error('Failed:' + result.status);
    }
  });
 
 }
 AcceptIntimation( ) {

  // if (!formData.valid) {
  //   alertify.error('All fields are required!');
  //   return;
  // }

  let temp = {};

 
  this.service.post('engineering/preventive.php?type=acceptIntimation&intimation_status=Accepted&id=' + this.selectedResult['id'], JSON.stringify( temp)).subscribe(response => {
    const result = JSON.parse(JSON.stringify(response));
    if (result.status === 'success') {
      this.isIntimation = false;
      this.getIntimation();
       alertify.success('Send Intimation Successfully');
       this.intimation_to = 'Engineering';
    } else {
      alertify.error('Failed:' + result.status);
    }
  });
 
 }




}
