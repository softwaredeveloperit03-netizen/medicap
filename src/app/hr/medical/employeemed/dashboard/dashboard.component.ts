import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {


  candidate;
  constructor(private service: DataAccessService,private router:Router) {}

  ngOnInit(): void {
    this.getpendingCandidate();
    this.get_med_test();
  }

  isView = false;

  getpendingCandidate() {
    this.service.get('hr/medical.php?type=getCandidates_medical_due').subscribe(response  => {
      this.candidate = response;
     });
   }

   get_med_test() {
    this.service.get('master/test.php?type=get_Test_for_medical').subscribe(response => {
      this.results = response;
     })
  }
  results;


   selectedResult;

   viewForm(index){
    this.isView = true;
    this.selectedResult = this.filteredMaterials[index];
  }




   searchQuery;

   get filteredMaterials(): any[] {
     if (!this.searchQuery || this.searchQuery.trim() === '') {
       return this.candidate; // If search query is empty or whitespace, return all materials
     }
 
     const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
     return this.candidate.filter((material) => {
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
 
 
 
   addMedicalRecord(data) {
    if (!data.valid) {
      alertify.error('All fileds are required');
      return;
    }
    const selectedCheckboxes = this.results.filter(item => item.selected);
    let temp = data.value;
    temp['emp_id']= this.selectedResult['emp_id'];
    temp['lastId']= this.selectedResult['lastId'];
    temp['tests']= selectedCheckboxes;
    this.service.post('hr/medical.php?type=saveRegularRoutineCheckup',JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('Medical Check Record sent to Phisician');
        this.isView = false;
        data.resetForm();
        this.router.navigate(['/medical/new'])
        this.getpendingCandidate();
      } else {
        alertify.error(response['status']);
      }
      });
  }
 
 


}
