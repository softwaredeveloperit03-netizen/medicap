import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-employment-agency',
  templateUrl: './employment-agency.component.html',
  styleUrls: ['./employment-agency.component.css']
})
export class EmploymentAgencyComponent implements OnInit {
  isNewAgency;
  isshowAll;
  isshowPending;
  agencies;
  checkers;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getAgencies();
  }

  getAgencies() {
    this.service.get('hrDepartment.php?type=getEmploymentAgencies')
    .subscribe(response => {
      this.agencies = response;
    });
  }

  saveAgency(agencyData) {
    this.service.post('hrDepartment.php?type=addEmployeeAgency', JSON.stringify(agencyData.value))
    .subscribe(response => {
      agencyData.reset();
      this.agencies = response;
      this.isNewAgency = false;
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

}
