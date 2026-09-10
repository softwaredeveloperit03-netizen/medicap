import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
declare let alertify;

@Component({
  selector: 'app-govtagency',
  templateUrl: './govtagency.component.html',
  styleUrls: ['./govtagency.component.css']
})
export class GovtagencyComponent implements OnInit {
  
 
   isNew = false;
    
   constructor(private service: DataAccessService) { }
 
   ngOnInit() {
     this.getCandidate();
   }
 
   
   results;
   getCandidate() {
     this.service.get('hr/candidate.php?type=getCandidateForPlantHeadAPproval').subscribe((response) => {
         this.results = response;
     });
   }
   
   selectedCandidate = [];
  
   viewData(data){
     this.selectedCandidate = data;
     this.isNew = true;
   }
 
   updateInterviewStatusFromPlantHead(status) {
 
     let temp = {};
     temp['candidate_id'] = this.selectedCandidate['id'];
     temp['status'] = status;
   
     this.service.post('hr/candidate.php?type=updateInterviewStatusFromPlantHead',JSON.stringify(temp)).subscribe((response) => {
         if (response['status'] == 'success') {
           alertify.success('Candidate Interview Scedule Successfully');
           this.isNew = false;
           this.getCandidate();
           this.selectedCandidate = [];
         } else {
           alertify.error('An error occured, please try again');
         }
       });
   }
 
 
  
   viewResume(url) {
     url = this.service.url + '../..' + url;
     window.open(url, '_blank');
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
 