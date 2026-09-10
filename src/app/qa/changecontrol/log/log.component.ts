import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  providers: [DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  department_name = '';
  change_related = '';
  fromdate;
  todate;
  results;
  departmentlist = [];
  selectedReport = [];
  
  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }
  ngOnInit() {
    this.getChangeControls();
    this.getdeparments();
  }

  getdeparments(){
    this.service.get('changecontrol.php?type=getDepartments').subscribe((response:any) =>{
      this.departmentlist =  response;
    });
  }

  getChangeControls() {
    this.service.get('changecontrol.php?type=getChangeControls&fromdate='+this.fromdate+'&todate='+this.todate+'&department_name='+this.department_name+'&change_related='+this.change_related).subscribe((response:any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  getprint(){
    this.service.open('pdf1/changecontrol.php?type=changecontrollog&fromdate='+this.fromdate+'&todate='+this.todate+'&getdepartment='+this.department_name+'&change_related='+this.change_related);
  }

  download(value, action){
    if (action == 'mannual') {
      this.service.open('pdf1/changecontrol.php?type=changecontrol&ctrl_no='+value);
    } else {
      this.service.open('pdf1/changecontrol.php?type=changecontroldigital&ctrl_no='+value);
    }
  }
}