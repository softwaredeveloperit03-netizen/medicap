import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-changecontrol',
  templateUrl: './changecontrol.component.html',
  styleUrls: ['./changecontrol.component.css'],
  providers: [DatePipe]
})
export class ChangecontrolComponent implements OnInit {

  isView = false;
  department = '';
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
    this.service.get('common.php?type=getDepartments').subscribe((response:any) =>{
      this.departmentlist =  response;
    });
  }

  getChangeControls() {
    this.service.get('changecontrol.php?type=getChangeControls&fromdate='+this.fromdate+'&todate='+this.todate+'&getdepartment='+this.department+'&change_related='+this.change_related).subscribe((response:any) => {
      this.results = response;
    });
  }

  resetFilter(){
    this.department = '';
    this.change_related = '';
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.getChangeControls();
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  getprint(){
    this.service.open('pdf1/changecontrol.php?type=changecontrollog&fromdate='+this.fromdate+'&todate='+this.todate+'&getdepartment='+this.department+'&change_related='+this.change_related);
  }

  download(value, action){
    if (action == 'mannual') {
      this.service.open('pdf1/changecontrol.php?type=changecontrol&ctrl_no='+value);
    } else {
      this.service.open('pdf1/changecontrol.php?type=changecontroldigital&ctrl_no='+value);
    }
  }

}
