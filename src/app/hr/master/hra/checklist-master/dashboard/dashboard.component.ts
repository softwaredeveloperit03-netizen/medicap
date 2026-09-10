import { Component, OnInit } from '@angular/core';
import { DataAccessService} from 'src/app/data-access.service'
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
tests;
selectedResult=[];
isView= false;

  constructor(
    private service: DataAccessService,
    private masterHubReturn: MasterHubReturnService
  ) { }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/hra');
  }

  ngOnInit(): void {
    this.getCheckList();
  }

getCheckList(){
  this.service.get('master/checklist.php?type=getCheckList').subscribe(response => {
    this.tests = response;
  })
}

view(index){
  this.selectedResult = this.tests[index];
  this.isView = true;
}

download(){
  this.service.open('hr/master.php?type=downloadChecklistMaster')
}

}
