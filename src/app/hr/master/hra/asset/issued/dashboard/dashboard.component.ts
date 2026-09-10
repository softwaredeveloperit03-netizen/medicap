import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  item;
  constructor(
    private service: DataAccessService,
    private masterHubReturn: MasterHubReturnService
  ) { }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/hra/asset');
  }

  ngOnInit() {
    this.getData();
  }
  getData() {
   
    this.service.get('hr/asset.php?type=getasset_issued').subscribe(response => {
      this.item = response;
    
    });
  }

  download(){
    this.service.open('hr/asset.php?type=downloadassert_log');
  }
}
