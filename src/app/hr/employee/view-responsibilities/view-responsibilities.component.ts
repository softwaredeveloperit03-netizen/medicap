import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-view-responsibilities',
  templateUrl: './view-responsibilities.component.html',
  styleUrls: ['./view-responsibilities.component.css']
})
export class ViewResponsibilitiesComponent implements OnInit {
  responsibilities;
  list1;
  isShow = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getResponsibilities();
  }

  getResponsibilities() {
    this.service.get('hrDepartment.php?type=getEmployeeResponsibilities')
    .subscribe(response => {
      this.responsibilities = response;
      this.list1 = this.responsibilities['responsiblities'];
      this.isShow = true;
    });
  }

}
