import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  employees;
  candidate;
  phisicians;
  entries = [{"id":"1","emp_name":"Kiran","checkup_type":"Pre Employeement"}];
  selectedResult=[];
  isView = false;
  results;
  data=[];
  candidate_name='';

 departments;
 plant_id;

 frequency= 6;
  

  
  constructor(private service: DataAccessService,private router:Router) {

    this.plant_id = this.service.getPlantConfigFields('plant_id');
   }

  ngOnInit() {
    this.getApprovedPhisicians();
    this.getApprovedEmployees();
    this.getpendingCandidate();
    this.getDepartments();
    this.get_med_test();
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
  viewForm(index){
    this.isView = true;
    this.selectedResult = this.filteredMaterials[index];
  }
  getApprovedPhisicians() {
    this.service.get('hr/physician.php?type=getPhysicians')
    .subscribe(response => {
      this.phisicians = response;
    });
  }
  getDepartments() {
    this.service.get('common.php?type=getDepartments')
    .subscribe(response => {
      this.departments = response;
    });
  }
  get_med_test() {
    this.service.get('master/test.php?type=get_Test_for_medical').subscribe(response => {
      this.results = response;
     })
  }

  getpendingCandidate() {
    this.service.get('hr/medical.php?type=getCandidates').subscribe(response  => {
      this.candidate = response;
      this.candidate.forEach(item => {
        item.candidate_name = item.firstname + ' ' + item.lastname;
      });
     });
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








  getApprovedEmployees() {
    this.service.get('qa.php?type=getPendingMedicalEmployees')
    .subscribe(response => {
      this.employees = response;
    });
  }
   

  addMedicalRecord(data) {
    if (!data.valid) {
      alertify.error('All fileds are required');
      return;
    }
    const selectedCheckboxes = this.results.filter(item => item.selected);
    let temp = data.value;
    temp['emp_id']= this.selectedResult['id'];
    temp['tests']= selectedCheckboxes;
    this.service.post('hr/medical.php?type=savePremedical',JSON.stringify(temp))
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
