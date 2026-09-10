import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
  grades;
  from_date = '';
  to_date = '';
  isView = false;
  results;
  grndetails=[];
  selectedReport = [];
  constructor(private service: DataAccessService) {
   this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getGRNLog();
    this.getgensubtype();
    this.get_rights();
  }
 
  material_subtype = 'Cultures';
  getGRNLog() {
    this.service.get('qc/chemical.php?type=getGRNLog&material_subtype='+this.material_subtype).subscribe(response => {
      this.results = response;
    });
  }

  subtypes;
  getgensubtype() {
    this.service.get('master/materialtype.php?type=get_gen_material_subtype&material_type=Microbiology Materials').subscribe((response: any) => {
      this.subtypes = response;
    });
  }


  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  // -----------------------------------------12th july------------------------------------------//

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

  get_rights() {
   this.service
     .get(
       'hr/employee.php?type=getrights&emp_id=' +
         localStorage.getItem('emp_id') +
         '&dep_name=' +
         localStorage.getItem('department')
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
  //---------------------------------------------------------------------------------//


}
