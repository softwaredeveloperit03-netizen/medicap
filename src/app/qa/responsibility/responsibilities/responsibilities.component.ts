import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-responsibilities',
  templateUrl: './responsibilities.component.html',
  styleUrls: ['./responsibilities.component.css']
})
export class ResponsibilitiesComponent implements OnInit {

  isShow = false;
  isView = false;
  responsibilities = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getEmployeeResponsibilities();
  }

  getEmployeeResponsibilities() {
    this.service.get('hr/responsibility.php?type=getMyResponsibilities').subscribe((response: any) => {
      this.responsibilities = response;
      if (this.responsibilities.length == 0) {
        this.isView = true;
      }
      this.isShow = true;
    });
  }

  update() {
    this.service.post('hr/responsibility.php?type=acceptResponsibilities', JSON.stringify(this.responsibilities)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Responsibilities Accepted Successfully');
        this.getEmployeeResponsibilities();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
