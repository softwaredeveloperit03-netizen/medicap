import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-due',
  templateUrl: './due.component.html',
  styleUrls: ['./due.component.css']
})
export class DueComponent implements OnInit {

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
  

  
  constructor(private service: DataAccessService,private router:Router) { }

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
    this.selectedResult = this.candidate[index];
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
    this.service.get('hr/medical.php?type=getCandidates_medical_due').subscribe(response  => {
      this.candidate = response;
      this.filterItem();
    });
  }

  getApprovedEmployees() {
    this.service.get('qa.php?type=getPendingMedicalEmployees')
    .subscribe(response => {
      this.employees = response;
    });
  }
  

  // getmedicalcheckup() {
  //   this.service.get('hr/medical.php?type=getCandidates').subscribe(response => {
  //     this.results = response;
  //   });
  // }
  filterItem() {
    this.data = [];
    for (let i = 0; i < this.candidate.length; i++) {
      let material = this.candidate[i];
      if (material['candidate_name'].toUpperCase().includes(this.candidate_name.toUpperCase())) {
        this.data[this.data.length] = material;
      }
    }
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
    temp['due']= 'duecompleted';

    this.service.post('hr/medical.php?type=savePremedical',JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('Medical Check Record sent to Phisician');
        data.resetForm();
        this.router.navigate(['/medical/new'])
        this.getApprovedEmployees();
      } else {
        alertify.error(response['status']);
      }
      });
  }

}
