import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
  isView = false;
  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getExpenses();
  }

  getExpenses(){
    this.service.get('account/expense.php?type=getExpenses').subscribe(response=>{
      this.results=response;
    });
  }

  download(){
    this.service.open('account/expense.php?type=downloadExpenses');
  }

}
