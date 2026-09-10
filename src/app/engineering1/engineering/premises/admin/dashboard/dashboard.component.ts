import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe] 
})
export class DashboardComponent implements OnInit {

  lists;
  isView=false;
  from_date='';
  to_date='';

  selectedResult = [];
  constructor(private service: DataAccessService,private datePipe: DatePipe) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getList();
  }
  getList(){
    this.service.get('engineering/premises.php?type=pendingAdminRecord&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.lists=response;
    });
  }
  downloadLog(){
    this.service.open('engineering/premises.php?type=downloadAdminRecord&from_date='+this.from_date+'&to_date='+this.to_date);
  }
  view(index){
    this.selectedResult=this.lists[index];
    this.isView=true;
  }

}
