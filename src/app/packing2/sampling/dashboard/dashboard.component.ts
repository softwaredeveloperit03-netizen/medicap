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
  results;
  results1;
  batch_no='';
  from_date='';
  to_date='';
  selectedResult=[];
  isView=false;
  today='';
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {      
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');   
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.loggedInDept = localStorage.getItem('department');

     }

  ngOnInit() {
    this.getIntimationLog();
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
  getIntimationLog(){
    this.service.get('packing/sampling.php?type=getIntimationLog&from_date='+this.from_date+ '&to_date='+this.to_date).subscribe(response=>{
      this.results1=response;
      this.filterProduct()
    });
  }

  filterProduct() {
    this.results = [];
    for (let i = 0; i < this.results1.length; i++) {
      let material = this.results1[i];
      if (material['batch_no'].toUpperCase().includes(this.batch_no.toUpperCase())) {
        this.results[this.results.length] = material;
      }
    }
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  download(){
    this.service.open('packing/sampling.php?type=downloadIntimationSlip&from_date='+this.from_date+ '&to_date='+this.to_date);
  }

  downloadReport(){
    this.service.open('packing/sampling.php?type=downloadIntimationRecord&id=1'+ this.selectedResult['id']);
  }

} 