import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  constructor(private service:DataAccessService) { }
  selectedCheckList=[];
  isView=false;
  results;

  ngOnInit(): void {
    this.getList();
  }
  getList(){
    this.service.get('hr/appraisalchecklist.php?type=gateapprove').subscribe(response=>{
      this.results=response;
      console.log('result',this.results);
    })

  }
  view(index) {
    this.selectedCheckList = this.results[index];
    this.isView = true;
    console.log('selectchecklist',this.selectedCheckList);
  }

}
