import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  qualifications;
  isNewQualification = false;
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }
 //----------------------For Pagination---------------------------------//

 currentPage: number = 1;
 pageSize: number = 10; // Default page size
 
 // Function to calculate the starting Sr.No based on the current page and page size
 // calculateStartSrNo(): number {
   //   return (this.currentPage - 1) * this.pageSize +1;
   // }
 calculateStartSrNo(): number {
   return (this.currentPage - 1) * 10 ;
 }
 
 // Update the current page when the page changes
 onPageChange(page: number) {
   this.currentPage = page;
   console.log(this.currentPage);
 }
 
 // Handle the change event for the page size dropdown
 onPageSizeChange(event: any) {
   this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
 }
 viewf(){
  //  this.isView=false;
   //  this.getLogs();
   this.currentPage=1;
   this.pageSize =10;
   
 }
 // ---------------------------------------------------------------------//
  ngOnInit() {
    this.getQualifications();
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }


  getQualifications() {
    this.service.get('common.php?type=getQualifications')
    .subscribe(response => {
      this.qualifications = response;
    });
  }

  addQualification(qualificationForm) {
    this.isNewQualification = false;
    this.service.post('master/qualification.php?type=saveQualification', JSON.stringify(qualificationForm.value))
    .subscribe(response => {
      if(response['status']=='success'){
        qualificationForm.reset();
        this.getQualifications();
        alertify.success("save successfully")
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }

}



 
