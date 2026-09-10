import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

 loading;
  isView = false;
  fromdate = '';
  todate = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }

  
  getprint(){
    this.service.open('pdf1/capa.php?type=capalog&fromdate='+this.fromdate+'&todate='+this.todate);
  }
}
