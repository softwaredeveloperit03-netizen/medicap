import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-registor',
  templateUrl: './registor.component.html',
  styleUrls: ['./registor.component.css']
})
export class RegistorComponent implements OnInit {

  loading;
  item;
  constructor(private service:DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit(){
  this.getKeys();
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
    // this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
  
  getKeys() {
   
    this.service.get('security/gatepass.php?type=getKeyregister').subscribe(response => {
      this.item = response;
    
    });
  }
  download(){
    this.service.open('security/gatepass.php?type=getKeyRegisterpdf')
  }
}
