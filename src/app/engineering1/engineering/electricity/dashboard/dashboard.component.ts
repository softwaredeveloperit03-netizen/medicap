  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  import { DatePipe } from '@angular/common';
  
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {
    from_date = '';
    to_date= '';
    results
    min;
    constructor(private service: DataAccessService, private datePipe: DatePipe) {
      this.loggedInDept = localStorage.getItem('department');
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
    ngOnInit() {    
      this.getTankSanitizationLog();
        this.get_rights();

    }
  
    getTankSanitizationLog(){
      this.service.get('engineering/electricity.php?type=getElectricityLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
        this.results=response;
      });
    }
    download(){
      this.service.open('engineering/electricity.php?type=downloadElectricityLog&from_date='+this.from_date+'&to_date='+this.to_date)
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
  
  


  }
  