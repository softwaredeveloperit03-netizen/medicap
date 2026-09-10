import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  loading = false;
  users: any[] = [];
  constructor(private service: DataAccessService, private masterHubReturn: MasterHubReturnService) { }

  ngOnInit(): void {
    this.getAssetMasters();
  }

  getAssetMasters() {
    this.loading = true;
    this.service.get('hr/asset.php?type=getAssetMasters').subscribe((response: any) => {
      this.users = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => {
      this.loading = false;
      this.users = [];
    });
  }

  download(){
    this.service.open('hr/asset.php?type=downloadAssetMasterLog');
  }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/hra/asset');
  }
}
