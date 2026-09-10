  import { Component, OnInit } from '@angular/core';
  import {DatePipe} from '@angular/common';
  import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]

})
export class DashboardComponent implements OnInit {

    date;
    departments;
    isView = false;
    entrys;
    applicable='';
    from_date='';
    to_date='';
    selectedBatch = [];
    selectedCondition = [];

    constructor(private service: DataAccessService,private datePipe: DatePipe) {
      this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
      this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
      this.loggedInDept = localStorage.getItem('department');

    }

    ngOnInit() {
    this.getDepartments();
    this.getEntrys();
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



    view(index) {
      this.selectedBatch = this.entrys[index];
      this.selectedCondition=this.selectedBatch['conditions']
      this.isView = true;
    }

    getDepartments(){
      this.service.get('common.php?type=getDepartments').subscribe(response=>{
        this.departments = response;
      })
    }
    getEntrys(){
      this.service.get('ehs/EHS_hotwater.php?type=getHowaterLogLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
        this.entrys = response;
      })
    }

  }
