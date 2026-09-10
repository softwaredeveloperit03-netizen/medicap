import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {

  results1;

  department_name='';
  status=''; 
  from_date='';
  to_date='';

  isView = false;
  results =[];
  selectedResult: [];
  constructor(private service: DataAccessService, private datePipe:DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getIndendsLog();
    this.getDepartment();
  }

  getIndendsLog() {
    this.service.get('purchase/indend/general.php?type=getIndendsLog&department_name='+this.department_name+'&status='+this.status+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe((response:any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.results1=response;
    })
  }

  downloadLog(){
    this.service.open('purchase/indend/general.php?type=indendLogPDF&department_name='+this.department_name+'&status='+this.status+'&from_date='+this.from_date+'&to_date='+this.to_date)
  }

  downloadPDF(){
    this.service.open('purchase/indend/general.php?type=indendpPDF&id='+this.selectedResult['id']);
  }

}
