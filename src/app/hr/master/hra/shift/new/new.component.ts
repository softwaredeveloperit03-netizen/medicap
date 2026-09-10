import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  shift_name;
   night_shift;
    start_time;
     end_time;
      duration;
       date_change;
        half_day;
         full_day;
          default_shift;



  constructor(
    private service: DataAccessService,
    private router: Router,
    private masterHubReturn: MasterHubReturnService
  ) { }

  cancelFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/hra/shift');
  }

  ngOnInit(): void {


  }
  saveAll(data) {
    if (!data.valid) {
      alert('All fields are required');
      return false; // Return false to indicate that data is not valid
    }
    return true; // Return true to indicate that data is valid
  }
  
  save(data) {
    let temp = data.value;

    if (!this.saveAll(data)) {
      return;
    }
  
    this.service.post('hr/shift.php?type=save_shift', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        // this.router.navigate(['/master/hra/shift']);
      } else {
        alertify.error('Failed: An error occurred, Please try again!');
      }
    });
  }
}

