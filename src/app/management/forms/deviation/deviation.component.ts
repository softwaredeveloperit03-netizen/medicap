import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-deviation',
  templateUrl: './deviation.component.html',
  styleUrls: ['./deviation.component.css'],
  providers:[DatePipe]
})
export class DeviationComponent implements OnInit {

  isView = false;
  department = '';
  category = '';
  fromdate;
  todate;
  results = [];
  departmentlist = [];
  selectedDev = [];
  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }
  
  ngOnInit() {
    this.getDeviations();
    this.getdeparments();
  }
  getdeparments(){
    this.service.get('common.php?type=getDepartments').subscribe((response:any) =>{
      this.departmentlist =  response;
    });
  }
  getDeviations() {
    this.service.get('deviation.php?type=getDeviation&fromdate='+this.fromdate+'&todate='+this.todate+'&getdepartment='+this.department+'&category='+this.category).subscribe((response:any) => {
      this.results = response;
    });
  }
  resetFilter(){
    this.department = '';
    this.category = '';
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.getDeviations();
  }

  view(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }

  getprint(){
    this.service.open('pdf1/deviation.php?type=deviationlog&fromdate='+this.fromdate+'&todate='+this.todate+'&getdepartment='+this.department+'&category='+this.category);
  }

  download(value, action){
    if (action == 'mannual') {
      this.service.open('pdf1/deviation.php?type=deviation&dev_no='+value);
    } else {
      this.service.open('pdf1/deviation.php?type=deviationdigital&dev_no='+value);
    }
  }

}
