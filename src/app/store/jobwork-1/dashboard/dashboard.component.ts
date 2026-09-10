import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]

})
export class DashboardComponent implements OnInit {
  result;
  selectedReport=[];
  isView=false;
  material_for='';
  material_type='';
  grades='';
  material_name='';
  grade='';
  from_date='';
  to_date='';
  today='';

  constructor(private service: DataAccessService ,private datePipe: DatePipe) {
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');  
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }
  
  ngOnInit(): void {
    this.service.observableGrade.subscribe(response =>{
      this.grades = response;
    });
    this.getIssueLog();
  }

  getIssueLog(){
    this.service.get('store/jobwork.php?type=getJobworkLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.result =response;
    });
  }

  view(index){
    this.selectedReport =  this.result[index];
    this.isView = true;
  }

  download() {
    this.service.open('store/jobwork.php?type=downloadJobworkReport&id=' + this.selectedReport['id']);
  }
}