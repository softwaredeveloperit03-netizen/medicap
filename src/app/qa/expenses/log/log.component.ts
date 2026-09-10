import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getExpenses();
  }
  getExpenses(){
    this.service.get('account/expense.php?type=getExpenses').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  download(){
    this.service.open('account/expense.php?type=downloadExpenses');
  }

}
