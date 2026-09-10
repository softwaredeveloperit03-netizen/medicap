import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  shifts ;
  constructor(
    private service: DataAccessService,
    private router: Router,
    private masterHubReturn: MasterHubReturnService
  ) { }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/hra');
  }

  ngOnInit(): void {
  
  this.getShiftLog();
}

 getShiftLog() {
  this.service.get('hr/shift.php?type=get_shift_list').subscribe(response => {
    this.shifts = response;
  });
}
 

selectedshift;

  delShift(index){


    this.selectedshift =  this.shifts[index];

    console.log(index);
    console.log(this.selectedshift);

    this.service.post('hr/shift.php?type=deleteShift&id='+this.selectedshift['id'] , JSON.stringify(this.selectedshift)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Delete Successfully');
        this.getShiftLog();

      } else {
        alertify.error('Failed: An error occured, Please try again!');
      } 

    });


  }

}