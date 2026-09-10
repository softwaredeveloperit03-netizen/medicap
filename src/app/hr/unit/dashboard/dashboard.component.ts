import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getUnit();
  }

  getUnit(){
    this.service.get('management/unit.php?type=getUnits').subscribe(response=>{
      this.results=response;
    });
  }
  download(){
    this.service.open('management/unit.php?type=downloadUnits')
  }

}
